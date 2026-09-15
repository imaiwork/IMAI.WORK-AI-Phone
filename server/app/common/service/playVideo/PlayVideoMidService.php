<?php

declare(strict_types=1);

namespace app\common\service\playVideo;

use app\common\enum\playVideo\PlayVideoEnum;
use app\common\service\ToolsService;
use think\facade\Log;

/**
 * 爆款玩法 · 中台调用封装。
 * 走 ToolsService 既有授权码签名（key/sign 头，HMAC 签 body 原文）。
 * 中台契约见 ai-new-zhongtai/docs/baokuan-play/01-zhongtai.md §2：
 * 模板字段直传，提交返回 {task_id,...}，查询/回调字段同构。
 */
class PlayVideoMidService
{
    /** 提交路径：face/dance 共用换脸单入口(场景 play_swap)；clip 独立(场景 play_splice) */
    private const SUBMIT_PATHS = [
        PlayVideoEnum::PLAY_FACE => '/api/playvideo/swap',
        PlayVideoEnum::PLAY_DANCE => '/api/playvideo/swap',
        PlayVideoEnum::PLAY_CLIP => '/api/playvideo/splice',
    ];

    /**
     * 提交生成。$params 已是中台契约形状（station_task_id/template/face_url/...）。
     * 返回中台信封 data 段；失败抛异常（message 已尽量对客可读）。
     *
     * @throws \Exception
     */
    public static function submit(string $play, array $params): array
    {
        if (!in_array($play, PlayVideoEnum::PLAYS, true)) {
            throw new \Exception('不支持的玩法');
        }
        $params['play'] = $play;
        $params['callback_url'] = self::notifyUrl();
        $path = self::SUBMIT_PATHS[$play];

        $response = app(ToolsService::class)
            ->setApiUrl($path)
            ->setRequest($params)
            ->setMethod('POST')
            ->setTimeout(10, 30)
            ->sendWithoutThrow()
            ->response;

        return self::unwrap($response, '提交生成失败');
    }

    /**
     * 查任务（免费接口）。返回中台 data 段：status/step/step_label/progress/video_url/fail_reason/refunded。
     *
     * @throws \Exception
     */
    public static function queryTask(string $midTaskId): array
    {
        $response = app(ToolsService::class)
            ->setApiUrl('/api/playvideo/task/' . urlencode($midTaskId))
            // ToolsService 为 app() 单例,request 属性会残留上一次调用的参数
            // (如设备CDK同步的 updated_since);GET 必须显式清空,否则脏参数拼进
            // query 且签名按参数 json 计算,数据侧按空 body 验签必 401
            ->setRequest([])
            ->setMethod('GET')
            ->setTimeout(10, 20)
            ->sendWithoutThrow()
            ->response;

        return self::unwrap($response, '查询任务失败');
    }

    /**
     * 回调地址：带随机 token 鉴权（.env 的 PLAY 段 NOTIFY_TOKEN）。
     * 未配置外网域名/token 时返回空——不带回调纯靠 cron 兜底，功能不断（本地/内网可测）。
     */
    /** 回调令牌：委托全站共用 CallbackTokenService（.env 的 NOTIFY 段 TOKEN） */
    public static function notifyToken(): string
    {
        return \app\common\service\CallbackTokenService::token();
    }

    public static function notifyUrl(): string
    {
        $host = (string)config('app.app_host');
        $token = self::notifyToken();
        if ($host === '' || $token === '' || (function_exists('ToolsIsInternalHost') && ToolsIsInternalHost($host))) {
            Log::channel('playvideo')->info('play_video 未配置外网回调(host/token)，将依赖 cron 兜底同步');
            return '';
        }
        return \app\common\service\CallbackTokenService::appendTo(rtrim($host, '/') . '/api/playVideo/notify');
    }

    /**
     * 拆中台信封 {code:10000,message,data}；非 10000 抛出 message。
     *
     * @throws \Exception
     */
    private static function unwrap($response, string $fallback): array
    {
        if (!is_array($response)) {
            throw new \Exception($fallback . '：中台无响应');
        }
        $code = (int)($response['code'] ?? 0);
        if ($code !== 10000) {
            $message = trim((string)($response['message'] ?? ''));
            // 站长欠费不向 C 端暴露（02 文档 §3）
            if (str_contains($message, '站点额度不足')) {
                Log::channel('playvideo')->error('play_video 站点中台额度不足，请站长充值');
                throw new \Exception('服务繁忙，请联系客服');
            }
            throw new \Exception($message !== '' ? $message : $fallback);
        }
        $data = $response['data'] ?? [];
        return is_array($data) ? $data : [];
    }
}
