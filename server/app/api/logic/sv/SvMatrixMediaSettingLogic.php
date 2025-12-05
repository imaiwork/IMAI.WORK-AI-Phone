<?php

namespace app\api\logic\sv;

use app\api\logic\ApiLogic;
use app\common\model\sv\SvMatrixMediaSetting;
use think\facade\Db;

/**
 * 矩阵媒体设置逻辑处理
 * Class SvMatrixMediaSettingLogic
 * @package app\api\logic\sv
 */
class SvMatrixMediaSettingLogic extends ApiLogic
{
    /**
     * 添加矩阵媒体设置
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '矩阵媒体设置'.date('YmdHi');
            
            // 预处理JSON字段
            $jsonFields = ['media_url', 'copywriting', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            // JSON格式正确，保持原样
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $params[$field] = json_encode([]);
                }
            }
            $params['media_count'] = count(json_decode($params['media_url'], true));
            if ( $params['media_count'] < 1){
                self::setError("媒体必填");
                return false;
            }
            // 开始事务
            Db::startTrans();
            try {
                $setting = SvMatrixMediaSetting::create($params);
                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新矩阵媒体设置
     * @param array $params
     * @return bool
     */
    public static function update(array $params): bool
    {
        try {
            $setting = SvMatrixMediaSetting::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->find();

            if (!$setting) {
                self::setError('矩阵媒体设置不存在');
                return false;
            }

            // 预处理JSON字段
            $jsonFields = ['media_url', 'copywriting', 'extra'];
            foreach ($jsonFields as $field) {
                if (isset($params[$field])) {
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    }
                }
            }
            $params['media_count'] = count(json_decode($params['media_url'], true));
            $params['update_time'] = time();

            // 开始事务
            Db::startTrans();
            try {
                $setting->save($params);
                Db::commit();
                self::$returnData = $setting->refresh()->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取矩阵媒体设置详情
     * @param int $id
     * @return bool
     */
    public static function detail(int $id): bool
    {
        try {
            $setting = SvMatrixMediaSetting::where('id', $id)
                ->where('user_id', self::$uid)
                ->find();

            if (!$setting) {
                self::setError('矩阵媒体设置不存在');
                return false;
            }

            $settingData = $setting->toArray();

            // 处理JSON字段
            $jsonFields = ['media_url', 'copywriting', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($settingData[$field])) {
                    $settingData[$field] = json_decode($settingData[$field], true);
                } else {
                    $settingData[$field] = [];
                }
            }

            self::$returnData = $settingData;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除矩阵媒体设置
     * @param int|array $id
     * @return bool
     */
    public static function delete($id): bool
    {
        try {
            if (is_string($id)) {
                SvMatrixMediaSetting::destroy(['id' => $id]);
            } else {
                SvMatrixMediaSetting::whereIn('id', $id)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
