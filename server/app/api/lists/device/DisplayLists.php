<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;

use app\common\enum\DeviceEnum;
use app\common\model\sv\SvCrawlingManualTaskRecord;
use app\common\model\sv\SvCrawlingWechatTask;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\sv\SvLeadScrapingRecord;
use app\common\model\sv\SvAccountLog;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\model\sv\SvDeviceCircleLikeReplyRecord;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvAccount;
use app\common\model\wechat\AiWechatCircleTask;



/**
 * 设备显示任务列表
 * Class DisplayLists
 * @package app\api\lists\device
 * @author Qasim
 */
class DisplayLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['device_code', 'auto_type']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['status', 'not in', [0, 1]];

        $params = $this->request->get();
        if (isset($params['account_type']) && !empty($params['account_type'])) {
            if ($params['account_type'] == 2) {
                $this->searchWhere[] = ['account_type', '=', 1];
                $this->searchWhere[] = ['task_type', 'in', [5, 7, 8, 9, 10, 25]];
            } else if ($params['account_type'] == 1) {
                $this->searchWhere[] = ['account_type', '=', 1];
                $this->searchWhere[] = ['task_type', 'not in', [5, 7, 8, 9, 10, 25]];
            } else {
                $this->searchWhere[] = ['account_type', '=', $params['account_type']];
            }
        }
        return SvDeviceTask::field('*')
            ->where($this->searchWhere)
            ->order('start_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item->account = SvAccount::field('account,nickname,avatar,type')->where('device_code', $item->device_code)->where('type', $item->account_type)->findOrEmpty()->toArray();
                $item->device = SvDevice::field('device_name,device_model,status,device_code,sdk_version')->where('device_code', $item->device_code)->findOrEmpty()->toArray();
                $item->task_type = DeviceEnum::getTaskTypeByAuto($item->task_type);

                switch ($item->source) {

                    case DeviceEnum::TASK_SOURCE_PUBLISH: //1
                        $item->data_info = [];
                        break;

                    case DeviceEnum::TASK_SOURCE_TAKEOVER: //2
                        $item->data_info = [
                            'reply_number' => SvAccountLog::where('log_type', 5)
                                ->where('account', $item->account['account'])
                                ->where('account_type', $item->account_type)
                                ->where('create_time', 'between', [$item->start_time, $item->end_time])
                                ->count(),
                        ];
                        break;

                    case DeviceEnum::TASK_SOURCE_ACTIVE: //3
                        $item->data_info = [];
                        break;

                    case DeviceEnum::TASK_SOURCE_FRIENDS: //5
                        $taskinfo = SvCrawlingManualTaskRecord::where('task_id', $item->sub_task_id)->count();
                        $item->data_info = [
                            'add_wechat_number' => $taskinfo,
                        ];
                        break;

                    case DeviceEnum::TASK_SOURCE_CLUES: //4
                        $keyword_number = SvCrawlingTask::where('id', $item->sub_task_id)->value('number_of_implemented_keywords');
                        $clues = SvCrawlingRecord::where('task_id', $item->sub_task_id)->where('reg_content', '<>', '')->group('reg_content')->column('reg_content');
                        $item->data_info = [
                            'keyword_number' => $keyword_number,
                            'clues_number' => count($clues),
                        ];
                        break;
                    case DeviceEnum::TASK_SOURCE_TOUCH: //6
                        $item->data_info = [
                            'comment_number' => SvLeadScrapingRecord::where('scraping_account_id', $item->sub_task_id)->where('task_type', $item->task_scene)->where('device_code', $item->device_code)->count(),
                        ];
                        break;
                    case DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH: //7
                        $item->data_info = [
                            'publish_number' => AiWechatCircleTask::where('id', $item->sub_data_id)->where('device_code', $item->device_code)->count(),
                        ];
                        break;
                    case DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_THUMB_COMMENT: //8
                        $like_number  = SvDeviceCircleLikeReplyRecord::where('like_reply_account', $item->sub_data_id)->where('type', 1)->where('device_code', $item->device_code)->count();
                        $comment_number = SvDeviceCircleLikeReplyRecord::where('like_reply_account', $item->sub_data_id)->where('type', 2)->where('device_code', $item->device_code)->count();
                        $like_comment_number = SvDeviceCircleLikeReplyRecord::where('like_reply_account', $item->sub_data_id)->where('type', 3)->where('device_code', $item->device_code)->count();
                        $type = 1;
                        $number = 0;
                        if($like_number > 0){
                            $type = 1;
                            $number = $like_number;
                        }
                        if($comment_number > 0){
                            $type = 2;
                            $number = $comment_number;
                        }
                        if($like_comment_number > 0){
                            $type = 3;
                            $number = $like_comment_number;
                        }
                        $item->data_info = [
                            'type' => $type,
                            'like_comment_number' => $number,
                        ];
                        break;
                    case DeviceEnum::TASK_SOURCE_CLUES_WECHAT: //9
                        $info = SvCrawlingWechatTask::where('id', $item->sub_task_id)->findOrEmpty()->toArray();
                        $item->data_info = [
                            'add_wechat_number' => SvAddWechatRecord::where('device_code', $item->device_code)->where('crawling_task_id', 'in', $info['craw_task_ids'])->count(),
                        ];
                        break;
                    default:
                        break;
                }
                $item->start_time_text = date('Y-m-d H:i',  $item->start_time);
                $item->start_time = date('H:i',  $item->start_time);
                $item->end_time = date('H:i', $item->end_time);
                return $item;
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvDeviceTask::field('id')
            ->where($this->searchWhere)
            ->count();
    }
}
