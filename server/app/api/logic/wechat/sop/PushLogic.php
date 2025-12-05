<?php
declare (strict_types=1);

namespace app\api\logic\wechat\sop;

use app\api\lists\sop\PushLists;
use app\api\logic\ApiLogic;
use app\common\model\wechat\sop\AiWechatSopFlow;
use app\common\model\wechat\sop\AiWechatSopPush;
use app\common\model\wechat\sop\AiWechatSopPushContent;
use app\common\model\wechat\sop\AiWechatSopPushLog;
use app\common\model\wechat\sop\AiWechatSopPushMember;
use app\common\model\wechat\sop\AiWechatSopPushTime;
use app\common\model\wechat\sop\AiWechatSopSubStage;
use think\facade\Db;

class PushLogic extends ApiLogic
{
    /**
     * 创建推送
     */
    public static function createPush(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['push_day'] = date('Y-m-d');

            //群发任务
            if (isset($params['push_type']) && $params['push_type'] == 0) {
                $params['type'] = 0;
            } else {
                $params['type'] = -1;
            }
            $push = AiWechatSopPush::create($params);
            if (!$push) {
                throw new \Exception('创建推送失败');
            }

            self::$returnData = $push->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新推送
     */
    public static function updatePush(array $params): bool
    {
        try {

            $push = AiWechatSopPush::where(['id' => $params['id'], 'user_id' => self::$uid])->find();
            if (!$push) {
                self::setError('推送数据不存在');
                return false;
            }
            $push->status = 1;
            $push->user_id = self::$uid;
            $push->type = $params['type'] ?? 0;
            if (in_array($params['type'], [1, 2])) {
                if ($params['type'] == 1) {
                    $where = ['id' => (int)$params['flow_id'], 'user_id' => self::$uid];
                    $flow = AiWechatSopFlow::where($where)->find();
                    if (!$flow) {
                        self::setError('流程数据不存在');
                        return false;
                    }
                    unset($params['stage_id']);
                    $push->flow_id = $params['flow_id'];
                }
                if ($params['type'] == 2) {
                    $where = ['id' => $params['stage_id'], 'flow_id' => $params['flow_id'], 'user_id' => self::$uid];
                    $stage = AiWechatSopSubStage::where($where)->find();
                    if (!$stage) {
                        self::setError('阶段数据不存在');
                        return false;
                    }
                    $push->flow_id = $params['flow_id'];
                    $push->stage_id = $params['stage_id'];
                }
            }
            if (isset($params['push_day']) && isset($params['type']) && $params['type'] == 4){
                if ($params['push_day'] != $push->push_day){
                    AiWechatSopPushTime::destroy(['push_id' => $push['id']]);
                    AiWechatSopPushContent::destroy(['push_id' => $push['id']]);
                }
            }
            if (isset($params['push_name'])) {
                $push->push_name = $params['push_name'];
            }
            $push->save($params);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除推送
     */
    public static function deletePush(array $params): bool
    {
        try {
            $push = AiWechatSopPush::where(['id' => $params['id'], 'user_id' => self::$uid])->find();
            if (!$push) {
                self::setError('数据不存在');
                return false;
            }

            $push->delete_time = time();
            $push->save();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取推送列表
     */
    public static function getPushLists(): array
    {
        return (new PushLists())->lists();
    }

    /**
     * 推送详情
     */
    public static function detail(array $params): bool
    {
        $push = AiWechatSopPush::where(['id' => $params['id'], 'user_id' => self::$uid])
                               ->field('id,push_name,push_day,status,push_type,type,all_day,user_id,flow_id,stage_id,is_publish_edit')
                               ->find();
        if (!$push) {
            self::setError('推送数据不存在');
            return false;
        }
        $push = $push->toArray();
        $push_time_list = AiWechatSopPushTime::where(['push_id' => $push['id'], 'user_id' => self::$uid])
                                             ->field('id as push_time_id,order_day,push_time,push_real_day')
                                             ->order('order_day asc')
                                             ->order('push_time asc')
                                             ->select()
                                             ->each(function ($item) {
                                                 $total = AiWechatSopPushContent::where('push_time_id', $item['push_time_id'])
                                                                                ->field('id as content_id,content,sort')
                                                                                ->findOrEmpty();
                                                 if ($total->isEmpty()) {
                                                     $item['content_list'] = [];
                                                 } else {
//                                                     $item['content_list'] = $total;
                                                     $item['content_id'] = $total['content_id'];
                                                     $item['content_list'] = json_decode($total['content'], true);
                                                 }
                                             })
                                             ->toArray();
        $arrs = [];
        $arr = [];
        foreach ($push_time_list as $val) {
            $arrs[$val['order_day']][] = $val;
        }
        $n = 0;
        foreach ($arrs as $k => $v){
            $arr[$n]['day'] = $k;
            $arr[$n]['list'] = $v;
            $n++;
        }
        $push['push_time_list'] = $arr;
        $push['flow_id'] = $push['flow_id'] == '' ? '' : json_decode($push['flow_id'], true);
        $push['stage_id'] = $push['stage_id'] === 0 ? '' : $push['stage_id'];
        self::$returnData = $push;
        return true;
    }


    /**
     * 更新群发任务
     */
    public static function updateSequencePush(array $params): bool
    {
        try {

            $push = AiWechatSopPush::where([
                                               'id'        => $params['id'],
                                               'user_id'   => self::$uid,
                                               'push_type' => AiWechatSopPush::PUSH_TYPE_CONTENT_SEQUENCE
                                           ])->find();
            if (!$push) {
                self::setError('群发任务不存在');
                return false;
            }

            if ($push->type == 1) {
                self::setError('群发任务状态错误');
                return false;
            }
            $push->status = 1;
            $push->user_id = self::$uid;

            if (isset($params['push_name'])) {
                $push->push_name = $params['push_name'];
            }

            $push->save($params);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 选择推送流程人员
     */
    public static function choiceFlow(array $params): bool
    {
        try {
            Db::startTrans();
            $push = AiWechatSopPush::where([
                                               'id'      => $params['id'],
                                               'user_id' => self::$uid,
                                           ])->find();
            if (!$push) {
                throw new \Exception('推送ID不存在');
            }
            $flow_ids = $params['flow_id'];
            $flows = AiWechatSopFlow::whereIn('id', $flow_ids)
                                    ->where('user_id', self::$uid)
                                    ->select();
            if (count($flows) != count($flow_ids)) {
                throw new \Exception('存在无效的流程ID');
            }

            //群发任务时，批量新增推送记录
            if ($push->type === 0 && $push->push_type === 0) {
                $members = AiWechatSopPushMember::whereIn('flow_id', $flow_ids)
                                                ->where('user_id', self::$uid)
                                                ->select()
                                                ->toArray();
                $contents = AiWechatSopPushContent::alias('c')
                                                  ->join('ai_wechat_sop_push_time t', 'c.push_time_id = t.id')
                                                  ->where('c.push_id', $push['id'])
                                                  ->where('c.user_id', self::$uid)
                                                  ->field('c.id as content_id, c.content, c.push_time_id, t.order_day, t.push_day, t.push_real_day, t.push_time')
                                                  ->select()
                                                  ->toArray();
                if (empty($contents)) {
                    throw new \Exception('没有可用的推送内容');
                }
                $insert_data = [];
                foreach ($flow_ids as $flow_id) {
                    // 获取当前Flow的Members
                    $current_members = array_filter($members, function ($item) use ($flow_id) {
                        return isset($item['flow_id']) && $item['flow_id'] == $flow_id;
                    });
                    if (empty($current_members)) {
                        continue;
                    }

                    // 为每个Member生成所有Content的记录
                    foreach ($current_members as $member) {
                        foreach ($contents as $content) {
                            $insert_data[] = [
                                'member_id'      => $member['id'],
                                'user_id'        => self::$uid,
                                'push_id'        => $push['id'],
                                'content_id'     => $content['content_id'],
                                'content'        => $content['content'],
                                'push_real_day'  => $content['push_real_day'],
                                'push_real_time' => $content['push_time'],
                                'status'         => 0,
                                'create_time'    => time()
                            ];
                        }
                    }
                }
                // 批量插入
                if (!empty($insert_data)) {
                    AiWechatSopPushLog::insertAll($insert_data);
                }
                $push->is_publish_edit = 1;//群发任务设置完成后禁止编辑
            }

            $push->flow_id = json_encode($flow_ids);
            $push->status = 2;//开启
            $push->save();
            Db::commit();
            $push->flow_id = $flow_ids;
            self::$returnData = $push->toArray();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

}