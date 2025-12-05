<?php

namespace app\adminapi\logic\lianlian;

use app\common\model\lianlian\LlAnalysis;
use app\common\logic\BaseLogic;
use app\common\service\FileService;
use app\api\logic\LianLianLogic;

/**
 * logic
 */
class LlAnalysisLogic extends BaseLogic
{
    /**
     * 重试
     * @param int $id
     * @return bool
     * @author L
     * @data 2024-07-05 11:05:46
     */
    public static function retry(int $id): bool
    {
        try {
            $info = LlAnalysis::findOrEmpty($id);

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            if($info->status != 3){
                throw new \Exception("状态错误");
            }

            LianLianLogic::analysisCron($info->task_id);

            self::$returnData = $info->refresh()->toArray();

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
                LlAnalysis::destroy(['id' => $data['id']]);
            } else {
                LlAnalysis::destroy($data['id']);
            }
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
            $info = LlAnalysis::findOrEmpty($id);

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            if($info->status != 2){
                throw new \Exception("状态错误");
            }

            //时长 end_time - start_time
            $info->duration   = $info->end_time - $info->start_time;
            $info->start_time = date('Y-m-d H:i:s', $info->start_time);
            $info->end_time   = date('Y-m-d H:i:s', $info->end_time);

            self::$returnData = $info->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
                        