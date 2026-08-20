<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Routing;

use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\RpaSocketService;
use app\common\workerman\rpa\WorkerEnum;

class HandlerRegistry
{
    private array $handlers;

    public function __construct()
    {
        $this->handlers = [
            'ping' => \app\common\workerman\rpa\handlers\HeartBeatHandler::class,
            WorkerEnum::RPA_DEVICE_HEARTBEAT => \app\common\workerman\rpa\handlers\DeviceHeartBeatHandler::class,
            WorkerEnum::RPA_DEVICE_INFO => \app\common\workerman\rpa\handlers\DeviceHandler::class,
            WorkerEnum::RPA_USER_INFO => \app\common\workerman\rpa\handlers\xhs\UserHandler::class,
            WorkerEnum::RPA_PRIVATE_MESSAGE => \app\common\workerman\rpa\handlers\xhs\PrivateMessageHandler::class,
            WorkerEnum::RPA_PUBLISHED_POST_STATUS => \app\common\workerman\rpa\handlers\xhs\InteractiveMessageHandler::class,
            WorkerEnum::RPA_SEND_CARD_STATUS => \app\common\workerman\rpa\handlers\xhs\CardHandler::class,
            WorkerEnum::RPA_CARD_INFO => \app\common\workerman\rpa\handlers\xhs\CardHandler::class,
            WorkerEnum::RPA_NEW_PRIVATE_MESSAGE => \app\common\workerman\rpa\handlers\xhs\MessageHandler::class,
            WorkerEnum::RPA_TASK_EXEC_STATUS => \app\common\workerman\rpa\handlers\TaskHandler::class,
            WorkerEnum::RPA_DEVICE_INIT => \app\common\workerman\rpa\handlers\CompletedHandler::class,
            WorkerEnum::RPA_MSG_REPLY_STATUS => \app\common\workerman\rpa\handlers\MsgReplyHandler::class,
            WorkerEnum::RPA_MSG_REPLY_COMPLETED => \app\common\workerman\rpa\handlers\MsgReplyHandler::class,
            WorkerEnum::RPA_MEDIA_STATUS => \app\common\workerman\rpa\handlers\MediaStatusHandler::class,
            WorkerEnum::WEB_SOCKET_STATUS_TEXT => \app\common\workerman\rpa\handlers\WebWorkerHandler::class,
            WorkerEnum::WEB_BIND_DEVICE_TEXT => \app\common\workerman\rpa\handlers\DeviceHandler::class,
            WorkerEnum::WEB_GET_USER_INFO_TEXT => \app\common\workerman\rpa\handlers\xhs\UserHandler::class,
            WorkerEnum::WEB_PRIVATE_MESSAGE_LIST_TEXT => \app\common\workerman\rpa\handlers\xhs\PrivateMessageHandler::class,
            WorkerEnum::WEB_SEND_PRIVATE_MESSAGE_TEXT => \app\common\workerman\rpa\handlers\xhs\MessageHandler::class,
            WorkerEnum::WEB_CARDS_TEXT => \app\common\workerman\rpa\handlers\xhs\CardHandler::class,
            WorkerEnum::WEB_POST_STATUS_LIST_TEXT => \app\common\workerman\rpa\handlers\xhs\InteractiveMessageHandler::class,
            WorkerEnum::WEB_INIT_CHECK_TEXT => \app\common\workerman\rpa\handlers\CheckInitHandler::class,
            WorkerEnum::WEB_SEND_CARD_TEXT => \app\common\workerman\rpa\handlers\xhs\CardHandler::class,
            WorkerEnum::RPA_SPH_TASK_SEND => \app\common\workerman\rpa\handlers\sph\TaskSendHandler::class,
            WorkerEnum::RPA_SPH_TASK_RECORD_SAVE => \app\common\workerman\rpa\handlers\sph\TaskRecordSaveHandler::class,
            WorkerEnum::RPA_SPH_TASK_PAUSE => \app\common\workerman\rpa\handlers\sph\TaskPauseHandler::class,
            WorkerEnum::RPA_SPH_TASK_RESUME => \app\common\workerman\rpa\handlers\sph\TaskRecoveryHandler::class,
            WorkerEnum::RPA_SPH_TASK_CANCEL => \app\common\workerman\rpa\handlers\sph\TaskDeleteHandler::class,
            WorkerEnum::RPA_SPH_TASK_COMPLETED => \app\common\workerman\rpa\handlers\sph\TaskCompletedHandler::class,
            WorkerEnum::RPA_SPH_TASK_RECEIVEW => \app\common\workerman\rpa\handlers\sph\TaskReceivedHandler::class,
            WorkerEnum::RPA_GET_ACCOUNT_APP_SEND => \app\common\workerman\rpa\handlers\device\AppSendHandler::class,
            WorkerEnum::RPA_GET_ACCOUNT_APP_EXEC => \app\common\workerman\rpa\handlers\device\AppExecHandler::class,
            WorkerEnum::RPA_GET_ACCOUNT_APP_OPEN => \app\common\workerman\rpa\handlers\device\AppOpenHandler::class,
            WorkerEnum::RPA_GET_ACCOUNT_APP_PERSONAL_CENTER => \app\common\workerman\rpa\handlers\device\AppPersonalCenterHandler::class,
            WorkerEnum::RPA_GET_ACCOUNT_APP_INFO => \app\common\workerman\rpa\handlers\device\AppInfoHandler::class,
            WorkerEnum::RPA_GET_ACCOUNT_APP_DATA_SEND => \app\common\workerman\rpa\handlers\device\AppDataSendHandler::class,
            WorkerEnum::RPA_GET_ACCOUNT_APP_COMPLETED => \app\common\workerman\rpa\handlers\device\AppCompletedHandler::class,
            WorkerEnum::RPA_COMMENT_TO_COMMENT_CHECK => \app\common\workerman\rpa\handlers\touch\CommentToCommentHandler::class,
            WorkerEnum::RPA_COMMENT_TO_MSG_CHECK => \app\common\workerman\rpa\handlers\touch\CommentToMsgHandler::class,
            WorkerEnum::RPA_COMMENT_TO_MARK_CLUE_CHECK => \app\common\workerman\rpa\handlers\touch\CommentToMarkClueHandler::class,
            WorkerEnum::RPA_COMMENT_TO_TOUCH_POST => \app\common\workerman\rpa\handlers\touch\CommentToTouchPostHandler::class,
            WorkerEnum::RPA_TAKE_OVER_TASK_RESULT_SAVE => \app\common\workerman\rpa\handlers\TakeOverTaskResultSaveHandler::class,
            WorkerEnum::RPA_ACTIVE_TASK_RESULT_SAVE => \app\common\workerman\rpa\handlers\ActiveTaskResultSaveHandler::class,
            WorkerEnum::RPA_DEVICE_APP_EXEC => \app\common\workerman\rpa\handlers\DeviceAppStartExecHandler::class,
            WorkerEnum::RPA_GET_WECHAT_DEVICE_CODE => \app\common\workerman\rpa\handlers\GetWechatDeviceCodeHandler::class,
            WorkerEnum::RPA_ADD_WECHAT_TASK_NOTICE => \app\common\workerman\rpa\handlers\wechat\AddWechatTaskNoticeHandler::class,
            WorkerEnum::RPA_WECHAT_CIRCLE_LIKE_COMMENT => \app\common\workerman\rpa\handlers\wechat\CircleLikeCommentHandler::class,
            WorkerEnum::RPA_CREATE_GROUP_NOTICE => \app\common\workerman\rpa\handlers\wechat\CreateGroupNoticeHandler::class,
            WorkerEnum::RPA_CITY_EXPOSURE => \app\common\workerman\rpa\handlers\imai\CityExposureHandler::class,
            WorkerEnum::RPA_CITY_TOUCH => \app\common\workerman\rpa\handlers\imai\CityTouchHandler::class,
            WorkerEnum::RPA_GROUP_BUY_TOUCH => \app\common\workerman\rpa\handlers\imai\GroupBuyTouchHandler::class,
            WorkerEnum::RPA_DEVICE_VIRAL_RITER_TASK => \app\common\workerman\rpa\handlers\device\ViralRewriterHandler::class, #设备爆款复
            WorkerEnum::RPA_SPH_COMMENT_THUMB => \app\common\workerman\rpa\handlers\wechat\SphCommentThumbSaveHandler::class, #评论点赞
            WorkerEnum::RPA_DEVICE_PRECISE_CLUES_TASK => \app\common\workerman\rpa\handlers\touch\PreciseCluesTaskHandler::class, #设备精准获客任务发送
            WorkerEnum::RPA_PHONE_AGENT_REPORT => \app\common\workerman\rpa\handlers\phoneAgent\PhoneAgentReportHandler::class,
            WorkerEnum::RPA_DEVICE_RUNNING_LOG => \app\common\workerman\rpa\handlers\device\RunningLogHandler::class, #设备运行日志
        ];
    }

    public function resolve(int|string $type, RpaSocketService $service): BaseMessageHandler
    {
        $class = $this->handlers[$type] ?? \app\common\workerman\rpa\handlers\DefaultHandler::class;
        return new $class($service);
    }
}
