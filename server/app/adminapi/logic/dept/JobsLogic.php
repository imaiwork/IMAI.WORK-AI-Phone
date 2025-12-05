<?php


namespace app\adminapi\logic\dept;

use app\common\enum\YesNoEnum;
use app\common\logic\BaseLogic;
use app\common\model\article\Article;
use app\common\model\dept\Jobs;
use app\common\service\FileService;


/**
 * 岗位管理逻辑
 * Class JobsLogic
 * @package app\adminapi\logic\dept
 */
class JobsLogic extends BaseLogic
{


    /**
     * @notes 新增岗位
     * @param array $params
     * @author 段誉
     * @date 2022/5/26 9:58
     */
    public static function add(array $params)
    {
        Jobs::create([
            'name' => $params['name'],
            'code' => $params['code'],
            'sort' => $params['sort'] ?? 0,
            'status' => $params['status'],
            'remark' => $params['remark'] ?? '',
        ]);
    }


    /**
     * @notes 编辑岗位
     * @param array $params
     * @return bool
     * @author 段誉
     * @date 2022/5/26 9:58
     */
    public static function edit(array $params): bool
    {
        try {
            Jobs::update([
                'id' => $params['id'],
                'name' => $params['name'],
                'code' => $params['code'],
                'sort' => $params['sort'] ?? 0,
                'status' => $params['status'],
                'remark' => $params['remark'] ?? '',
            ]);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 删除岗位
     * @param array $params
     * @author 段誉
     * @date 2022/5/26 9:59
     */
    public static function delete(array $params)
    {
        Jobs::destroy($params['id']);
    }


    /**
     * @notes 获取岗位详情
     * @param $params
     * @return array
     * @author 段誉
     * @date 2022/5/26 9:59
     */
    public static function detail($params): array
    {
        return Jobs::findOrEmpty($params['id'])->toArray();
    }


    /**
     * @notes 岗位数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/10/13 10:30
     */
    public static function getAllData()
    {
        return Jobs::where(['status' => YesNoEnum::YES])
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
    }
}
