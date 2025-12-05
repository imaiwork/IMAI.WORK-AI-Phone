<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvGreetStrategy;
use app\common\model\sv\SvReplyStrategy;
use app\common\model\sv\SvRobot;
use app\common\service\FileService;
use app\common\model\sv\SvAccountContact;
use app\common\model\sv\SvAccount;
use think\facade\Cache;

/**
 * MessageLogic
 * @desc 消息
 * @author Qasim
 */
class MessageLogic extends SvBaseLogic
{

    /**
     * @desc 打招呼
     * @param array $params
     * @return bool
     */
    public static function greetMessage(array $params)
    {

        try {
            // 获取账号信息
            $account = self::accountInfo($params['account']);
            if (is_bool($account)) {
                return false;
            }

            // 获取好友信息
            $friend = self::friendInfo($params['account'], $params['friend_id']);
            if (is_bool($friend)) {
                return false;
            }

            if ($friend->open_ai == 0) {
                self::setError('未开启全局AI');
                return false;
            }

            // 获取用户设置
            $greet = SvGreetStrategy::where('user_id', self::$uid)->findOrEmpty();

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
                    'account' => $account->account,
                    'friend_id' => $params['friend_id'],
                    'device_code' => $account->device_code,
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

                \app\common\service\ToolsService::Sv()->push($message);
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
            // 获取账号信息
            $account = SvAccount::alias('w')
                ->join('sv_setting s', 's.account = w.account')
                ->where('w.account', $params['account'])
                ->where('w.user_id', self::$uid)
                ->findOrEmpty();

            if ($account->isEmpty()) {
                self::setError('账号不存在');
                return false;
            }

            // 获取好友信息
            $friend = self::friendInfo($params['account'], $params['friend_id']);
            if (is_bool($friend)) {
                return false;
            }

            // 未开启全局AI
            if ($friend->open_ai == 0) {
                self::setError('未开启全局AI');
                return false;
            }

            // 获取用户回复设置
            $reply = SvReplyStrategy::where('user_id', self::$uid)->where('robot_id', $account->robot_id)->findOrEmpty();
            if ($reply->isEmpty()) {
                self::setError('请先设置回复的配置');
                return false;
            }

            // 机器人
            $robot = SvRobot::where('id', $account->robot_id)->findOrEmpty();
            if ($robot->isEmpty()) {
                self::setError('机器人不存在');
                return false;
            }

            // 组装请求参数
            $request = [
                'account' => $account->account,
                'friend_id' => $params['friend_id'],
                'friend_remark' => $friend->remark,
                'device_code' => $account->device_code,
                'message' => $params['message'],
                'message_id' => $params['message_id'],
                'message_type' => 1,
            ];

            //step 1. 正则匹配停止AI回复
            $stop = self::regularMatchStopAI($reply, $request);
            if ($stop) {

                // 关闭AI接管
                SvAccountContact::where('account', $account->account)->where('friend_id', $params['friend_id'])->update(['takeover_mode' => 0]);
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
     * @param string $account
     * @param string $device_code
     * @param int $type 0:  1: 设备
     * @return bool
     */
    public static function getOnlineStatus(string $account, string $device_code, int $type = 0)
    {

        try {
            // 获取账号信息
            $accountinfo = self::accountInfo($account, false, $type);
            if (is_bool($accountinfo)) {
                return false;
            }

            // 获取设备信息
            $device = self::deviceInfo($device_code);
            if (is_bool($device)) {
                self::setError('设备不存在');
                return false;
            }
            Cache::store('redis')->select(env('redis.WS_SELECT', 8));
            $account = Cache::store('redis')->get("xhs:device:{$device_code}:status");

            self::$returnData = $account === 'online' ? 1 : 0;
            return true;
            
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
