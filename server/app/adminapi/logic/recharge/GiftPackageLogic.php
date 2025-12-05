<?php

namespace app\adminapi\logic\recharge;

use app\common\model\recharge\GiftPackage;
use app\common\logic\BaseLogic;


/**
 * logic
 */
class GiftPackageLogic extends BaseLogic
{
    /**
     * 添加
     * @param array $postData
     * @return bool
     * @author L
     * @data 2024-08-15 15:04:27
     */
    public static function add(array $postData): bool
    {
        try {
            $postData['package_info'] = json_encode($postData['package_info'], JSON_UNESCAPED_UNICODE);
            $postData['package_info'] = json_decode($postData['package_info'], true);
            $postData['package_info']['expired'] =  $postData['package_info']['expired'] ?? 50;
            if($postData['package_info']['expired'] > 70){
                throw new \Exception("过期时间不能大于70");
            }
            $postData['package_info'] = json_encode($postData['package_info'], JSON_UNESCAPED_UNICODE);
        
            self::$returnData     = GiftPackage::create($postData)->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 删除
     * @param array $getData
     * @return bool
     * @author L
     * @data 2024-08-15 15:04:27
     */
    public static function delete(array $getData): bool
    {
        try {
            GiftPackage::destroy(['id' => $getData['id']]);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 编辑
     * @param array $postData
     * @return bool
     * @author L
     * @data 2024-08-15 15:04:27
     */
    public static function edit(array $postData): bool
    {
        try {
            $info = GiftPackage::findOrEmpty($postData['id']);
            if ($info->isEmpty()) {
                throw new \Exception("信息异常");
            }
            $postData['package_info'] = json_encode($postData['package_info'], JSON_UNESCAPED_UNICODE);
            $postData['package_info'] = json_decode($postData['package_info'], true);
            $postData['package_info']['expired'] =  $postData['package_info']['expired'] ?? 50;
            if($postData['package_info']['expired'] > 70){
                throw new \Exception("过期时间不能大于70");
            }
            $postData['package_info'] = json_encode($postData['package_info'], JSON_UNESCAPED_UNICODE);
            self::$returnData = GiftPackage::update($postData)->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 详情
     * @param array $getData
     * @return bool
     * @author L
     * @data 2024-08-15 15:04:27
     */
    public static function detail(array $getData): bool
    {
        try {
            self::$returnData = GiftPackage::json(['package_info'], true)->findOrEmpty($getData['id'])->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 修改状态
     * @param array $params
     * @return bool
     * @author L
     * @data 2024/7/5 10:25
     */
    public static function changeStatus(array $params): bool
    {
        try {
            $info = GiftPackage::findOrEmpty($params['id']);
            if ($info->isEmpty()) {
                throw new \Exception("信息异常");
            }
            $info->status = 1 - $info->status;
            $info->save();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
                        