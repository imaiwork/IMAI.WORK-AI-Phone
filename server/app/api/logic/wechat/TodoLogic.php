<?php

namespace app\api\logic\wechat;

use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatTodo;
use app\common\traits\WechatTrait;
/**
 * TodoLogic
 * @desc 微信待办
 * @author Qasim
 */
class TodoLogic extends WechatBaseLogic
{
    use WechatTrait;
    /**
     * @desc 添加待办
     * @param array $params
     * @return bool
     */
    public static function addTodo(array $params)
    {

        try {
            // 获取微信账号信息
            $wechat = self::wechatInfo($params['wechat_id']);
            if (is_bool($wechat)) {
                return false;
            }

            // 获取好友信息
            $friend = self::friendInfo($wechat->wechat_id, $params['friend_id']);
            if (is_bool($friend)) {
                return false;
            }

            // 添加
            $todo = AiWechatTodo::create($params);

            self::$returnData = $todo->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 编辑待办
     * @param array $params
     * @return bool
     */
    public static function updateTodo(array $params)
    {

        try {
            // 获取微信账号信息
            $wechat = self::wechatInfo($params['wechat_id']);
            if (is_bool($wechat)) {
                self::setError('微信id不存在');
                return false;
            }

            // 获取好友信息
            $friend = self::friendInfo($wechat->wechat_id, $params['friend_id']);
            if (is_bool($friend)) {
                self::setError('好友id不存在');
                return false;
            }

            $todo = AiWechatTodo::where('id', $params['id'])->findOrEmpty();
            if ($todo->isEmpty()) {
                self::setError('待办不存在');
                return false;
            }
            // 编辑
            AiWechatTodo::where('id',$params['id'])->update($params);

            self::$returnData = $todo->refresh()->toArray();;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除
     * @param array $params
     * @return bool
     */
    public static function deleteTodo(array $params)
    {
        try {
            $todo = AiWechatTodo::where('id', $params['id'])->findOrEmpty();
            if ($todo->isEmpty()) {
                self::setError('待办不存在');
                return false;
            }

            // 获取微信
            $wechat = self::wechatInfo($todo->wechat_id);
            if (is_bool($wechat)) {
                self::setError('待办不存在');
                return false;
            }

            $todo->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 推送消息
     * @param array $params
     * @return bool
     */
    public static function pushMessageCron()
    {

        try{
            // 获取所有代办
            AiWechatTodo::where('todo_status', 0)
                ->order('id', 'asc')
                ->limit(10)
                ->select()
                ->each(function ($item) {
                    // 验证时间
                    $time = strtotime($item->todo_time);
                    if ($time > time()) {
                        return true;
                    }

                    // 代办提醒， 自动完成即可
                    if($item->todo_type == 0){
                        $item->todo_status = 1;
                        $item->save();
                        return true;
                    }

                    // 失败次数
                    $failNum = $item->retry_num;
                    if ($failNum >= 3) {
                        $item->todo_status = 2;
                        $item->save();
                        return true;
                    }

                    // 获取设备
                    $deviceCode = AiWechat::where('wechat_id', $item->wechat_id)->value('device_code', '');
                    if (empty($deviceCode)) {
                        $item->retry_num = 3;
                        $item->fail_reason = '设备不存在';
                        $item->save();
                        return true;
                    }
                    sleep(10);

                    try {
                        // 推送消息
                        // $response = \app\common\service\ToolsService::Wechat()->push([
                        //     'wechat_id' => $item->wechat_id,
                        //     'friend_id' => $item->friend_id,
                        //     'message' => $item->todo_content,
                        //     'device_code' => $deviceCode,
                        // ]);

                        $response = self::wxPush([
                            'wechat_id' => $item->wechat_id,
                            'friend_id' => $item->friend_id,
                            'message' => $item->todo_content,
                            'device_code' => $deviceCode,
                            'opt_type' => 'todo'
                        ]);

                        if ($response['code'] == 10000) {
                            $item->todo_status = 1;
                            $item->fail_reason = '推送完成';
                            $item->save();
                        } else {
                            $item->retry_num++;
                            $item->fail_reason = $response['message'];
                            $item->save();
                        }
                    } catch (\think\exception\HttpResponseException $e) {
                        $item->retry_num++;
                        $item->fail_reason = $e->getResponse()->getData()['msg'] ?? '提交任务出错';
                        $item->save();
                        return true;
                    }

                    return true;
                });

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
