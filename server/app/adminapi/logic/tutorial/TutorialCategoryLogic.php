<?php


namespace app\adminapi\logic\tutorial;

use app\common\logic\BaseLogic;
use app\common\model\tutorial\TutorialCategory;

/**
 * 教程分类管理逻辑
 * Class TutorialCategoryLogic
 * @package app\adminapi\logic\tutorial
 */
class TutorialCategoryLogic extends BaseLogic
{


    public static function add(array $params)
    {
        TutorialCategory::create([
            'name' => $params['name'],
            'status' => $params['status'] ?? 0,
            'sort' => $params['sort'] ?? 0
        ]);
    }


    public static function edit(array $params): bool
    {
        try {
            $updateData = ['id' => $params['id']];
            
            $allowFields = ['id', 'name', 'status', 'sort'];
            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }
            TutorialCategory::update($updateData);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function delete(array $params)
    {
        TutorialCategory::destroy($params['id']);
    }

    public static function detail($params): array
    {
        return TutorialCategory::findOrEmpty($params['id'])->toArray();
    }


    public static function getAllData()
    {
        return TutorialCategory::order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
    }
}
