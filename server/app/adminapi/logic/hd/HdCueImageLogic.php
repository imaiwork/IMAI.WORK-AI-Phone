<?php


namespace app\adminapi\logic\hd;

use app\common\model\hd\HdCueImage;
use app\common\logic\BaseLogic;
use think\facade\Db;


/**
 * HdCueImage逻辑
 * Class HdCueImageLogic
 * @package app\adminapi\logic\hd
 */
class HdCueImageLogic extends BaseLogic
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
            HdCueImage::create($params);

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
            HdCueImage::where('id', $params['id'])->update($params);

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
                HdCueImage::destroy(['id' => $data['id']]);
            } else {
                HdCueImage::destroy($data['id']);
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
        return HdCueImage::findOrEmpty($params['id'])->toArray();
    }

    /**
     * @desc 修改状态
     * @param $params
     * @return HdCueImage
     * @date 2024/5/23 11:43
     * @author dagouzi
     */
    public static function updateStatus($params)
    {
        return HdCueImage::where(['id' => $params['id']])->update(['status' => $params['status']]);
    }
}
