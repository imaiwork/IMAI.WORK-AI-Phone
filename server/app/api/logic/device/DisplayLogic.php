<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\model\sv\SvAccountLog;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvCrawlingRecord;
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
            //throw $th;
            self::setError($th->getMessage());
            return false;
        }
    }
}
