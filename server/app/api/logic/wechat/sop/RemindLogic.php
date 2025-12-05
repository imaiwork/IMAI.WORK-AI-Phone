<?php
declare (strict_types = 1);

namespace app\api\logic\wechat\sop;

use app\api\logic\ApiLogic;
use app\common\model\wechat\sop\AiWechatSopSubFlowRemind;
use app\common\model\wechat\sop\AiWechatSopSubStage;

class RemindLogic extends ApiLogic
{
    /**
     * 创建跟进提醒
     */
    public static function createRemind(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            

            $subStage = AiWechatSopSubStage::where([
                'id' => $params['stage_id'],
                'flow_id' => $params['flow_id'],
                'user_id' => self::$uid
            ])->find();

            if (!$subStage) {   
                throw new \Exception('子阶段不存在');
            }
          
            
            $remind = AiWechatSopSubFlowRemind::create($params);
            if (!$remind) {
                throw new \Exception('创建跟进提醒失败');
            }

            self::$returnData = $remind->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新跟进提醒
     */
    public static function updateRemind(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $remind = AiWechatSopSubFlowRemind::where(['id' => $params['remind_id'], 'user_id' => self::$uid])->find();
            if (!$remind) {
                self::setError('数据不存在');
                return false;
            }

            $params['update_time'] = time();
            $remind->save($params);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除跟进提醒
     */
    public static function deleteRemind(array $params): bool
    {
        try {
            $remind = AiWechatSopSubFlowRemind::where(['id' => $params['id'], 'user_id' => self::$uid])->find();
            if (!$remind) {
                self::setError('数据不存在');
                return false;
            }

            $remind->delete_time = time();
            $remind->save();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


} 