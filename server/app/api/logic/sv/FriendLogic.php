<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvAccountContact;

/**
 * FriendLogic
 * @desc 好友
 * @author Qasim
 */
class FriendLogic extends SvBaseLogic
{

    /**
     * @desc 添加
     * @param array $params
     * @return bool
     */
    public static function addFriend(array $params)
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
                $friend = SvAccountContact::create($params);
            } else {
                // 更新
                SvAccountContact::where('id', $friend->id)->update($params);
            }

            self::$returnData = $friend->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 添加
     * @param array $params
     * @return bool
     */
    public static function batchAddFriend(array $params)
    {

        try {
            // 获取账号信息
            $account = self::accountInfo($params['account']);
            if (is_bool($account)) {
                return false;
            }

            foreach ($params['friends'] as $friend) {

                if ($friend['label_ids'] == "") {
                    $friend['label_ids'] = [];
                }

                // 获取好友信息
                $friendInfo = self::friendInfo($params['account'], $friend['friend_id']);
                if (is_bool($friendInfo)) {
                    // 创建
                    $friend['account'] = $account->account;
                    SvAccountContact::create($friend);
                } else {
                    // 更新
                    SvAccountContact::where('id', $friendInfo->id)->update($friend);
                }
            }

            self::$returnData = [];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新好友
     * @param array $params
     * @return bool
     */
    public static function updateFriend(array $params)
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

            // 更新好友
            SvAccountContact::where('id', $friend->id)->update($params);
            self::$returnData = $friend->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取好友信息
     * @param array $params
     * @return bool
     */
    public static function deleteFriend(array $params)
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

            $friend->delete();
            self::$returnData = [];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取好友详情
     * @param array $params
     * @return bool
     */
    public static function friendDetail(array $params)
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

            self::$returnData = $friend->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
