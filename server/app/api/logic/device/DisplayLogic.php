<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvAccountLog;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingWechatTask;
use app\common\model\sv\SvCrawlingManualTaskRecord;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvLeadScrapingRecord;
use app\common\model\sv\SvDeviceCircleLikeReplyRecord;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvLeadScrapingSetting;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use app\common\model\wechat\AiWechatLog;

use think\facade\Db;


/**
 * 设备任务逻辑
 * Class DeviceLogic    
 * @package app\api\logic\device
 */
class DisplayLogic extends ApiLogic
{
    public static function display($params)
    {
        try {
            $date = $params['date'] ?? date('Y-m-d');
            $start_time = date('Y-m-d 00:00:00', strtotime($date));
            $end_time = date('Y-m-d 23:59:59', strtotime($date));

            $device_count = SvDevice::where('user_id', self::$uid)->count(); //统计设备
            $sph_clues_count = SvCrawlingRecord::where('user_id', self::$uid)
                ->whereTime('create_time', 'between', [$start_time, $end_time])
                ->where('reg_content', 'not in', ['', null])
                ->group('reg_content')
                ->count(); //社媒回复

            $social_media_reply_count = SvAccountLog::where('user_id', self::$uid)
                ->whereTime('create_time', 'between', [$start_time, $end_time])
                ->where('log_type', SvAccountLog::TYPE_MESSAGE_REPLY)
                ->count(); //社媒回复


            $logCounts = AiWechatLog::field('log_type, count(id) as count')
                ->where('user_id', self::$uid)
                ->whereTime('create_time', 'between', [$start_time, $end_time])
                ->group('log_type')
                ->column('count(id)', 'log_type');
            self::$returnData = array(
                'device_count' => $device_count, //统计设备
                'social_media_reply_count' => $social_media_reply_count, //社媒回复
                'sph_clues_count' => $sph_clues_count, // 获客人数
                'wechat_reply_count' => $logCounts[AiWechatLog::TYPE_MESSAGE_REPLY] ?? 0, //私域回复
                'wechat_add_count' => $logCounts[AiWechatLog::TYPE_ACCEPT_FRIEND] ?? 0, //添加好友
                'wechat_through_friend_count' => $logCounts[AiWechatLog::TYPE_THROUGH_FRIEND] ?? 0, //通过好友
                'wechat_like_circle_count' => $logCounts[AiWechatLog::TYPE_LIKE_CIRCLE] ?? 0, //点赞朋友圈
                'wechat_comment_circle_count' => $logCounts[AiWechatLog::TYPE_REPLY_CIRCLE] ?? 0, //评论朋友圈
                'wechat_publish_circle_count' => $logCounts[AiWechatLog::TYPE_CIRCLE_POST] ?? 0, //发布朋友圈
            );
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }


    public static function cluesDetail($params)
    {
        try {
            $id = $params['id'] ?? 0;
            if (empty($id)) {
                throw new \Exception('任务ID不能为空');
            }

            $task = SvDeviceTask::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('任务不存在');
            }
            $keywordlist = SvCrawlingRecord::getKeywordStatusStats([], $task->sub_task_id);
            $all_number_of_recognitions = array_sum(array_column($keywordlist, 'number_of_recognitions'));
            self::$returnData = [
                'task_info' => $task->toArray(),
                'all_number_of_recognitions' => $all_number_of_recognitions,
                'keyword_list' => $keywordlist,
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
    

    public static function touchDetail($params)
    {
        try {
            $id = $params['id'] ?? 0;
            if (empty($id)) {
                throw new \Exception('任务ID不能为空');
            }

            $task = SvDeviceTask::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('任务不存在');
            }
            $list = SvLeadScrapingRecord::where('scraping_account_id', $task->sub_task_id)
                ->where('task_type', $task->task_scene)
                ->where('device_code', $task->device_code)
                ->select()->each(function ($item) {

                   $account_info = SvLeadScrapingSettingAccount::where('scraping_id', $item->scraping_id)
                    ->where('id', $item->scraping_account_id)->findOrEmpty()->toArray();
                    $item->execute_name = $account_info['nickname'] ?? '';
                    $item->execute_avatar  = $account_info['avatar'] ?? 0;
                    $item->execute_account = $account_info['account'] ?? '';
                });
            $touchNumber = $list->count();
            $setting_info = [];
            if (!$list->isEmpty()) {
                $scraping_id = $list->first()->scraping_id;
                $setting_info = SvLeadScrapingSetting::where('id', $scraping_id)->field('id,industry_type,is_like,is_follow,marker_method')->findOrEmpty()->toArray();
               
            }
     
            self::$returnData = [
                'task_info' => $task->toArray(),
                'setting_info' => $setting_info,
                'touch_number' => $touchNumber,
                'list' => $list,
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function wechatCircleThumbCommentDetail($params)
    {
        try {
            $id = $params['id'] ?? 0;
            if (empty($id)) {
                throw new \Exception('任务ID不能为空');
            }
            $type = $params['type'] ?? 0;
    
            $task = SvDeviceTask::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('任务不存在');
            }
            $list = SvDeviceCircleLikeReplyRecord::getStatisticType( $task->sub_data_id,$type);

            self::$returnData = [
                'task_info' => $task->toArray(),
                'list' => $list,
                'touch_number' => $list->count(),
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function cluesWechatDetail($params)
    {
        try {
            $id = $params['id'] ?? 0;
            if (empty($id)) {
                throw new \Exception('任务ID不能为空');
            }

            $task = SvDeviceTask::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('任务不存在');
            }

            if ($task->source == 9) {
                $info = SvCrawlingWechatTask::where('id', $task->sub_task_id)->findOrEmpty()->toArray();
                $addWechatlist = SvAddWechatRecord::where('device_code', $task->device_code)
                 ->field([
                    'id',
                    'user_id',
                    'task_id',
                    'device_code',
                    'reg_wechat',      // 重命名
                    'wechat_no as execute_account',
                    'wechat_name as execute_name',
                    'wechat_avatar as execute_avatar',
                    'original_message',     // 重命名
                    'remark',
                    'action',
                    'user_account',
                    'account',
                    'account_type',
                    'status',
                    'result',
                    'image',
                    'create_time',
                    'update_time'
                ])  
                ->where('crawling_task_id', 'in', $info['craw_task_ids'] ?? [])
                ->select();
            }else{
                $addWechatlist = SvCrawlingManualTaskRecord::where('task_id', $task->sub_task_id)
                ->field([
                    'id',
                    'user_id',
                    'task_id',
                    'clue_wechat as reg_wechat',      // 重命名
                    'wechat_no as execute_account',
                    'wechat_name as execute_name',
                    'wechat_avatar as execute_avatar',
                    'remark',     // 重命名
                    'exec_task_id',
                    'exec_time',
                    'status',
                    'result',
                    'create_time',
                    'update_time'
                ])  
               ->select();
            }
            self::$returnData = [
                'task_info' => $task->toArray(),
                'list' => $addWechatlist,
                'add_wechat_number' => $addWechatlist->count(),
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

}
