<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\playVideo\PlayVideoLogic;
use app\common\service\CallbackTokenService;
use app\common\service\playVideo\PlayVideoFaceService;
use think\facade\Log;
use think\response\Json;

/**
 * 爆款玩法（影视换脸改词/跳舞换脸）。
 * 路由：/api/playVideo/{action}。notify 为中台回调（token 鉴权，免登录）。
 */
class PlayVideoController extends BaseApiController
{
    public array $notNeedLogin = ['notify'];

    /**
     * 提交生成（预扣算力，失败自动退）
     */
    public function submit(): Json
    {
        $result = PlayVideoLogic::submit($this->userId, $this->request->post());
        if ($result === false) {
            return $this->fail(PlayVideoLogic::getError() ?: '提交失败');
        }
        return $this->success('提交成功', $result);
    }

    /**
     * 任务详情（生成中带惰性同步）
     */
    public function detail(): Json
    {
        $sn = trim((string)$this->request->post('sn', ''));
        if ($sn === '') {
            return $this->fail('sn 不能为空');
        }
        $result = PlayVideoLogic::detail($this->userId, $sn);
        if ($result === false) {
            return $this->fail(PlayVideoLogic::getError() ?: '查询失败');
        }
        return $this->success('', $result);
    }

    /**
     * 我的任务列表
     */
    public function lists(): Json
    {
        return $this->success('', PlayVideoLogic::lists($this->userId, $this->request->post()));
    }

    /**
     * 模板墙（外部模板系统对接中，暂返回空列表占位）
     */
    public function templates(): Json
    {
        $play = trim((string)$this->request->post('play', ''));
        return $this->success('', PlayVideoLogic::templates($this->userId, $play));
    }

    /**
     * 一键替换台词：按人设 AI 改写（DeepSeek，按模型用量计费）
     */
    public function rewriteLines(): Json
    {
        try {
            $personaId = (int)$this->request->post('persona_id', 0);
            $lines = (array)$this->request->post('lines', []);
            $templateName = trim((string)$this->request->post('template_name', ''));
            if ($personaId <= 0) {
                return $this->fail('请选择人设');
            }
            $result = \app\common\service\playVideo\PlayVideoRewriteService::rewriteLines(
                $this->userId, $personaId, $lines, $templateName
            );
            return $this->success('已按人设替换台词', ['lines' => $result]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage() ?: 'AI 改写失败');
        }
    }

    /**
     * 上传正脸照/背景图（私有桶，返回 face_file_id + 10 分钟预览签名 URL）
     */
    public function uploadFace(): Json
    {
        try {
            $file = $this->request->file('file');
            if ($file === null) {
                return $this->fail('请选择图片');
            }
            $result = PlayVideoFaceService::upload($this->userId, $file);
            return $this->success('上传成功', $result);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage() ?: '上传失败');
        }
    }

    /**
     * 中台回调（免登录，token 鉴权 + Redis 锁 + 终态幂等在 Logic 内）。
     * 返回体语义对齐闪剪 notify：终态/处理中一律 ack，处理失败 fail 让中台按退避重试。
     */
    public function notify(): Json
    {
        $params = $this->request->param();
        Log::channel('playvideo')->write('接收爆款玩法回调 ' . json_encode(CallbackTokenService::maskForLog($params), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (!CallbackTokenService::verify((string)$this->request->get('token', ''))) {
            Log::channel('playvideo')->warning('play_video notify token 校验失败');
            return $this->fail('unauthorized');
        }
        // 令牌只用于鉴权，不能跟着 $params 流进业务层落库或写日志
        unset($params['token']);
        try {
            if (PlayVideoLogic::notify($params)) {
                return $this->success('ok');
            }
            return $this->fail(PlayVideoLogic::getError() ?: 'fail');
        } catch (\Throwable $e) {
            Log::channel('playvideo')->error('play_video notify 异常: ' . $e->getMessage());
            return $this->fail('fail');
        }
    }
}
