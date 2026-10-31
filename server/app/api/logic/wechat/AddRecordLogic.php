<?php

namespace app\api\logic\wechat;

use app\common\model\wechat\AiWechatAddRecord;

/**
 * AddRecordLogic
 * @desc AI销售加微记录
 * @author Qasim
 */
class AddRecordLogic extends WechatBaseLogic
{

    /**
     * @desc 删除加微记录
     * @param array $params
     * @return bool
     */
    public static function delete(array $params)
    {
        try {
            $record = AiWechatAddRecord::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($record->isEmpty()) {
                self::setError('加微记录不存在');
                return false;
            }

            $record->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
