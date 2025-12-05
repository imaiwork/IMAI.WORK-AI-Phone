<?php

namespace app\api\logic\wechat;

use app\common\model\wechat\AiWechatContact;
use app\common\model\wechat\AiWechatAcceptFriendStrategy;
use app\common\model\wechat\AiWechatLog;
use think\facade\Queue;

/**
 * FriendLogic
 * @desc 微信好友
 * @author Qasim
 */
class FriendLogic extends WechatBaseLogic
{

    /**
     * @desc 添加微信
     * @param array $params
     * @return bool
     */
    public static function addFriend(array $params)
    {

        try
        {
            // 获取微信账号信息
            $wechat = self::wechatInfo($params['wechat_id']);
            if (is_bool($wechat))
            {
                return false;
            }

            // 获取微信好友信息
            $friend = self::friendInfo($params['wechat_id'], $params['friend_id']);

            if (is_bool($friend))
            {
                $friend = AiWechatContact::create($params);
            }
            else
            {
                // 更新
                AiWechatContact::where('id', $friend->id)->update($params);
            }

            self::$returnData = $friend->refresh()->toArray();
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 添加微信
     * @param array $params
     * @return bool
     */
    public static function batchAddFriend(array $params)
    {

        try
        {
            // 获取微信账号信息
            $wechat = self::wechatInfo($params['wechat_id']);
            if (is_bool($wechat))
            {
                return false;
            }

            foreach ($params['friends'] as $friend)
            {

                if ($friend['label_ids'] == "")
                {
                    $friend['label_ids'] = [];
                }

                // 获取微信好友信息
                $friendInfo = self::friendInfo($params['wechat_id'], $friend['friend_id']);
                if (is_bool($friendInfo))
                {
                    // 创建
                    $friend['wechat_id'] = $wechat->wechat_id;
                    AiWechatContact::create($friend);
                }
                else
                {
                    // 更新
                    AiWechatContact::where('id', $friendInfo->id)->update($friend);
                }
            }

            self::$returnData = [];
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新微信好友
     * @param array $params
     * @return bool
     */
    public static function updateFriend(array $params)
    {
        try
        {
            // 获取微信账号信息
            $wechat = self::wechatInfo($params['wechat_id']);
            if (is_bool($wechat))
            {
                return false;
            }

            // 获取微信好友信息
            $friend = self::friendInfo($params['wechat_id'], $params['friend_id']);
            if (is_bool($friend))
            {
                return false;
            }

            // 更新微信好友
            AiWechatContact::where('id', $friend->id)->update($params);
            self::$returnData = $friend->refresh()->toArray();
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取微信好友信息
     * @param array $params
     * @return bool
     */
    public static function deleteFriend(array $params)
    {
        try
        {
            // 获取微信账号信息
            $wechat = self::wechatInfo($params['wechat_id']);
            if (is_bool($wechat))
            {
                return false;
            }

            // 获取微信好友信息
            $friend = self::friendInfo($params['wechat_id'], $params['friend_id']);
            if (is_bool($friend))
            {
                return false;
            }

            $friend->delete();
            self::$returnData = [];
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取微信好友详情
     * @param array $params
     * @return bool
     */
    public static function friendDetail(array $params)
    {
        try
        {
            // 获取微信账号信息
            $wechat = self::wechatInfo($params['wechat_id']);
            if (is_bool($wechat))
            {
                return false;
            }

            // 获取微信好友信息
            $friend = self::friendInfo($params['wechat_id'], $params['friend_id']);
            if (is_bool($friend))
            {
                return false;
            }

            self::$returnData = $friend->toArray();
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 接受好友请求
     * @param array $params
     * @return bool
     */
    public static function acceptFriend(array $params)
    {

        try
        {
            // 获取微信账号信息
            $wechat = self::wechatInfo($params['wechat_id']);
            if (is_bool($wechat))
            {
                return false;
            }

            // 获取自动通过好友策略
            $strategy = AiWechatAcceptFriendStrategy::where('user_id', self::$uid)->findOrEmpty();

            if ($strategy->isEmpty())
            {
                self::setError('请先设置自动通过好友策略');
                return false;
            }

            if ($strategy->is_enable == 0)
            {
                self::setError('自动通过好友策略已禁用');
                return false;
            }

            // 检查微信号是否存在执行集合中
            if (!in_array($wechat->wechat_id, $strategy->wechat_ids))
            {
                self::setError('此微信号未在自动通过好友策略中');
                return false;
            }

            // 检查好友来源是否符合
            if (!in_array($params['friend_source'], $strategy->accept_source))
            {
                self::setError('此好友来源未在自动通过好友策略中');
                return false;
            }

            // 当日已接受好友数量
            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');
            $todayAcceptCount = AiWechatLog::where('user_id', self::$uid)
                ->where('log_type', AiWechatLog::TYPE_ACCEPT_FRIEND)
                ->whereBetween('create_time', [$todayStart, $todayEnd])
                ->count();

            // 超出添加上限
            if ($todayAcceptCount >= $strategy->accept_numbers)
            {
                self::setError('今日已达到自动通过好友数量上限');
                return false;
            }

            // 获取最近一条接受时间
            $lastAcceptTime = AiWechatLog::where('user_id', self::$uid)
                ->where('log_type', AiWechatLog::TYPE_ACCEPT_FRIEND)
                ->where('friend_id', $params['friend_id'])
                ->order('create_time', 'desc')
                ->value('create_time', 0);

            // 计算当前时间 与 上一条接受时间 间隔 单位分钟
            $interval = 0;
            if ($lastAcceptTime)
            {
                $interval = (time() - $lastAcceptTime) / 60;
            }
            $taskId = generate_unique_task_id();

            // 任务数据
            $data = [
                'device_code'   => $wechat->device_code,
                'wechat_id'     => $params['wechat_id'],
                'friend_id'     => $params['friend_id'],
                'task_id'       => $taskId,
                'uid'           => self::$uid,
            ];

            // 推送到队列
            Queue::later($interval, 'app\common\Jobs\WechatAcceptFriendJob@fire', $data);
            self::$returnData = [];
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }
}
