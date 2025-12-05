<?php

declare(strict_types=1);

namespace app\api\logic\wechat\sop;

use app\api\logic\ApiLogic;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatContact;
use app\common\model\wechat\sop\AiWechatSopFlow;
use app\common\model\wechat\sop\AiWechatSopPushMember;
use app\common\model\wechat\sop\AiWechatSopStageTrigger;
use app\common\model\wechat\sop\AiWechatSopSubStage;

class StageLogic extends ApiLogic
{
    /**
     * 创建子阶段
     */
    public static function createStage(array $params): bool
    {
        try {
            // 添加用户ID
            $params['user_id'] = self::$uid;
            // 设置为其他阶段
            $params['status'] = AiWechatSopSubStage::STAGE_STATUS_OTHER;
            // 如果没有传入sort，设置默认值
            $params['sort'] = $params['sort'] ?? 0;
            $params['create_time'] = $params['update_time'] = time();

            $subStage = AiWechatSopSubStage::create($params);
            if (!$subStage) {
                throw new \Exception('创建子阶段失败');
            }

            self::$returnData = $subStage->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * 更新子阶段
     */
    public static function updateStage(array $params): bool
    {
        try {
            $subStage = AiWechatSopSubStage::where('id', $params['id'])->find();
            if (!$subStage) {
                self::setError('子阶段不存在');
                return false;
            }

            // 验证是否是自己的子阶段
            if ($subStage->user_id !== self::$uid) {
                self::setError('只能操作自己创建的阶段');
                return false;
            }

            // 如果是关键阶段或警示阶段，只允许更新名称和排序
            if ($subStage->isProtectedStage() && isset($params['status'])) {
                self::setError('关键阶段和警示阶段不能修改状态');
                return false;
            }

            // 检查阶段名称是否有实际变化
            //            if (isset($params['sub_stage_name'])) {
            //                $newName = trim((string)$params['sub_stage_name']);
            //                $oldName = trim((string)$subStage->sub_stage_name);
            //
            //                if ($newName === $oldName) {
            //                    self::setError('阶段名称没有变化');
            //                    return false;
            //                }
            //            }

            $params['update_time'] = time();
            $subStage->save($params);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除子阶段
     */
    public static function deleteStage(array $params): bool
    {
        try {
            $subStage = AiWechatSopSubStage::where('id', $params['id'])->find();
            if (!$subStage) {
                self::setError('子阶段不存在');
                return false;
            }

            // 验证是否是自己的子阶段
            if ($subStage->user_id !== self::$uid) {
                self::setError('只能删除自己创建的阶段');
                return false;
            }

            // 检查是否为关键阶段或警示阶段
            if ($subStage->isProtectedStage()) {
                self::setError('关键阶段和警示阶段不能删除');
                return false;
            }

            $subStage->delete_time = time();
            $subStage->save();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取子阶段列表
     */
    public static function getStageList(array $params): bool
    {
        try {
            // 验证流程是否属于当前用户
            $flow = AiWechatSopFlow::where('id', $params['flow_id'])->find();
            if (!$flow) {
                self::setError('流程不存在');
                return false;
            }

            if ($flow->user_id !== self::$uid) {
                self::setError('只能查看自己创建的流程的阶段');
                return false;
            }

            $list = AiWechatSopSubStage::where([
                ['flow_id', '=', $params['flow_id']],
                ['delete_time', 'null', null]
            ])
                ->order('sort desc, id desc')
                ->select()
                ->toArray();

            self::$returnData = $list;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 创建触发条件
     */
    public static function createTrigger(array $params): bool
    {
        try {
            // 验证阶段是否属于当前用户
            $stage = AiWechatSopSubStage::where([
                'id'      => $params['stage_id'],
                'flow_id' => $params['flow_id'],
                'user_id' => self::$uid
            ])->find();
            if (!$stage) {
                self::setError('只能为自己的阶段添加触发条件');
                return false;
            }

            // 根据匹配类型构建查询条件

            //            $exists = AiWechatSopStageTrigger::where('user_id',self::$uid)->where('match_type',1)->where('action_type',1)->find();
            //            if ($exists) {
            //                self::setError('该动作触发条件只可存在于一个阶段中');
            //                return false;
            //            }

            $params['user_id'] = self::$uid;
            $params['create_time'] = $params['update_time'] = time();

            $trigger = AiWechatSopStageTrigger::create($params);
            self::$returnData = $trigger->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新触发条件
     */
    public static function updateTrigger(array $params): bool
    {
        try {
            $trigger = AiWechatSopStageTrigger::where([
                'id'      => $params['trigger_id'],
                'user_id' => self::$uid
            ])->find();
            if (!$trigger) {
                self::setError('触发条件不存在');
                return false;
            }

            // 验证是否是自己的触发条件
            if ($trigger->user_id !== self::$uid) {
                self::setError('只能修改自己创建的触发条件');
                return false;
            }

            //            $exists = AiWechatSopStageTrigger::where('user_id',self::$uid)->where('match_type',1)->where('action_type',1)->find();
            //            if ($exists) {
            //                self::setError('该动作触发条件只可存在于一个阶段中');
            //                return false;
            //            }

            $params['update_time'] = time();
            $trigger->save($params);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除触发条件
     */
    public static function deleteTrigger(array $params): bool
    {
        try {
            $trigger = AiWechatSopStageTrigger::where('id', $params['id'])->find();
            if (!$trigger) {
                self::setError('触发条件不存在');
                return false;
            }

            // 验证是否是自己的触发条件
            if ($trigger->user_id !== self::$uid) {
                self::setError('只能删除自己创建的触发条件');
                return false;
            }

            $trigger->delete_time = time();
            $trigger->save();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * 是否触发条件
     */
    public static function sopStagetrigger($params): bool
    {
        try {
            //聊天内容匹配
            $chat_match_object = $params['chat_object']; //1 AI回复，2 客户回复
            $user_id = AiWechat::where('wechat_id', $params['wechat_id'])->value('user_id');

            //模糊匹配
            $stages = AiWechatSopStageTrigger::where('match_type', 2) //匹配类型 0-无 1-动作匹配 2-聊天内容匹配
                ->where('chat_match_mode', 1) //聊天匹配模式 1-模糊匹配 2-精确匹配
                ->where('status', 1) //状态 0-禁用 1-启用
                ->where('user_id', $user_id)
                ->where('chat_match_object', $chat_match_object)
                ->select();
            if ($stages) {
                $stages = $stages->toArray();
                foreach ($stages as $stage) {
                    if (str_contains($params['content'], $stage['chat_keywords'])) {
                        $member_flow = $stage['flow_id'];
                        $member_stage = $stage['stage_id'];
                    }
                }
            }

            //精准匹配
            $true_stage = AiWechatSopStageTrigger::where('match_type', 2) //匹配类型 0-无 1-动作匹配 2-聊天内容匹配
                ->where('chat_match_mode', 2) //聊天匹配模式 1-模糊匹配 2-精确匹配
                ->where('status', 1) //状态 0-禁用 1-启用
                ->where('user_id', $user_id)
                ->where('chat_match_object', $chat_match_object)
                ->where('chat_keywords', $params['content'])
                ->findOrEmpty();
            if (!$true_stage->isEmpty()) {
                $member_flow = $true_stage['flow_id'];
                $member_stage = $true_stage['stage_id'];
            }

            if (isset($member_flow) && isset($member_stage)) {
                $member = AiWechatSopPushMember::where('wechat_id', $params['wechat_id'])
                    ->where('friend_id', $params['friend_id'])
                    ->where('user_id', $user_id)
                    ->findOrEmpty();
                //好友详情
                $friend_detail = AiWechatContact::where('friend_id', $params['friend_id'])
                    ->where('wechat_id', $params['wechat_id'])
                    ->findOrEmpty();
                if ($friend_detail->isEmpty()) {
                    return false;
                }
                $data = [
                    'flow_id'         => $member_flow,
                    'stage_id'        => $member_stage,
                    'join_flow_time'  => time(),
                    'join_stage_time' => time(),
                    'status'          => 1
                ];
                if ($member->isEmpty()) {
                    $data['friend_id'] = $params['friend_id'];
                    $data['wechat_id'] = $params['wechat_id'];
                    $data['user_id'] = $user_id;
                    $data['nickname'] = $friend_detail['nickname'] ?? '';
                    $data['remark'] = $friend_detail['remark'] ?? '';
                    $data['avatar'] = $friend_detail['avatar'] ?? '';
                    AiWechatSopPushMember::create($data);
                } else {
                    AiWechatSopPushMember::where('id', $member['id'])->update($data);
                }
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 是否触发动作条件
     */
    public static function sopActionStagetrigger($params): bool
    {
        try {
            //动作匹配
            $user_id = AiWechat::where('wechat_id', $params['wechat_id'])->value('user_id');
            $true_stage = AiWechatSopStageTrigger::where('match_type', 1) //匹配类型 0-无 1-动作匹配 2-聊天内容匹配
                ->where('action_type', 1) //动作类型 1-刚加好友
                ->where('status', 1) //状态 0-禁用 1-启用
                ->where('user_id', $user_id)
                ->findOrEmpty();
            if (!$true_stage->isEmpty()) {
                $member_flow = $true_stage['flow_id'];
                $member_stage = $true_stage['stage_id'];
            }

            if (isset($member_flow) && isset($member_stage)) {
                $member = AiWechatSopPushMember::where('wechat_id', $params['wechat_id'])
                    ->where('friend_id', $params['friend_id'])
                    ->where('user_id', $user_id)
                    ->findOrEmpty();
                if ($member->isEmpty()) {
                    $insert = [
                        'wechat_id' => $params['wechat_id'],
                        'friend_id' => $params['friend_id'],
                        'user_id' => $user_id,
                        'avatar' => $params['avatar'],
                        'nickname' => $params['nickname'],
                        'remark' => $params['remark']
                    ];
                    $member = AiWechatSopPushMember::create($insert);
                }

                $data = [
                    'flow_id'         => $member_flow,
                    'stage_id'        => $member_stage,
                    'join_flow_time'  => time(),
                    'join_stage_time' => time(),
                    'status'          => 1
                ];
                AiWechatSopPushMember::where('id', $member->id)->update($data);
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
