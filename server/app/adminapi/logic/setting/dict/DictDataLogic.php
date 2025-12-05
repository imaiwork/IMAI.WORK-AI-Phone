<?php


namespace app\adminapi\logic\setting\dict;

use app\common\logic\BaseLogic;
use app\common\model\dict\DictData;
use app\common\model\dict\DictType;


/**
 * 字典数据逻辑
 * Class DictDataLogic
 * @package app\adminapi\logic\DictData
 */
class DictDataLogic extends BaseLogic
{

    /**
     * @notes 添加编辑
     * @param array $params
     * @return DictData|\think\Model
     * @author 段誉
     * @date 2022/6/20 17:13
     */
    public static function save(array $params)
    {
        $data = [
            'name' => $params['name'],
            'value' => $params['value'],
            'sort' => $params['sort'] ?? 0,
            'status' => $params['status'],
            'remark' => $params['remark'] ?? '',
        ];

        if (!empty($params['id'])) {
            return DictData::where(['id' => $params['id']])->update($data);
        } else {
            $dictType = DictType::findOrEmpty($params['type_id']);
            $data['type_id'] = $params['type_id'];
            $data['type_value'] = $dictType['type'];
            return DictData::create($data);
        }
    }


    /**
     * @notes 删除字典数据
     * @param array $params
     * @return bool
     * @author 段誉
     * @date 2022/6/20 17:01
     */
    public static function delete(array $params)
    {
        return DictData::destroy($params['id']);
    }


    /**
     * @notes 获取字典数据详情
     * @param $params
     * @return array
     * @author 段誉
     * @date 2022/6/20 17:01
     */
    public static function detail($params): array
    {
        return DictData::findOrEmpty($params['id'])->toArray();
    }
}
