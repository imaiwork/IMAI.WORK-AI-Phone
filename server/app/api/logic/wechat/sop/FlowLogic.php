<?php
declare (strict_types=1);

namespace app\api\logic\wechat\sop;

use app\api\logic\ApiLogic;
use app\common\model\wechat\sop\AiWechatSopFlow;
use app\common\model\wechat\sop\AiWechatSopPush;
use app\common\model\wechat\sop\AiWechatSopPushMember;
use app\common\model\wechat\sop\AiWechatSopStageTrigger;
use app\common\model\wechat\sop\AiWechatSopSubFlowRemind;
use app\common\model\wechat\sop\AiWechatSopSubStage;
use think\facade\Db;

class FlowLogic extends ApiLogic
{
    /**
     * 创建流程
     */
    public static function createFlow(array $params): bool
    {
        Db::startTrans();
        try {
            // 添加用户ID
            $params['user_id'] = self::$uid;
            $params['create_time'] = $params['update_time'] = time();

            // 创建流程
            $flow = AiWechatSopFlow::create($params);
            if (!$flow) {
                throw new \Exception('流程创建失败');
            }

            // 创建默认子阶段
            foreach (AiWechatSopSubStage::DEFAULT_SUB_STAGES as $stage) {
                $stageData = array_merge($stage, [
                    'flow_id'     => $flow->id,
                    'user_id'     => self::$uid,
                    'create_time' => time(),
                    'update_time' => time()
                ]);

                $result = AiWechatSopSubStage::create($stageData);
                if (!$result) {
                    throw new \Exception('子阶段创建失败');
                }
            }

            // 所有操作成功后提交事务
            Db::commit();
            self::$returnData = $flow->toArray();
            return true;
        } catch (\Exception $e) {
            // 任何异常都回滚事务
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新流程
     */
    public static function updateFlow(array $params): bool
    {
        Db::startTrans();
        try {
            $flow = AiWechatSopFlow::where('id', $params['id'])->find();
            if (!$flow) {
                self::setError('流程不存在');
                return false;
            }

            // 验证是否是自己的流程
            if ($flow->user_id !== self::$uid) {
                self::setError('只能操作自己创建的流程');
                return false;
            }

            if (isset($params['status']) && !in_array($params['status'], [
                    AiWechatSopFlow::STATUS_CLOSE,
                    AiWechatSopFlow::STATUS_OPEN
                ])) {
                self::setError('状态值不正确');
                return false;
            }

            $params['update_time'] = time();

            // 更新流程
            if (!$flow->save($params)) {
                throw new \Exception('流程更新失败');
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除流程（软删除）
     */
    public static function deleteFlow(array $params): bool
    {
        Db::startTrans();
        try {
            $flow = AiWechatSopFlow::where('id', $params['id'])->find();
            if (!$flow) {
                self::setError('流程不存在');
                return false;
            }

            // 验证是否是自己的流程
            if ($flow->user_id !== self::$uid) {
                self::setError('只能删除自己创建的流程');
                return false;
            }

            $deleteTime = time();

            // 同步删除所有子阶段
            $result = AiWechatSopSubStage::where('flow_id', $params['id'])
                                         ->whereNull('delete_time')
                                         ->update([
                                                      'delete_time' => $deleteTime,
                                                      'update_time' => $deleteTime
                                                  ]);

            if ($result === false) {
                throw new \Exception('子阶段删除失败');
            }

            // 删除流程
            $flow->delete_time = $deleteTime;
            $flow->update_time = $deleteTime;
            if (!$flow->save()) {
                throw new \Exception('流程删除失败');
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取流程详情，包括子阶段及其触发条件和跟进提醒个数
     */
    public static function getFlowDetail(string $flow_id)
    {
        $flow = AiWechatSopFlow::where(['id' => $flow_id, 'user_id' => self::$uid])->findOrEmpty();
        if (!$flow) {
            self::setError('流程不存在');
            return false;
        }
        $result = $flow->toArray();
        // 查询子阶段
        $subStages = AiWechatSopSubStage::where(['flow_id' => $flow_id, 'user_id' => self::$uid])
                                        ->order('sort desc')
                                        ->select();

        $result['sub_stage_list'] = [];
        foreach ($subStages as $subStage) {
            // 统计触发条件个数
            $triggerlist = AiWechatSopStageTrigger::where(['stage_id' => $subStage->id, 'status' => 1])
                                                  ->select()->toArray();
            // 统计跟进提醒个数
            $remindlist = AiWechatSopSubFlowRemind::where('stage_id', $subStage->id)
                                                  ->select()->toArray();

            $result['sub_stage_list'][] = [
                'sub_stage_id'   => $subStage->id,
                'status'         => $subStage->status,
                'status_text'    => AiWechatSopSubStage::getStatusText($subStage->status),
                'sort'           => $subStage->sort,
                'sub_stage_name' => $subStage->sub_stage_name,
                'trigger_count'  => count($triggerlist),
                'remind_count'   => count($remindlist),
                'triggerlist'    => $triggerlist,
                'remindlist'     => $remindlist,
            ];
        }

        return $result;
    }

    public static function getDashboardDetail(array $params)
    {
        try {
            $flow = AiWechatSopFlow::where(['id' => $params['flow_id'], 'user_id' => self::$uid])->findOrEmpty();
            if ($flow->isEmpty()) {
                self::setError('流程不存在');
                return false;
            }
            $result = $flow->toArray();
            $subStages = AiWechatSopSubStage::where(['flow_id' => $params['flow_id'], 'user_id' => self::$uid])
                                            ->field('id as stage_id,sub_stage_name,status')
                                            ->order('sort desc')
                                            ->select()
                                            ->each(function ($item) use ($params) {
                                                $item['members'] = AiWechatSopPushMember::field('friend_id,nickname,remark,avatar,status,create_time,update_time,delete_time')
                                                                                        ->where('wechat_id', $params['wechat_id'])
                                                                                        ->where('stage_id', $item->stage_id)
                                                                                        ->where('status', 1)
                                                                                        ->select();
                                            })->toArray();
            $result['sub_stage_list'] = $subStages;
            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}