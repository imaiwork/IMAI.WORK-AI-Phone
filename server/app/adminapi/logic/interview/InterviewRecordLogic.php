<?php

namespace app\adminapi\logic\interview;

use app\common\logic\BaseLogic;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewRecord;
use app\common\service\ConfigService;

class InterviewRecordLogic extends BaseLogic
{

    

    /**
     * 详情
     * @param string $id
     * @return bool
     * @author L
     * @data 2024/6/29 10:30
     */
    public static function detail(string $id): bool
    {
        try {
            $result =  InterviewRecord::where('id', $id)->findOrEmpty()->toArray();

            if (empty($result)) {

                throw new \Exception('面试记录不存在');
            }
            $result['attention'] = json_decode($result['attention']);

            self::$returnData = $result;
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    

   /**
     * @notes 批量删除
     * @param array $recordIds
     * @return int
     */
    public static function deleteRecords($recordIds): int
    {
        try {
            // 确保传入的 ID 数组不为空
            if (empty($recordIds)) {
                throw new \Exception('没有提供要删除的面试记录 ID');
             }
             if(is_array($recordIds)){
                $exists = InterviewRecord::whereIn('id',$recordIds)->value('id');
             }else{
                $exists = InterviewRecord::where(['id'=>$recordIds])->value('id');
             }
             if (!$exists) {
                throw new \Exception('面试记录不存在');
             }
        // 批量删除
            $result = InterviewRecord::destroy($recordIds);
            if ($result > 0) {
                return $result;// 删除成功
            } 
          
            throw new \Exception('删除失败');
          
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }   
}