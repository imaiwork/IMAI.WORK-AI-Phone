<?php


namespace app\adminapi\logic\hd;

use app\common\model\hd\HdCueWordCategory;
use app\common\logic\BaseLogic;
use think\facade\Db;


/**
 * HdCueWordCategory逻辑
 * Class HdCueWordCategoryLogic
 * @package app\adminapi\logic\hd
 */
class HdCueWordCategoryLogic extends BaseLogic
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
            HdCueWordCategory::create($params);

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
            HdCueWordCategory::where('id', $params['id'])->update($params);

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
     * @param array $data
     * @return bool
     * @date 2024/5/23 11:33
     * @author dagouzi
     */
    public static function delete(array $data): bool
    {
        try {
            if (is_string($data['id'])) {
                HdCueWordCategory::destroy(['id' => $data['id']]);
            } else {
                HdCueWordCategory::destroy($data['id']);
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
        return HdCueWordCategory::findOrEmpty($params['id'])->toArray();
    }

    /**
     * @desc 修改状态
     * @param $params
     * @return HdCueWordCategory
     * @date 2024/5/23 11:43
     * @author dagouzi
     */
    public static function updateStatus($params)
    {
        return HdCueWordCategory::where(['id' => $params['id']])->update(['status' => $params['status']]);
    }
}
