<?php
declare (strict_types=1);

namespace app\api\logic\wechat\sop;

use app\api\logic\ApiLogic;
use app\common\model\wechat\AiWechatContact;
use app\common\model\wechat\sop\AiWechatSopPushMember;
use app\common\traits\WechatTrait;
use think\facade\Db;

class PushMemberLogic extends ApiLogic
{
    use WechatTrait;

    /**
     * 创建推送人员
     */
    public static function createPushMember(array $params): bool
    {
        try {
            Db::startTrans();
            $friends = $params['friend_id'];
            $result = [];
            foreach ($friends as $friend) {
                $friend_detail = AiWechatContact::where('friend_id', $friend)
                                                ->where('wechat_id', $params['wechat_id'])
                                                ->findOrEmpty();
                if ($friend_detail->isEmpty()) {
                    throw new \Exception('该好友未找到，请在（通用设置->微信管理->操作）更新好友列表');
                }
                $pushMember = AiWechatSopPushMember::where('friend_id', $friend)->where('wechat_id', $params['wechat_id'])->findOrEmpty();
                if ($pushMember->isEmpty()) {
                    $pushMemberParams = [
                        'wechat_id'       => $params['wechat_id'],
                        'friend_id'       => $friend,
                        'nickname'        => $friend_detail['nickname'] ?? '',
                        'remark'          => $friend_detail['remark'] ?? '',
                        'avatar'          => $friend_detail['avatar'] ?? '',
                        'flow_id'         => $params['flow_id'],
                        'stage_id'        => $params['stage_id'],
                        'join_flow_time'  => time(),
                        'join_stage_time' => time(),
                        'user_id'         => self::$uid,
                        'status'          => 1,
                    ];
                    $pushMember = AiWechatSopPushMember::create($pushMemberParams);
                } else {
                    $pushMember->nickname = $friend_detail['nickname'] ?? '';
                    $pushMember->remark = $friend_detail['remark'] ?? '';
                    $pushMember->flow_id = $params['flow_id'];
                    $pushMember->stage_id = $params['stage_id'];
                    $pushMember->join_flow_time = time();
                    $pushMember->join_stage_time = time();
                    $pushMember->status = 1;
                    $pushMember->save();
                }
                $result[] = $pushMember->toArray();
            }
            self::$returnData = $result;
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除推送人员
     */
    public static function deletePushMember(array $params): bool
    {
        try {
            Db::startTrans();
            $friends = $params['friend_id'];
            foreach ($friends as $friend) {
                $pushMember = AiWechatSopPushMember::where([
                                                               'wechat_id' => $params['wechat_id'],
                                                               'friend_id' => $friend,
                                                               'flow_id'   => $params['flow_id'],
                                                               'stage_id'  => $params['stage_id'],
                                                               'user_id'   => self::$uid
                                                           ])->find();
                if (!$pushMember) {
                    throw new \Exception('推送人员不存在');
                }
                $pushMember->status = 0;
                $pushMember->flow_id = 0;
                $pushMember->stage_id = 0;
                $pushMember->join_flow_time = 0;
                $pushMember->join_stage_time = 0;
                $pushMember->save();
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
     * 转移推送人员
     */
    public static function transferPushMember(array $params): bool
    {
        try {
            Db::startTrans();
            $friends = $params['friend_id'];
            foreach ($friends as $friend) {
                $pushMember = AiWechatSopPushMember::where([
                                                               'wechat_id' => $params['wechat_id'],
                                                               'friend_id' => $friend,
                                                               'flow_id'   => $params['flow_id'],
                                                               'stage_id'  => $params['stage_id'],
                                                               'user_id'   => self::$uid
                                                           ])->find();
                if (!$pushMember) {
                    throw new \Exception('推送人员不存在');
                }
                $pushMember->status = 1;
                $pushMember->stage_id = $params['to_stage_id'];
                if ($params['to_flow_id'] != $pushMember['flow_id']) {
                    $pushMember->flow_id = $params['to_flow_id'];
                    $pushMember->join_flow_time = time();
                }
                $pushMember->join_stage_time = time();
                $pushMember->save();
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


} 