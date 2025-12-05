<?php


namespace app\adminapi\logic\hd;

use app\common\model\hd\HdCueImageCategory;
use app\common\logic\BaseLogic;
use think\facade\Db;


/**
 * HdCueImageCategory逻辑
 * Class HdCueImageCategoryLogic
 * @package app\adminapi\logic\hd
 */
class HdCueImageCategoryLogic extends BaseLogic
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
            HdCueImageCategory::create($params);

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
            HdCueImageCategory::where('id', $params['id'])->update($params);

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
                HdCueImageCategory::destroy(['id' => $data['id']]);
            } else {
                HdCueImageCategory::destroy($data['id']);
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
        return HdCueImageCategory::findOrEmpty($params['id'])->toArray();
    }

    /**
     * @desc 修改状态
     * @param $params
     * @return HdCueImageCategory
     * @date 2024/5/23 11:43
     * @author dagouzi
     */
    public static function updateStatus($params)
    {
        return HdCueImageCategory::where(['id' => $params['id']])->update(['status' => $params['status']]);
    }
}
