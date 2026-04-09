<?php


namespace app\api\logic;

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
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvPrivateMessage;
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
            $st = strtotime(date('Y-m-d 00:00:00', strtotime($date)));
            $et = strtotime(date('Y-m-d 23:59:59', strtotime($date)));

            $today_task_count = SvDeviceTask::where('user_id', self::$uid)->where('day', $date)->count(); //今日任务数
            $today_complete_task_count = SvDeviceTask::where('user_id', self::$uid)->where('day', $date)->where('status', 2)->count(); //今日完成任务数
            $today_rate = $today_task_count ? round(($today_complete_task_count / $today_task_count) * 100, 2) : 0; //今日完成率
            $worker_device_count = SvDevice::where('user_id', self::$uid)->where('status', 2)->count(); //统计设备数
            $today_touch_number = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)
                ->where('s.industry_type', 0)
                ->where('r.create_time', 'between', [$st, $et])->count(); //今日触达人数
            $touch_expose_number = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)
                ->where('s.industry_type', 1)
                ->where('r.create_time', 'between', [$st, $et])->count();
            $msg_expose_number = SvPrivateMessage::where('user_id', self::$uid)->where('create_time', 'between', [$st, $et])->where('reply_time', '<>', 'null')->count(); //今日曝光人数
            $today_expose_number = $touch_expose_number + $msg_expose_number;  //今日曝光人数

            $st7 = strtotime(date('Y-m-d 00:00:00', strtotime('-6 days')));
            $seven_touch_expose_number = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)
                ->where('s.industry_type', 1)
                ->where('r.create_time', 'between', [$st7, $et])->count();
            $seven_msg_expose_number = SvPrivateMessage::where('user_id', self::$uid)->where('create_time', 'between', [$st7, $et])->where('reply_time', '<>', 'null')->count(); //7天曝光人数  
            $seven_expose_number = $seven_touch_expose_number + $seven_msg_expose_number;  //7天曝光人数

            $seven_touch_number = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)
                ->where('s.industry_type', 0)
                ->where('r.create_time', 'between', [$st7, $et])->count(); //7天触达人数
            $seven_intention_number = SvAddWechatRecord::where('intention_type', 'in', [1, 2, 3, 4])
                ->where('user_id', self::$uid)
                ->where('create_time', 'between', [$st7, $et])->count(); //7天意向人数


            $touch_number = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)->count();; //截流触达人数
            $msg_number = SvPrivateMessage::where('user_id', self::$uid)->where('reply_time', '<>', 'null')->count(); //截流消息人数
            $sph_number = SvCrawlingRecord::where('user_id', self::$uid)->count(); //截流接待人数
            $total_touch_number = $touch_number + $sph_number + $msg_number; //总触达人数

            $total_expose_number = SvAddWechatRecord::where('channel', 'in', [3, 4, 5])->where('user_id', self::$uid)->count(); //总曝光人数

            self::$returnData = array(
                'today_task_count' => $today_task_count, //今日任务数
                'today_complete_task_count' => $today_complete_task_count, //今日完成任务数
                'today_rate' => $today_rate, //今日完成率
                'worker_device_count' => $worker_device_count, //统计设备数
                'today_touch_number' => $today_touch_number, //今日触达人数
                'today_expose_number' => $today_expose_number, //今日曝光人数

                'seven_expose_number' => $seven_expose_number, //7天曝光人数
                'seven_touch_number' => $seven_touch_number, //7天触达人数
                'seven_intention_number' => $seven_intention_number, //7天意向人数

                'ai_clues' => [
                    'touch_number' => $total_touch_number, //触达任务
                    'expose_number' => $total_expose_number, //意向
                    'rate' => $total_touch_number > 0 ? round(($total_expose_number / $total_touch_number) * 100, 2) : 0, //意向完成率
                ],
                'content_operation' => [
                    'count' => SvPublishSettingDetail::where('user_id', self::$uid)->where('auto_type', 1)->count(), //总生成发布数
                    'publish_count' => SvPublishSettingDetail::where('user_id', self::$uid)->where('auto_type', 1)->where('status', 1)->count(), //发布成功数
                ],
                'ai_customer' => [
                    'receive' => SvPrivateMessage::where('user_id', self::$uid)->where('reply_time', '<>', 'null')->count(), //接待
                    'drainage' => 0, //引流
                ],
                'ai_sales' => [
                    'add_friends' => SvAddWechatRecord::where('user_id', self::$uid)->where('status', 'in', [1, 2])->count(), //添加好友
                    'group' => 0, //群聊
                ]
            );
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
}
