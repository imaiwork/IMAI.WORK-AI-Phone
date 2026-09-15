<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersonaWechatInteractionConfig;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\sv\SvDeviceTakeOverSpeechHistory;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvWechatStrategy;
use app\common\enum\DeviceEnum;
use think\facade\Db;


/**
 * 设备接管任务逻辑
 * Class TakeOverLogic    
 * @package app\api\logic\device
 */
class TakeOverLogic extends ApiLogic
{
    public static function add($params)
    {
        Db::startTrans();
        try {
            //校验只能选择一种平台
            self::checkAutoDevice($params);
            TaskLogic::checkAccounts($params['accounts']);

            $is_overlap = $params['task_exec_type'] ?? 0;
            if ((int)$is_overlap === 1) {
                \app\api\logic\device\TaskLogic::updateTaskStatusByIds($params['task_ids']);
                $params['time_config'] = [
                    date('H:i', time()) . '-' . date('H:i', (time() + (60 * (int)$params['minutes']))),
                ];
                $params['custom_date'] = [
                    date('Y-m-d', time())
                ];
                unset($params['task_ids']);
            }

            $times = TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), $params['task_frep'], $params['custom_date']);

            $params['user_id'] = self::$uid;
            $accounts = $params['accounts'];
            $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            $taskType = [
                1 => '回复评论',
                2 => '回复私信',
                3 => '全部回复',
            ];
            $params['task_type'] = $params['task_type'] ?? 2;
            $params['task_name'] =  $params['task_name'] ??  '设备接管' . $taskType[$params['task_type']] . '任务' . date('mdHis', time());
            $params['task_name'] =  $params['task_name'] ??  '设备接管任务' . date('mdHis', time());

            //print_r($params);die;
            $task = SvDeviceTakeOverTask::create($params);
            $allTaskInstall = [];
            foreach ($accounts as $account) {
                $find = SvAccount::where('account', $account['account'])->where('user_id', self::$uid)->limit(1)->find()->toArray();
                $account = array_merge($account, $find);

                foreach ($times as $time) {
                    if ((int)$is_overlap === 0) {
                        list($isOverlap, $lap) = TaskLogic::isTaskTimeOverlapping($account['device_code'], DeviceEnum::TASK_TYPE_TAKEOVER, $time['start_time'], $time['end_time'], self::$uid);
                        if (!$isOverlap) {
                            $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                            $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                            throw new \Exception($msg);
                        }
                    }


                    $row = SvDeviceTakeOverTaskAccount::create([
                        'take_over_id' => $task->id,
                        'user_id' => self::$uid,
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'nickname' => $account['nickname'],
                        'avatar' => $account['avatar'],
                        'device_code' => $account['device_code'],
                        'robot_id' => $params['message_robot_id'] ?? 0,
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'status' => 0,
                    ]);

                    $task_name = ((int)$params['task_type'] === 1 && (int)$account['type'] == 1) ? '评论点赞任务'.date('mdHis', time()) : $params['task_name'];
                    $task_scene = (int)$params['task_type'] === 1 ? ((int)$account['type'] == 1 ? DeviceEnum::AUTO_TASK_SCENE_COMMENT_LIKE : DeviceEnum::AUTO_TASK_SCENE_COMMENT_TAKE_OVER) : DeviceEnum::AUTO_TASK_SCENE_TAKE_OVER;  
                    $task_type = ((int)$params['task_type'] === 1 && (int)$account['type'] == 1) ? DeviceEnum::TASK_TYPE_SPH_THUMB : DeviceEnum::TASK_TYPE_TAKEOVER;
                    $source = ((int)$params['task_type'] === 1 && (int)$account['type'] == 1) ? DeviceEnum::TASK_SOURCE_SPH_THUMB : DeviceEnum::TASK_SOURCE_TAKEOVER;
                    array_push($allTaskInstall, [
                        'user_id' => self::$uid,
                        'device_code' => $account['device_code'],
                        'task_type' => $task_type,
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'nickname' => $account['nickname'],
                        'avatar' => $account['avatar'],
                        'task_name' => $task_name,
                        'status' => 0,
                        'day' => date('Y-m-d', $time['start_time']),
                        'time_config' => $params['time_config'],
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'sub_task_id' => $row->id,
                        'task_scene' => $task_scene,
                        'source' => $source, //sv_device_take_over_task_account
                        'create_time' => time(),
                    ]);

                    \app\api\logic\device\TaskLogic::updateWechatRpaTaskTime($account['device_code'], $time['start_time']);
                }
            }
            //print_r($allTaskInstall);die;
            TaskLogic::add($allTaskInstall);

            self::createSpeechHistory($params);

            Db::commit();
            self::$returnData = $task->toArray();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            //print_r($th->__toString());die;
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function createSpeechHistory($params)
    {
        $insertData = [];
        if ((int)$params['task_type'] === 1) {
            foreach ($params['comment_speech'] as $keyword) {
                $find = SvDeviceTakeOverSpeechHistory::where('user_id', self::$uid)->where('keyword', $keyword)->where('type', 1)->findOrEmpty();
                if ($find->isEmpty()) {
                    $insertData[] = [
                        'user_id' => self::$uid,
                        'type' => 1,
                        'keyword' => $keyword,
                        'create_time' => time(),
                    ];
                }
            }
        } elseif ((int)$params['task_type'] === 2) {
            foreach ($params['message_speech'] as $keyword) {
                $find = SvDeviceTakeOverSpeechHistory::where('user_id', self::$uid)->where('keyword', $keyword)->where('type', 2)->findOrEmpty();
                if ($find->isEmpty()) {
                    $insertData[] = [
                        'user_id' => self::$uid,
                        'type' => 2,
                        'keyword' => $keyword,
                        'create_time' => time(),
                    ];
                }
            }
        }

        if (!empty($insertData)) {
            SvDeviceTakeOverSpeechHistory::insertAll($insertData);
        }
    }


    public static function speechDelete($params)
    {
        try {
            if (isset($params['id'])) {
                $find = SvDeviceTakeOverSpeechHistory::where('id', $params['id'])->findOrEmpty();
                if ($find->isEmpty()) {
                    throw new \Exception('固定话术不存在');
                }
                $find->delete();
            }

            if (isset($params['type'])) {
                SvDeviceTakeOverSpeechHistory::where('type', $params['type'])->select()->delete();
            }

            return true;
        } catch (\Throwable $th) {
            //throw $th;
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 读取当前用户最新个微策略的加群触发关键词
     */
    public static function getGroupTriggerKeywords(): bool
    {
        try {
            $strategy = SvWechatStrategy::where('user_id', self::$uid)
                ->order('id', 'desc')
                ->findOrEmpty();

            $keywords = AiPersonaWechatInteractionConfig::getDefaultGroupTriggerKeywords();
            if (!$strategy->isEmpty()) {
                $raw = $strategy->getData('group_trigger_keywords');
                if (!is_null($raw)) {
                    $stored = $strategy->group_trigger_keywords;
                    if (!empty($stored)) {
                        $keywords = $stored;
                    }
                }
            }

            self::$returnData = ['group_trigger_keywords' => $keywords];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
}
