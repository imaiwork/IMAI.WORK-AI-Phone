<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\api\logic\auto\DeviceLogic;
use app\common\enum\DeviceEnum;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\wechat\AiWechatCircleTask;
use app\common\service\FileService;
use think\facade\Db;

/**
 * 发布失败重新发送（社媒发布 / 朋友圈发布）
 * 校验对齐 DeviceLogic::checkOpt，下发对齐 DeviceLogic::opt
 */
class PublishResendLogic extends ApiLogic
{
    public const VIDEO_SOURCE_GENERATED = 'generated';
    public const VIDEO_SOURCE_UPLOAD = 'upload';

    public const PUBLISH_KIND_CONTENT = 'content';
    public const PUBLISH_KIND_CIRCLE = 'circle';

    public const RESEND_TIP = '当前有任务正在执行，发送将会中断任务，直至下一任务才会重新恢复';
    public const NO_GENERATED_VIDEO_MSG = '暂无生成好的视频';
    public const DEVICE_OFFLINE_MSG = '设备当前不在线，请先启动设备后再重新发送';
    private const INTERRUPT_REMARK = '重新发送视频，当前任务已中断';

    /**
     * 校验重发前置条件：先校验失败任务，再走 DeviceLogic::checkOpt（账号/payload）
     */
    public static function checkPublishResend(array $params): bool
    {
        try {
            $context = self::loadFailedPublishContext($params);
            $task = $context['task'];
            $optParams = self::buildOptParamsFromTask($task);
            self::assertAccountExists((string)$task->device_code, (int)$optParams['account_type']);

            $generated = self::findAvailableGeneratedVideo(
                (int)$task->persona_id,
                (string)$task->device_code
            );
            $copy = self::extractCopywritingFromContext($context);
            $accountType = (int)$optParams['account_type'];
            $platform = (int)$context['platform'];
            $source = (int)$optParams['source'];
            $publishKind = (string)$context['kind'];
            $payload = self::buildFailedTaskOptPayload($context, $copy);
            $deviceOnline = self::isDeviceOnlineSafe((string)$task->device_code);

            self::$returnData = [
                'tip' => self::RESEND_TIP,
                'can_resend' => true,
                'device_online' => $deviceOnline,
                'device_offline_reason' => $deviceOnline ? '' : self::DEVICE_OFFLINE_MSG,
                'device_running' => self::isDeviceRunning((string)$task->device_code),
                'is_demo_data' => 0,
                'publish_kind' => $publishKind,
                'publish_kind_desc' => $publishKind === self::PUBLISH_KIND_CIRCLE ? '朋友圈发布' : '社媒发布',
                'task_id' => (int)$task->id,
                'detail_id' => (int)$context['detail_id'],
                'persona_id' => (int)($task->persona_id ?? 0),
                'device_code' => (string)$task->device_code,
                'source' => $source,
                'source_desc' => (string)DeviceEnum::getTaskSceneDesc($source),
                'account_type' => $accountType,
                'account_type_desc' => (string)DeviceEnum::getAccountTypeDesc($accountType),
                'platform' => $platform,
                'platform_desc' => (string)DeviceEnum::getAccountTypeDesc($platform),
                'start_time' => $optParams['start_time'],
                'end_time' => $optParams['end_time'],
                'material_title' => $copy['material_title'],
                'material_subtitle' => $copy['material_subtitle'],
                'material_tag' => $copy['material_tag'],
                'poi' => $copy['poi'],
                'has_generated_video' => $generated !== null,
                'generated_disabled_reason' => $generated === null ? self::NO_GENERATED_VIDEO_MSG : '',
                'generated_video' => $generated === null ? null : [
                    'video_task_id' => (int)$generated->id,
                    'video_url' => FileService::getFileUrl((string)$generated->video_result_url),
                    'pic' => FileService::getFileUrl((string)($generated->pic ?? '')),
                ],
                'data' => $payload,
            ];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 确认后立即重新发送：写回发布任务后走 DeviceLogic::opt 下发
     */
    public static function publishResend(array $params): bool
    {
        $claimed = false;
        $context = null;
        $task = null;
        $interruptedTaskIds = [];

        try {
            $context = self::loadFailedPublishContext($params);
            $task = $context['task'];
            $source = (string)($params['video_source'] ?? '');
            [$videoUrl, $videoTaskId, $pic] = self::resolveResendVideo($source, $params, $task);
            $copy = self::resolveResendCopywriting($source, $params, $context);
            $optParams = self::buildOptParamsFromTask($task);
            self::assertAccountExists((string)$task->device_code, (int)$optParams['account_type']);
            // 指令经 Channel 实时推送，离线设备收不到且不补发，任务会挂在执行中直到超时，下发前拦截
            if (!self::isDeviceOnlineSafe((string)$task->device_code)) {
                throw new \Exception(self::DEVICE_OFFLINE_MSG);
            }
            $publishTime = date('Y-m-d H:i:s');

            Db::startTrans();
            try {
                $interruptedTaskIds = self::interruptRunningTasks((string)$task->device_code, (int)$task->id);

                if ($context['kind'] === self::PUBLISH_KIND_CONTENT) {
                    self::claimContentDetail($context['detail'], $videoUrl, $videoTaskId, $pic, $copy, $publishTime, $source);
                } else {
                    self::claimCircleTask($context['circle'], $videoUrl, $videoTaskId, $copy, $publishTime, $source);
                }
                $claimed = true;

                SvDeviceTask::where('id', $task->id)
                    ->where('user_id', self::$uid)
                    ->update([
                        'status' => DeviceEnum::TASK_STATUS_RUNNING,
                        'remark' => '',
                        'update_time' => time(),
                    ]);

                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                throw $e;
            }

            $payload = self::buildFailedTaskOptPayload($context, $copy, [$videoUrl], $publishTime);
            DeviceLogic::publishOptPayload((string)$task->device_code, $payload);

            self::$returnData = [
                'task_id' => (int)$task->id,
                'detail_id' => (int)$context['detail_id'],
                'device_code' => (string)$task->device_code,
                'publish_kind' => $context['kind'],
                'video_source' => $source,
                'material_url' => $videoUrl,
                'material_title' => $copy['material_title'],
                'material_subtitle' => $copy['material_subtitle'],
                'material_tag' => $copy['material_tag'],
                'poi' => $copy['poi'],
                'publish_time' => $publishTime,
                'is_demo_data' => 0,
                'data' => $payload,
            ];
            return true;
        } catch (\Throwable $e) {
            if ($claimed && $context !== null && $task !== null) {
                self::rollbackClaimedResend($context, $task, $e->getMessage(), $interruptedTaskIds);
            }
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function hasGeneratedVideo(int $personaId, string $deviceCode = ''): bool
    {
        return self::findAvailableGeneratedVideo($personaId, $deviceCode) !== null;
    }

    /**
     * 在线检查是提示/拦截性质：redis 故障时按在线放行（无法判定不应堵死重发），仅明确 offline 才拦截
     */
    private static function isDeviceOnlineSafe(string $deviceCode): bool
    {
        try {
            return DeviceLogic::isDeviceOnline($deviceCode);
        } catch (\Throwable $e) {
            \think\facade\Log::write('重发在线检查失败，按在线放行：' . $e->getMessage(), 'warning');
            return true;
        }
    }

    public static function isDeviceRunning(string $deviceCode): bool
    {
        if ($deviceCode === '') {
            return false;
        }

        return SvDeviceTask::where('user_id', self::$uid)
            ->where('device_code', $deviceCode)
            ->where('status', DeviceEnum::TASK_STATUS_RUNNING)
            ->whereNull('delete_time')
            ->count() > 0;
    }

    /**
     * @return array{
     *   kind: string,
     *   task: SvDeviceTask,
     *   detail:?SvPublishSettingDetail,
     *   circle:?AiWechatCircleTask,
     *   detail_id:int,
     *   platform:int
     * }
     */
    private static function loadFailedPublishContext(array $params): array
    {
        $taskId = (int)($params['task_id'] ?? 0);
        $detailId = (int)($params['detail_id'] ?? 0);
        if ($taskId <= 0 && $detailId <= 0) {
            throw new \Exception('请传入任务ID或发布明细ID');
        }

        // 1) 社媒内容发布
        $contentTask = self::findContentPublishTask($taskId, $detailId);
        if (!$contentTask->isEmpty()) {
            $detail = SvPublishSettingDetail::where('user_id', self::$uid)
                ->where('id', $detailId > 0 ? $detailId : (int)$contentTask->sub_data_id)
                ->whereNull('delete_time')
                ->findOrEmpty();
            if ($detail->isEmpty()) {
                throw new \Exception('发布明细不存在');
            }
            if ((int)$detail->material_type !== 1) {
                throw new \Exception('仅支持短视频发布失败重发');
            }
            $failed = (int)$detail->status === 2
                || (int)$detail->status === 5
                || (int)$contentTask->status === DeviceEnum::TASK_STATUS_FAILED;
            if (!$failed) {
                throw new \Exception('仅失败的发布任务可重新发送');
            }

            return [
                'kind' => self::PUBLISH_KIND_CONTENT,
                'task' => $contentTask,
                'detail' => $detail,
                'circle' => null,
                'detail_id' => (int)$detail->id,
                'platform' => (int)($detail->platform ?: $contentTask->account_type),
            ];
        }

        // 2) 朋友圈发布
        $circleTask = self::findCirclePublishTask($taskId, $detailId);
        if ($circleTask->isEmpty()) {
            throw new \Exception('发布任务不存在');
        }

        $circle = AiWechatCircleTask::where('user_id', self::$uid)
            ->where('id', $detailId > 0 ? $detailId : (int)$circleTask->sub_data_id)
            ->findOrEmpty();
        if ($circle->isEmpty()) {
            throw new \Exception('朋友圈发布任务不存在');
        }

        // attachment_type: 1图文 2/3视频
        $attachmentType = (int)($circle->attachment_type ?? 0);
        if (!in_array($attachmentType, [2, 3], true)) {
            throw new \Exception('仅支持短视频朋友圈发布失败重发');
        }

        $failed = (int)$circle->send_status === 3
            || (int)$circleTask->status === DeviceEnum::TASK_STATUS_FAILED;
        if (!$failed) {
            throw new \Exception('仅失败的发布任务可重新发送');
        }

        return [
            'kind' => self::PUBLISH_KIND_CIRCLE,
            'task' => $circleTask,
            'detail' => null,
            'circle' => $circle,
            'detail_id' => (int)$circle->id,
            'platform' => DeviceEnum::PUBLISH_PLATFORM_WX,
        ];
    }

    private static function findContentPublishTask(int $taskId, int $detailId): SvDeviceTask
    {
        $query = SvDeviceTask::where('user_id', self::$uid)
            ->whereNull('delete_time')
            ->whereIn('task_type', [DeviceEnum::TASK_TYPE_PUBLISH, DeviceEnum::AUTO_TYPE_PUBLISH])
            ->where('source', DeviceEnum::TASK_SOURCE_PUBLISH)
            ->where('task_scene', DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH);

        if ($taskId > 0) {
            $query->where('id', $taskId);
        } else {
            $query->where('sub_data_id', $detailId);
        }

        return $query->order('id', 'desc')->findOrEmpty();
    }

    private static function findCirclePublishTask(int $taskId, int $detailId): SvDeviceTask
    {
        $query = SvDeviceTask::where('user_id', self::$uid)
            ->whereNull('delete_time')
            ->where('task_type', DeviceEnum::TASK_TYPE_WECHAT_CIRCLE)
            ->where('source', DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH)
            ->where('task_scene', DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH);

        if ($taskId > 0) {
            $query->where('id', $taskId);
        } else {
            $query->where('sub_data_id', $detailId);
        }

        return $query->order('id', 'desc')->findOrEmpty();
    }

  
    /**
     * 校验设备账号存在（对齐 checkOpt 的账号校验，不按时间窗另找任务）
     */
    private static function assertAccountExists(string $deviceCode, int $accountType): void
    {
        $account = SvAccount::where('user_id', self::$uid)
            ->where('device_code', $deviceCode)
            ->where('type', $accountType)
            ->findOrEmpty();
        if ($account->isEmpty()) {
            throw new \Exception('账号不存在');
        }
    }

    /**
     * 按本次失败任务组装 checkOpt 同结构 payload，taskId/material_id 必须是当前任务
     */
    private static function buildFailedTaskOptPayload(
        array $context,
        array $copy,
        ?array $videoList = null,
        ?string $publishTime = null
    ): array {
        $task = $context['task'];
        $platform = (int)$context['platform'];
        $accountType = (int)$task->account_type;
        if ($context['kind'] === self::PUBLISH_KIND_CIRCLE) {
            $accountType = DeviceEnum::ACCOUNT_TYPE_SPH;
        }

        if ($videoList === null) {
            $videoList = self::extractMediaListFromContext($context);
        }
        $videoList = array_values(array_filter(array_map(static function ($url) {
            $url = trim((string)$url);
            return $url === '' ? '' : FileService::getFileUrl($url);
        }, $videoList)));

        if ($publishTime === null || $publishTime === '') {
            $publishTime = self::extractPublishTimeFromContext($context);
        }

        $content = [
            'publish_platform' => $context['kind'] === self::PUBLISH_KIND_CIRCLE ? DeviceEnum::PUBLISH_PLATFORM_WX : $platform,
            'material_id' => (int)$context['detail_id'],
            'auto_type' => 1,
            'title' => $copy['material_title'],
            'type' => 1,
            'list' => $videoList,
            'isLocation' => $copy['poi'] !== '' ? 1 : 0,
            'location' => $copy['poi'],
            'isScheduledTime' => true,
            'scheduledTime' => $publishTime,
            'taskId' => (int)$task->id,
            'body' => $copy['material_subtitle'],
            'tag' => $copy['material_tag'],
            'isSend' => 0,
            'isDemoData' => 0,
        ];
        if ($context['kind'] === self::PUBLISH_KIND_CIRCLE) {
            $content['comment'] = '';
        }

        return [
            'appType' => $accountType,
            'messageId' => '0',
            'type' => (int)$task->task_scene,
            'deviceId' => (string)$task->device_code,
            'appVersion' => DeviceEnum::APP_VERSION,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    private static function extractMediaListFromContext(array $context): array
    {
        if ($context['kind'] === self::PUBLISH_KIND_CONTENT) {
            $raw = $context['detail']->material_url ?? '';
            if (is_array($raw)) {
                return $raw;
            }
            return array_values(array_filter(array_map('trim', explode(',', (string)$raw))));
        }

        $raw = $context['circle']->attachment_content ?? [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : array_filter([$raw]);
        }
        return is_array($raw) ? $raw : [];
    }

    private static function extractPublishTimeFromContext(array $context): string
    {
        if ($context['kind'] === self::PUBLISH_KIND_CONTENT) {
            $time = (string)($context['detail']->publish_time ?? '');
            if ($time !== '') {
                return $time;
            }
        } else {
            $time = (string)($context['circle']->send_time ?? '');
            if ($time !== '') {
                return $time;
            }
        }

        $start = (int)($context['task']->start_time ?? 0);
        return $start > 0 ? date('Y-m-d H:i:s', $start) : date('Y-m-d H:i:s');
    }

    /**
     * 组装 checkOpt / opt 入参（时间窗必须用原任务档期，否则找不到任务）
     */
    private static function buildOptParamsFromTask(SvDeviceTask $task): array
    {
        $accountType = (int)$task->account_type;
        if (!in_array($accountType, [
            DeviceEnum::ACCOUNT_TYPE_SPH,
            DeviceEnum::ACCOUNT_TYPE_XHS,
            DeviceEnum::ACCOUNT_TYPE_DY,
            DeviceEnum::ACCOUNT_TYPE_KS,
        ], true)) {
            // 朋友圈设备任务固定 account_type=1
            $accountType = DeviceEnum::ACCOUNT_TYPE_SPH;
        }

        $source = (int)$task->task_scene;
        if (!in_array($source, [
            DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
            DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH,
        ], true)) {
            throw new \Exception('不支持的发布任务类型');
        }

        $start = (int)$task->start_time;
        $end = (int)$task->end_time;
        if ($start <= 0 || $end <= 0) {
            throw new \Exception('发布任务时间配置异常');
        }

        return [
            'device_code' => (string)$task->device_code,
            'account_type' => $accountType,
            'source' => $source,
            'start_time' => date('H:i', $start),
            'end_time' => date('H:i', $end),
        ];
    }

    private static function extractCopywritingFromContext(array $context): array
    {
        if ($context['kind'] === self::PUBLISH_KIND_CONTENT) {
            $detail = $context['detail'];
            return [
                'material_title' => (string)($detail->material_title ?? ''),
                'material_subtitle' => (string)($detail->material_subtitle ?? ''),
                'material_tag' => (string)($detail->material_tag ?? ''),
                'poi' => (string)($detail->poi ?? ''),
            ];
        }

        $circle = $context['circle'];
        $content = (string)($circle->content ?? '');
        return [
            'material_title' => '',
            'material_subtitle' => $content,
            'material_tag' => '',
            'poi' => '',
        ];
    }

    /**
     * @return array{material_title:string,material_subtitle:string,material_tag:string,poi:string}
     */
    private static function resolveResendCopywriting(string $source, array $params, array $context): array
    {
        if ($source === self::VIDEO_SOURCE_GENERATED) {
            return self::extractCopywritingFromContext($context);
        }

        if ($source === self::VIDEO_SOURCE_UPLOAD) {
            if (!array_key_exists('material_title', $params)
                || !array_key_exists('material_subtitle', $params)
                || !array_key_exists('material_tag', $params)
                || !array_key_exists('poi', $params)
            ) {
                throw new \Exception('换视频发布需填写标题、文案、话题、位置');
            }

            $title = trim((string)$params['material_title']);
            $subtitle = trim((string)$params['material_subtitle']);
            $tag = trim((string)$params['material_tag']);
            $poi = trim((string)$params['poi']);

            if ($title === '' && $subtitle === '') {
                throw new \Exception('请填写标题或文案');
            }

            // 朋友圈无正文作为 content；标题/话题/位置仍落库到返回值，位置不写入朋友圈
            return [
                'material_title' => $title,
                'material_subtitle' => $subtitle,
                'material_tag' => $tag,
                'poi' => $poi,
            ];
        }

        throw new \Exception('视频来源不正确');
    }

    /**
     * @return array{0:string,1:int,2:string}
     */
    private static function resolveResendVideo(string $source, array $params, SvDeviceTask $task): array
    {
        if ($source === self::VIDEO_SOURCE_GENERATED) {
            $videoTaskId = (int)($params['video_task_id'] ?? 0);
            if ($videoTaskId > 0) {
                $generated = ShanjianVideoTask::where('user_id', self::$uid)
                    ->where('id', $videoTaskId)
                    ->where('persona_id', (int)$task->persona_id)
                    ->where('status', ShanjianVideoTask::STATUS_SUCCESS)
                    ->where('is_final', 1)
                    ->where('video_result_url', '<>', '')
                    ->whereNull('delete_time')
                    ->findOrEmpty();
                if ($generated->isEmpty()) {
                    throw new \Exception(self::NO_GENERATED_VIDEO_MSG);
                }
            } else {
                $generated = self::findAvailableGeneratedVideo((int)$task->persona_id, (string)$task->device_code);
                if ($generated === null) {
                    throw new \Exception(self::NO_GENERATED_VIDEO_MSG);
                }
            }

            return [
                FileService::getFileUrl((string)$generated->video_result_url),
                (int)$generated->id,
                (string)($generated->pic ?? ''),
            ];
        }

        if ($source === self::VIDEO_SOURCE_UPLOAD) {
            $videoUrl = trim((string)($params['video_url'] ?? ''));
            if ($videoUrl === '') {
                throw new \Exception('请上传视频');
            }
            return [FileService::getFileUrl($videoUrl), 0, trim((string)($params['pic'] ?? ''))];
        }

        throw new \Exception('视频来源不正确');
    }

    private static function claimContentDetail(
        SvPublishSettingDetail $detail,
        string $videoUrl,
        int $videoTaskId,
        string $pic,
        array $copy,
        string $publishTime,
        string $source
    ): void {
        $updateDetail = [
            'material_url' => $videoUrl,
            'material_title' => $copy['material_title'],
            'material_subtitle' => $copy['material_subtitle'],
            'material_tag' => $copy['material_tag'],
            'poi' => $copy['poi'],
            'material_type' => 1,
            'publish_time' => $publishTime,
            'status' => 3,
            'remark' => '',
            'exec_time' => time(),
            'update_time' => time(),
        ];
        if ($pic !== '') {
            $updateDetail['pic'] = $pic;
        }
        if ($videoTaskId > 0) {
            $updateDetail['video_task_id'] = $videoTaskId;
        } elseif ($source === self::VIDEO_SOURCE_UPLOAD) {
            $updateDetail['video_task_id'] = 0;
        }

        $affected = SvPublishSettingDetail::where('id', $detail->id)
            ->where('user_id', self::$uid)
            ->whereIn('status', [0, 2, 5])
            ->update($updateDetail);
        if ($affected <= 0) {
            throw new \Exception('发布明细状态已变更，请刷新后重试');
        }
    }

    private static function claimCircleTask(
        AiWechatCircleTask $circle,
        string $videoUrl,
        int $videoTaskId,
        array $copy,
        string $publishTime,
        string $source
    ): void {
        // 朋友圈正文优先用文案，其次标题
        $content = $copy['material_subtitle'] !== '' ? $copy['material_subtitle'] : $copy['material_title'];
        // attachment_type 保留原值（2短视频/3长视频，前置校验已限定 [2,3]），避免改写视频子类型语义
        $update = [
            'content' => $content,
            'attachment_content' => [$videoUrl],
            'send_time' => $publishTime,
            'send_status' => 1,
            'update_time' => time(),
        ];
        if ($videoTaskId > 0) {
            $update['shanjian_video_task_id'] = $videoTaskId;
        } elseif ($source === self::VIDEO_SOURCE_UPLOAD) {
            $update['shanjian_video_task_id'] = 0;
        }

        $affected = AiWechatCircleTask::where('id', $circle->id)
            ->where('user_id', self::$uid)
            ->whereIn('send_status', [0, 1, 3])
            ->update($update);
        if ($affected <= 0) {
            throw new \Exception('朋友圈任务状态已变更，请刷新后重试');
        }
    }

    private static function rollbackClaimedResend(
        array $context,
        SvDeviceTask $task,
        string $message,
        array $interruptedTaskIds = []
    ): void {
        try {
            if ($context['kind'] === self::PUBLISH_KIND_CONTENT && $context['detail'] !== null) {
                $originalStatus = (int)$context['detail']->status;
                $rollbackStatus = in_array($originalStatus, [0, 2, 5], true) ? $originalStatus : 5;
                SvPublishSettingDetail::where('id', $context['detail']->id)
                    ->where('status', 3)
                    ->update([
                        'status' => $rollbackStatus,
                        'remark' => '重新发送下发失败：' . $message,
                        'update_time' => time(),
                    ]);
            }
            if ($context['kind'] === self::PUBLISH_KIND_CIRCLE && $context['circle'] !== null) {
                AiWechatCircleTask::where('id', $context['circle']->id)
                    ->where('send_status', 1)
                    ->update([
                        'send_status' => 3,
                        'update_time' => time(),
                    ]);
            }
            SvDeviceTask::where('id', $task->id)->update([
                'status' => DeviceEnum::TASK_STATUS_FAILED,
                'remark' => '重新发送下发失败：' . $message,
                'update_time' => time(),
            ]);
            // 下发失败说明中断指令未到达设备，被标记中断的任务实际仍在执行，恢复为执行中
            if ($interruptedTaskIds) {
                SvDeviceTask::whereIn('id', $interruptedTaskIds)
                    ->where('status', DeviceEnum::TASK_STATUS_INTERRUPTED)
                    ->where('remark', self::INTERRUPT_REMARK)
                    ->update([
                        'status' => DeviceEnum::TASK_STATUS_RUNNING,
                        'remark' => '',
                        'update_time' => time(),
                    ]);
            }
        } catch (\Throwable $ignore) {
        }
    }

    private static function findAvailableGeneratedVideo(int $personaId, string $deviceCode = ''): ?ShanjianVideoTask
    {
        if ($personaId <= 0) {
            return null;
        }

        $query = ShanjianVideoTask::where('user_id', self::$uid)
            ->where('persona_id', $personaId)
            ->where('status', ShanjianVideoTask::STATUS_SUCCESS)
            ->where('is_final', 1)
            ->where('video_result_url', '<>', '')
            ->whereNull('delete_time');

        if ($deviceCode !== '') {
            $sameDevice = (clone $query)
                ->where('device_code', $deviceCode)
                ->order('id', 'desc')
                ->findOrEmpty();
            if (!$sameDevice->isEmpty()) {
                return $sameDevice;
            }
        }

        $any = $query->order('id', 'desc')->findOrEmpty();
        return $any->isEmpty() ? null : $any;
    }

    /**
     * 中断同设备其他执行中任务，返回被中断的任务ID（下发失败回滚时恢复为执行中）
     * @return int[]
     */
    private static function interruptRunningTasks(string $deviceCode, int $excludeTaskId): array
    {
        if ($deviceCode === '') {
            return [];
        }

        $ids = SvDeviceTask::where('user_id', self::$uid)
            ->where('device_code', $deviceCode)
            ->where('status', DeviceEnum::TASK_STATUS_RUNNING)
            ->where('id', '<>', $excludeTaskId)
            ->whereNull('delete_time')
            ->column('id');
        if (!$ids) {
            return [];
        }

        SvDeviceTask::whereIn('id', $ids)
            ->where('status', DeviceEnum::TASK_STATUS_RUNNING)
            ->update([
                'status' => DeviceEnum::TASK_STATUS_INTERRUPTED,
                'remark' => self::INTERRUPT_REMARK,
                'update_time' => time(),
            ]);

        return array_map('intval', $ids);
    }
}
