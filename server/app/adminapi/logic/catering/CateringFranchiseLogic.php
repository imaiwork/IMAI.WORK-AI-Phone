<?php


namespace app\adminapi\logic\catering;

use app\common\logic\BaseLogic;
use app\common\model\catering\CateringFranchise;

/**
 * 招商项目管理逻辑
 * Class CateringFranchiseLogic
 * @package app\adminapi\logic\catering
 */
class CateringFranchiseLogic extends BaseLogic
{


    public static function add(array $params)
    {
        try {
            $catering = CateringFranchise::create([
                'category_type' => $params['category_type'],
                'title' => $params['title'],
                'exposure' => $params['exposure'] ?? '',
                'leads' => $params['leads'] ?? '0',
                'convert_users' => $params['convert_users'] ?? '0',
                'intro' => $params['intro'] ?? '',
                'target_users' => $params['target_users'] ?? [],
                'task_types' => $params['task_types'] ?? [],
                'detail_content' => $params['detail_content'] ?? '',
                'detail_task_types' => $params['detail_task_types'] ?? '',
                'detail_users' => $params['detail_users'] ?? '',
                'detail_images' => $params['detail_images'] ?? [],
                'detail_videos' => $params['detail_videos'] ?? [],
                'status' => $params['status'] ?? 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            self::$returnData = $catering->toArray();
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
            
            $allowFields = ['category_type', 'title', 'exposure', 'leads', 'convert_users', 'intro', 'target_users', 'task_types', 'detail_content', 'detail_task_types', 'detail_users', 'detail_images', 'detail_videos', 'status'];
            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }
            $updateData['update_time'] = time();
            
            CateringFranchise::update($updateData);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function delete(array $params)
    {
        try {
            CateringFranchise::destroy($params['id']);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
        return true;
    }

    public static function detail($params): bool
    {
        try {
            $catering = CateringFranchise::findOrEmpty($params['id'])->toArray();
            if (empty($catering)) {
                self::setError('招商项目不存在');
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
        self::$returnData = $catering;
        return true;
    }
}
