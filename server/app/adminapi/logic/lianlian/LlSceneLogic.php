<?php

namespace app\adminapi\logic\lianlian;

use app\common\model\lianlian\LlScene;
use app\common\logic\BaseLogic;
use app\common\service\FileService;

/**
 * logic
 */
class LlSceneLogic extends BaseLogic
{
    /**
     * 添加
     * @param array $data
     * @return bool
     * @author L
     * @data 2024-07-05 11:05:46
     */
    public static function add(array $data): bool
    {
        try {
            if (isset($data['logo'])) {
                $data['logo'] = FileService::setFileUrl($data['logo']);
            }

            if (isset($data['training_target'])) {
                $data['training_target'] = json_encode($data['training_target'], JSON_UNESCAPED_UNICODE);
            }


            if(isset($data['analysis_report_config'])){
                
                $data['analysis_report_config'] = json_encode($data['analysis_report_config'], JSON_UNESCAPED_UNICODE);
            }

            if(isset($data['tips'])){
                
                $data['tips'] = json_encode($data['tips'], JSON_UNESCAPED_UNICODE);
            }

            self::$returnData = LlScene::create($data)->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 删除
     * @param array $data
     * @return bool
     * @author L
     * @data 2024-07-05 11:05:46
     */
    public static function delete(array $data): bool
    {
        try {
            if (is_string($data['id'])) {
                LlScene::destroy(['id' => $data['id']]);
            } else {
                LlScene::destroy($data['id']);
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 编辑
     * @param array $data
     * @return bool
     * @author L
     * @data 2024-07-05 11:05:46
     */
    public static function edit(array $data): bool
    {
        try {
            $info = LlScene::findOrEmpty($data['id']);

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            if (isset($data['logo'])) {
                $data['logo'] = FileService::setFileUrl($data['logo']);
            }

            if (isset($data['training_target'])) {
                $data['training_target'] = json_encode($data['training_target'], JSON_UNESCAPED_UNICODE);
            }


            if(isset($data['analysis_report_config'])){
                
                $data['analysis_report_config'] = json_encode($data['analysis_report_config'], JSON_UNESCAPED_UNICODE);
            }

            if(isset($data['tips'])){
                
                $data['tips'] = json_encode($data['tips'], JSON_UNESCAPED_UNICODE);
            }
            

            LlScene::update($data);

            $info = $info->refresh();

            $info->logo = FileService::getFileUrl($info->logo);

            $info->training_target = json_decode($info->training_target, true);    

            $info->analysis_report_config = json_decode($info->analysis_report_config, true);

            $info->tips = json_decode($info->tips, true);

            self::$returnData = $info->toArray();
            
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 详情
     * @param int $id
     * @return bool
     * @author L
     * @data 2024-07-05 11:05:46
     */
    public static function detail(int $id): bool
    {
        try {

            $info = LlScene::findOrEmpty($id);
            if ($info->isEmpty()) {

                throw new \Exception("查无此信息");
            }

            self::$returnData = $info->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 修改状态
     * @param int $id
     * @return bool
     * @author L
     * @data 2024/7/5 10:25
     */

    public static function changeStatus(int $id):bool
    {
        try {
            $info = LlScene::findOrEmpty($id);


            if($info->isEmpty()){
                throw new \Exception("查无此信息");
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
