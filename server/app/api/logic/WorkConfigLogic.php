<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\workWeChat\WorkConfig;


/**
 * logic
 */
class WorkConfigLogic extends BaseLogic
{
    /**
     * 添加
     * @param array $postData
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-08-22 15:06:34
     */
    public static function add(array $postData, int $userId): bool
    {
        try {
            $info = WorkConfig::where('user_id', $userId)->findOrEmpty();
            if (!$info->isEmpty()) {
                throw new \Exception("不可重复添加");
            }
            $postData['user_id'] = $userId;
            self::$returnData = WorkConfig::create($postData)->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 删除
     * @param array $getData
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-08-22 15:06:34
     */
    public static function delete(array $getData, int $userId): bool
    {
        try {
            WorkConfig::destroy(['user_id' => $userId, 'id' => $getData['id']]);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 编辑
     * @param array $postData
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-08-22 15:06:34
     */
    public static function edit(array $postData, int $userId): bool
    {
        try {
            $info = WorkConfig::where('user_id', $userId)->findOrEmpty();
            if ($info->isEmpty()) {
                $postData['user_id'] = $userId;
                WorkConfig::create($postData)->toArray();
            } else {
                WorkConfig::where('user_id', $userId)->update($postData);
            }
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }


    /**
     * 详情
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-08-22 15:06:34
     */
    public static function detail(int $userId): bool
    {
        try {
            self::$returnData = WorkConfig::where('user_id', $userId)->findOrEmpty()->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 修改状态
     * @param array $params
     * @return bool
     * @author L
     * @data 2024/7/5 10:25
     */
    public static function changeStatus(array $params):bool
    {
        try {
            $info = WorkConfig::findOrEmpty($params['id']);
            if ($info->isEmpty()) {
                throw new \Exception("信息异常");
            }
            $info->status = 1 - $info->status;
            $info->save();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
                        