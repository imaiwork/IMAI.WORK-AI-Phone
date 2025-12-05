<?php


namespace app\adminapi\logic\kb;

use app\common\logic\BaseLogic;
use app\common\pgsql\KbEmbedding;
use Exception;

/**
 * 训练数据管理
 */
class KbTeachLogic extends BaseLogic
{
    /**
     * @notes 删除数据
     * @param array|string $uuid
     * @return bool
     *@author kb
     */
    public static function del( array|string $uuid): bool
    {
        try {
            if (is_array($uuid)) {
                $where[] = ['uuid', 'in', $uuid];
            }else{
                $where[] = ['uuid', '=', $uuid];
            }
            $model = new KbEmbedding();
            $model->where($where)->update([
                'is_delete' => 1,
                'delete_time' => time()
            ]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}