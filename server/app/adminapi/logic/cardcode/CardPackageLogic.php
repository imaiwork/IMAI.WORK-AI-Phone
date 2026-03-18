<?php
namespace app\adminapi\logic\cardcode;

use app\common\logic\BaseLogic;
use app\common\model\cardcode\CardPackage;

/**
 * 卡密套餐逻辑层
 * Class CardPackageLogic
 * @package app\adminapi\logic\cardcode
 */
class CardPackageLogic extends BaseLogic
{
    /**
     * @notes 添加套餐
     * @param array $params
     * @return CardPackage
     */
    public static function add(array $params)
    {
        return CardPackage::create([
            'name' => $params['name'],
            'tokens' => $params['tokens'],
            'status' => $params['status'],
            'sort' => $params['sort'] ?? 0,
        ]);
    }

    public static function edit(array $params)
    {
        $updateData = [];
        if (isset($params['name'])) {
            $updateData['name'] = $params['name'];
        }
        if (isset($params['tokens'])) {
            $updateData['tokens'] = $params['tokens'];
        }
        if (isset($params['status'])) {
            $updateData['status'] = $params['status'];
        }
        if (isset($params['sort'])) {
            $updateData['sort'] = $params['sort'];
        }

        if (!empty($updateData)) {
            return CardPackage::update($updateData, ['id' => $params['id']]);
        }

        return true;
    }

    /**
     * @notes 套餐详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id)
    {
        return CardPackage::findOrEmpty($id)->toArray();
    }

    /**
     * @notes 删除套餐
     * @param int $id
     * @return bool
     */
    public static function delete(int $id)
    {
        return CardPackage::destroy($id);
    }
}
