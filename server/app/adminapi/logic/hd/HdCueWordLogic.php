<?php


namespace app\adminapi\logic\hd;

use app\common\model\hd\HdCueWord;
use app\common\logic\BaseLogic;
use think\facade\Db;


/**
 * HdCueWord逻辑
 * Class HdCueWordLogic
 * @package app\adminapi\logic\hd
 */
class HdCueWordLogic extends BaseLogic
{
    /**
     * @desc 添加
     * @param array $params
     * @return bool
     * @date 2024/5/23 11:33
     * @author dagouzi
     */
    public static function add(array $params)
    {
        Db::startTrans();
        try {
            HdCueWord::create($params);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 编辑
     * @param array $params
     * @return bool
     * @date 2024/5/23 11:33
     * @author dagouzi
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            HdCueWord::where('id', $params['id'])->update($params);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 删除
     * @param array $params
     * @return bool
     * @date 2024/5/23 11:33
     * @author dagouzi
     */
    public static function delete(array $params): bool
    {
        try {
            if (is_string($data['id'])) {
                HdCueWord::destroy(['id' => $data['id']]);
            } else {
                HdCueWord::destroy($data['id']);
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * @desc 获取详情
     * @param $params
     * @return array
     * @date 2024/5/23 11:33
     * @author dagouzi
     */
    public static function detail($params): array
    {
        return HdCueWord::findOrEmpty($params['id'])->toArray();
    }

    /**
     * @desc 修改状态
     * @param $params
     * @return HdCueWord
     * @date 2024/5/23 11:43
     * @author dagouzi
     */
    public static function updateStatus($params)
    {
        return HdCueWord::where(['id' => $params['id']])->update(['status' => $params['status']]);
    }
}
