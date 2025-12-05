<?php

namespace app\api\logic\wechat;

use app\api\logic\ApiLogic;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatContact;
use app\common\model\wechat\AiWechatDevice;
use app\common\model\wechat\AiWechatRobot;
use app\common\model\wechat\AiWechatRobotKeyword;
use app\common\model\wechat\AiWechatReplyStrategy;
use app\common\service\FileService;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\model\ChatPrompt;
use think\facade\Queue;
use app\common\traits\WechatTrait;
/**
 * WechatBaseLogic
 * @desc 微信基础逻辑
 * @author Qasim
 */
class WechatBaseLogic extends ApiLogic
{

    use WechatTrait;
    /**
     * @desc 获取微信账号信息
     * @param string $wechatId
     * @return bool|AiWechat
     */
    protected static function wechatInfo(string $wechatId, bool $check = true): bool|AiWechat
    {
        $wechat = AiWechat::where('wechat_id', $wechatId)->when($check, function ($query)
        {
            $query->where('user_id', self::$uid);
        })->findOrEmpty();
        if ($wechat->isEmpty())
        {
            self::setError('微信账号不存在');
            return false;
        }
        return $wechat;
    }


    /**
     * @desc 获取微信好友信息
     * @param string $wechatId
     * @param string $friendId
     * @return bool|AiWechatContact
     */
    protected static function friendInfo(string $wechatId, string $friendId): bool|AiWechatContact
    {
        $friend = AiWechatContact::where('wechat_id', $wechatId)->where('friend_id', $friendId)->findOrEmpty();
        if ($friend->isEmpty())
        {
            self::setError('微信好友不存在');
            return false;
        }
        return $friend;
    }

    /**
     * @desc 获取设备信息
     * @param string $deviceCode
     * @return bool|AiWechatDevice
     */
    protected static function deviceInfo(string $deviceCode, bool $check = true): bool|AiWechatDevice
    {
        $device = AiWechatDevice::where('device_code', $deviceCode)->when($check, function ($query)
        {
            $query->where('user_id', self::$uid);
        })->findOrEmpty();

        if ($device->isEmpty())
        {
            self::setError('设备不存在');
            return false;
        }
        return $device;
    }


    /**
     * @desc 正则匹配关键词
     * @param AiWechatRobot $robot
     * @param array $request
     * @return bool
     */
    protected static function regularMatchKeyword(AiWechatRobot $robot, array $request)
    {

        $match = false;
        // 获取微信机器人设置的正关键词
        AiWechatRobotKeyword::where('robot_id', $robot->id)->select()->each(function ($item) use ($request, &$match)
        {

            // 模糊匹配
            if ($item->match_type == 0)
            {
                if (str_contains($request['message'], $item->keyword))
                {

                    self::parseMessage($request, $item->reply);
                    $match = true;
                }
            }
            else
            {
                if ((string)$item->keyword === $request['message'])
                {

                    self::parseMessage($request, $item->reply);
                    $match = true;
                }
            }
        });

        return $match;
    }


    /**
     * @desc 解析AI提示词
     * @param array $request
     * @param array $content
     * @return void
     */
    protected static function parseAiPrompt(AiWechatRobot $robot, array $request, array $logs): void
    {

        //检查扣费
        $unit = TokenLogService::checkToken(self::$uid, 'ai_wechat');

        //获取提示词
        $keyword = ChatPrompt::where('id', 12)->value('prompt_text') ?? '';

        if (!$keyword)
        {

            message("提示词不存在");
        }
        $keyword = str_replace(
            ['企业背景', '角色设定', '用户备注', '用户标签', '咨询', '最近对话记录', '用户发送的内容'],
            [$robot->company_background, $robot->description, $request['friend_remark'], "", "", json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $request['message']],
            $keyword
        );
        $task_id = generate_unique_task_id();

        // 检查是否挂载知识库
        $bind = \app\common\model\knowledge\KnowledgeBind::where('data_id', $robot->id)->where('user_id', self::$uid)->where('type', 1)->limit(1)->find();
        $knowledge = [];
        if (!empty($bind)) {
            $knowledge = \app\common\model\knowledge\Knowledge::where('id', $bind['kid'])->limit(1)->find();
            if (empty($knowledge)) {
                message("挂载的知识库不存在");
            }
            $knowledge['task_id'] = $task_id;
        }

        $request = [
            'user_id' => self::$uid,
            'task_id' => $task_id,
            'wechat_id' => $request['wechat_id'],
            'friend_id' => $request['friend_id'],
            'friend_remark' => $request['friend_remark'],
            'device_code' => $request['device_code'],
            'message' => $request['message'],
            'message_id' => $request['message_id'],
            'chat_type' => AccountLogEnum::TOKENS_DEC_AI_WECHAT,
            'now'       => time(),
            'messages' => array_merge([['role' => 'system', 'content' => $keyword]], $logs),
            'knowledge' => $knowledge,
        ];

        // 任务数据
        $data = [
            'wechat_id' => $request['wechat_id'],
            'friend_id' => $request['friend_id'],
            'device_code' => $request['device_code'],
            'task_id' => $request['task_id'],
            'uid' => self::$uid,
            'request' => $request,
        ];

        // 推送到队列
        Queue::push('app\common\Jobs\WechatAIMessageJob@handle', $data);
    }


    /**
     * @desc 解析消息
     * @param array $request
     * @param array $content
     * @return void
     */
    protected static function parseMessage(array $request, array $content)
    {
        foreach ($content as $item)
        {

            $send = true;

            switch ((int)$item['type'])
            {

                case 0: //文本

                    // 推送消息
                    $request['message_type'] = 1;
                    $request['message'] = str_replace('${remark}', $request['friend_remark'], $item['content']);
                    break;

                case 1: //图片

                    // 推送消息
                    $request['message'] = FileService::getFileUrl($item['content']);
                    $request['message_type'] = 2;
                    break;

                default:
                    $send = false;
            }

            if ($send)
            {
                self::send($request);
            }
        }
    }

    /**
     * @desc 正则匹配停止AI回复
     * @param AiWechatRobot $robot
     * @param string $message
     * @return bool
     */
    protected static function regularMatchStopAI(AiWechatReplyStrategy $reply, array $request)
    {

        $stop = false;

        $keywords = explode(';', $reply->stop_keywords);

        // 获取微信机器人设置的正关键词
        foreach ($keywords as $keyword)
        {

            if ((string)$keyword === $request['message'])
            {

                $stop = true;

                break;
            }
        }

        return $stop;
    }

    /**
     * @desc 发送消息
     * @param array $request
     * @return void
     */
    protected static function send(array $request): void
    {
        sleep(5);
        //\app\common\service\ToolsService::Wechat()->push($request);
        $request['opt_type'] = 'send';
        self::wxPush($request);
    }
}
