<?php


namespace app\adminapi\logic\tutorial;

use app\common\logic\BaseLogic;
use app\common\model\tutorial\Tutorial;

/**
 * 教程卡片管理逻辑
 * Class TutorialLogic
 * @package app\adminapi\logic\tutorial
 */
class TutorialLogic extends BaseLogic
{


    public static function add(array $params)
    {
        try {
            $params['sub_items'] =  $params['sub_items'] ?? [];
            // if (count($params['sub_items']) == 0) {
            //     self::setError('请添加子项');
            //     return false;
            // }
          
            $tutorial = Tutorial::create([
                'tutorial_category_id' => $params['tutorial_category_id'],
                'title' => $params['title'],
                'main_type' => $params['main_type'],
                'main_url' => $params['main_url'],
                'sub_items' => $params['sub_items'],
                'sort' => $params['sort'] ?? 0,
                'status' => $params['status'] ?? 0,
                ]);
            self::$returnData = $tutorial->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function edit(array $params): bool
    {
        try {

            $updateData = ['id' => $params['id']];
            
            $allowFields = ['id', 'tutorial_category_id', 'title', 'main_type', 'main_url', 'sub_items', 'sort', 'status'];
            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }
            Tutorial::update($updateData);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function delete(array $params)
    {   try {
            Tutorial::destroy($params['id']);
        } catch (\Exception $e) {
            dd($e->getMessage());
            self::setError($e->getMessage());
            return false;
        }
        return true;
    }

    public static function detail($params): bool
    {
        try {
            $tutorial = Tutorial::findOrEmpty($params['id'])->toArray();
            if (empty($tutorial)) {
                self::setError('教程卡片不存在');
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
        self::$returnData = $tutorial;
        return true;
    }
}
