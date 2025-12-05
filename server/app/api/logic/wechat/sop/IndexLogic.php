<?php
declare (strict_types=1);

namespace app\api\logic\wechat\sop;

use app\api\logic\ApiLogic;
use app\common\model\wechat\sop\AiWechatSopFlow;
use app\common\model\wechat\sop\AiWechatSopPushLog;
use app\common\model\wechat\sop\AiWechatSopPushMember;
use app\common\model\wechat\sop\AiWechatSopSubStage;

class IndexLogic extends ApiLogic
{
    public static function getFriendFlow(array $params)
    {
        try {
            $flow = AiWechatSopFlow::alias('f')
                                   ->join('ai_wechat_sop_push_member m', 'f.id = m.flow_id')
                                   ->field('f.*,m.wechat_id,m.friend_id,m.stage_id,m.join_flow_time,m.join_stage_time')
                                   ->where('f.user_id', self::$uid)
                                   ->where('m.wechat_id', $params['wechat_id'])
                                   ->where('m.friend_id', $params['friend_id'])
                                   ->findOrEmpty();
            if ($flow->isEmpty()) {
                self::$returnData = [];
                return true;
            }
            $flow['stay_flow_day'] = $flow['join_flow_time'] == 0 ? 0 : (strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', $flow['join_flow_time']))) / 86400;
            $flow['stay_stage_day'] = $flow['join_stage_time'] == 0 ? 0 : (strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', $flow['join_stage_time']))) / 86400;
            $flow['stage'] = AiWechatSopSubStage::where('flow_id', $flow['id'])
                                                ->field('id as stage_id,sub_stage_name,status')
                                                ->select()
                                                ->toArray();
            $flow['join_flow_time'] = date('Y-m-d H:i:s', $flow['join_flow_time']);
            $flow['join_stage_time'] = date('Y-m-d H:i:s', $flow['join_stage_time']);
            self::$returnData = $flow->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getFriendPushLog(array $params)
    {
        try {
            $results = [];
            $result = [];
            $member = AiWechatSopPushMember::where('wechat_id', $params['wechat_id'])
                                           ->where('friend_id', $params['friend_id'])
                                           ->where('user_id', self::$uid)
                                           ->findOrEmpty();
            if ($member->isEmpty()) {
                self::$returnData = [];
                return true;
            }
            $logs = AiWechatSopPushLog::alias('l')
                                      ->join('ai_wechat_sop_push p', 'l.push_id = p.id')
                                      ->field('l.*,p.push_name,p.push_type')
                                      ->where('p.push_type', $params['push_type'])
                                      ->where('l.member_id', $member['id'])
                                      ->where('l.user_id', self::$uid)
                                      ->order('l.push_real_day', 'desc')
                                      ->order('l.push_real_time', 'asc')
                                      ->select();
            if (empty($logs)) {
                self::$returnData = [];
                return true;
            }
            $logs = $logs->toArray();
            foreach ($logs as $log) {
                $date = $log['push_real_day'];
                if (empty($log['content'])) {
                    continue;
                }
                $log['content'] = json_decode($log['content'], true);
                $log['content_num'] = count($log['content']);
                $results[$date][] = $log;
            }
            $num = 0;
            foreach ($results as $key => $val) {
                $result[$num]['date'] = $key;
                $result[$num]['log'] = $val;
                $num++;
            }
            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function deleteFriendPushLog(array $params)
    {
        try {
            $log = AiWechatSopPushLog::where('id', $params['id'])->findOrEmpty();
            if ($log->isEmpty()) {
                self::setError('推送记录不存在');
                return false;
            }
            if ($log['status'] === 0) {
                self::setError('只能删除已执行的记录');
                return false;
            }
            $log->delete();
            self::$returnData = [];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getPushLogList(array $params)
    {
        try {
            $page_no = $params['page_no'] ?? 1;
            $page_size = $params['page_size'] ?? 10;
            if (!isset($params['push_id'])){
                self::setError('推送id有误');
                return false;
            }
            $where = [];
            if (isset($params['nickname'])){
                $where[] = ['m.nickname', 'like', "%{$params['nickname']}%"];
            }
            $logs = AiWechatSopPushLog::alias('l')
                                      ->join('ai_wechat_sop_push_member m', 'l.member_id = m.id')
                                      ->field('l.id,l.create_time as push_time,l.update_time as push_real_time,l.status,l.content_id,l.content,m.wechat_id,m.friend_id,m.nickname,m.remark,m.avatar')
                                      ->where('l.push_id', $params['push_id'])
                                      ->where($where)
                                      ->order('l.push_real_day', 'desc')
                                      ->order('l.push_real_time', 'desc')
                                      ->limit((int)($page_no - 1) * $page_size, (int)$page_size)
                                      ->select();
            $common = AiWechatSopPushLog::alias('l')
                                        ->join('ai_wechat_sop_push_member m', 'l.member_id = m.id')
                                        ->field('l.id,l.status')
                                        ->where('l.push_id', $params['push_id']);
            $friend_num = (clone $common)->group('l.member_id')->count();
            $wait_num = (clone $common)->where('l.status', 0)->count();
            $success_num = (clone $common)->where('l.status', 1)->count();
            $failed_num = (clone $common)->where('l.status', 2)->count();

            if (empty($logs)) {
                self::$returnData = [
                    "lists"     => [],
                    "count"     => 0,
                    "page_no"   => $page_no,
                    "page_size" => $page_size,
                ];
                return true;
            }
            $logs = $logs->toArray();
            foreach ($logs as $key => $log) {
                $logs[$key]['push_time'] = $log['push_time'] > 0 ? date('Y-m-d H:i:s', $log['push_time']) : 0;
                $logs[$key]['push_real_time'] = $log['push_real_time'] > 0 ? date('Y-m-d H:i:s', $log['push_real_time']) : '未执行';
                $logs[$key]['content'] = !empty($log['content']) ? json_decode($log['content'], true) : [];
            }
            self::$returnData = [
                "lists"       => $logs,
                "count"       => AiWechatSopPushLog::alias('l')
                                                   ->join('ai_wechat_sop_push_member m', 'l.member_id = m.id')
                                                   ->where('l.push_id', $params['push_id'])
                                                   ->where($where)
                                                   ->count(),
                "page_no"     => $page_no,
                "page_size"   => $page_size,
                'success_num' => $success_num,
                'failed_num'  => $failed_num,
                'friend_num'  => $friend_num,
                'wait_num'    => $wait_num
            ];

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
} 