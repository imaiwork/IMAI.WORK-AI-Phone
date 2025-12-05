<?php

namespace app\api\logic\wechat;

use app\common\model\wechat\AiWechatGreetStrategy;
use app\common\model\wechat\AiWechatReplyStrategy;
use app\common\model\wechat\AiWechatRobot;
use app\common\service\FileService;
use app\common\model\wechat\AiWechatContact;
use app\common\model\wechat\AiWechat;

/**
 * MessageLogic
 * @desc 微信消息
 * @author Qasim
 */
class MessageLogic extends WechatBaseLogic
{

    /**
     * @desc 打招呼
     * @param array $params
     * @return bool
     */
    public static function greetMessage(array $params)
    {

        try {
            // 获取微信账号信息
            $wechat = self::wechatInfo($params['wechat_id']);
            if (is_bool($wechat)) {
                return false;
            }

            // 获取微信好友信息
            $friend = self::friendInfo($params['wechat_id'], $params['friend_id']);
            if (is_bool($friend)) {
                return false;
            }

            if ($friend->open_ai == 0) {
                self::setError('未开启全局AI');
                return false;
            }

            // 获取用户设置
            $greet = AiWechatGreetStrategy::where('user_id', self::$uid)->findOrEmpty();

            if ($greet->isEmpty()) {
                self::setError('请先设置打招呼的配置');
                return false;
            }

            if ($greet->is_enable == 0) {
                self::setError('未开启打招呼配置');
                return false;
            }

            // 给好友发消息
            foreach ($greet->greet_content as $key => $content) {

                if ($key !== 0) {

                    sleep(5);
                }

                $message = [
                    'wechat_id' => $wechat->wechat_id,
                    'friend_id' => $params['friend_id'],
                    'device_code' => $wechat->device_code,
                    'opt_type' => 'greet'
                ];

                switch ($content['type']) {

                    case 0: //文本

                        // 推送消息
                        $message['message'] = str_replace('${remark}', $friend->remark, $content['content']);
                        break;

                    case 1: //图片

                        // 推送消息
                        $message['message'] = FileService::getFileUrl($content['content']);
                        $message['message_type'] = 2;
                        break;

                    default:
                }

                //\app\common\service\ToolsService::Wechat()->push($message);
                self::wxPush($message);
            }

            // 用户设置打招呼，设置接管模式
            $friend->takeover_mode = $greet->greet_after_ai_enable;
            $friend->save();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 发送消息
     * @param array $params
     * @return bool
     */
    public static function sendMessage(array $params)
    {

        try {
            // 获取微信账号信息
            $wechat = AiWechat::alias('w')
                ->join('ai_wechat_setting s', 's.wechat_id = w.wechat_id')
                ->where('w.wechat_id', $params['wechat_id'])
                ->where('w.user_id', self::$uid)
                ->findOrEmpty();

            if ($wechat->isEmpty()) {
                self::setError('微信账号不存在');
                return false;
            }

            // 获取微信好友信息
            $friend = self::friendInfo($params['wechat_id'], $params['friend_id']);
            if (is_bool($friend)) {
                return false;
            }

            // 未开启全局AI
            if ($friend->open_ai == 0) {
                self::setError('未开启全局AI');
                return false;
            }

            // 获取用户回复设置
            $reply = AiWechatReplyStrategy::where('user_id', self::$uid)->findOrEmpty();
            if ($reply->isEmpty()) {
                self::setError('请先设置回复的配置');
                return false;
            }

            // 机器人
            $robot = AiWechatRobot::where('id', $wechat->robot_id)->findOrEmpty();
            if ($robot->isEmpty()) {
                self::setError('机器人不存在');
                return false;
            }

            // 组装请求参数
            $request = [
                'wechat_id' => $wechat->wechat_id,
                'friend_id' => $params['friend_id'],
                'friend_remark' => $friend->remark,
                'device_code' => $wechat->device_code,
                'message' => $params['message'],
                'message_id' => $params['message_id'],
                'message_type' => 1,
            ];

            //step 1. 正则匹配停止AI回复
            $stop = self::regularMatchStopAI($reply, $request);
            if ($stop) {

                // 关闭AI接管
                AiWechatContact::where('wechat_id', $wechat->wechat_id)->where('friend_id', $params['friend_id'])->update(['takeover_mode' => 0]);
                return true;
            }

            // step 2. 正则匹配关键词
            $match = self::regularMatchKeyword($robot, $request);

            if ($match) {
                return true;
            }

            // step 3. 发送AI消息
            self::parseAiPrompt($robot, $request, $params['message_logs']);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取在线状态
     * @param string $wechat_id
     * @param string $device_code
     * @param int $type 0: 微信 1: 设备
     * @return bool
     */
    public static function getOnlineStatus(string $wechat_id, string $device_code, int $type = 0)
    {

        try {
            // 获取微信账号信息 
            $wechat = self::wechatInfo($wechat_id);
            if (is_bool($wechat)) {
                return false;
            }

            // 获取设备信息
            $device = self::deviceInfo($device_code);
            if (is_bool($device)) {
                self::setError('设备不存在');
                return false;
            }

            // $response = \app\common\service\ToolsService::Wechat()->online([
            //     'wechat_id' => $wechat_id,
            //     'device_code' => $device_code,
            //     'type' => $type,
            // ]);

            $response = self::wxOnline([
                'wechat_id' => $wechat_id,
                'device_code' => $device_code,
                'type' => $type,
            ]);
            
            if (isset($response['code']) && $response['code'] != 10000) {
                self::setError($response['message']?? '获取在线状态失败');
                return false;
            }

            self::$returnData = $response['data']['online_status'] ?? 0;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
