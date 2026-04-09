<?php


namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExcelInterface;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvDevice;

/**
 * 列表
 * Class SvAccountLists
 * @package app\api\lists\sv
 * @author Qasim
 */
class SvAddWechatRecordLists extends BaseApiDataLists implements ListsSearchInterface, ListsExcelInterface
{
    private array $channel = array(
        3 => '小红书',
        1 => '视频号',
    );

    public function setSearch(): array
    {
        return [
            '=' => ['account', 'wechat_no', 'action', 'device_code', 'channel', 'exec_type', 'intention_type'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $status = $this->request->get('status', '');
        if($status != ''){
            if ((int)$this->request->get('status', 0) === 0) {
                $this->searchWhere[] = ['status', 'in', [0, 3, 5]];
            } else {
                $this->searchWhere[] = ['status', '=', $this->request->get('status', 0)];
            }
        }
        return SvAddWechatRecord::field('*')
            ->where($this->searchWhere)
             ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                if((time() - strtotime($item->create_time)) > (7 * 86400) && $item->status == 2){
                    $item->status = 0;
                    $item->result = '加微执行失败';
                    $item->save();
                }
                $item['account_detail'] = SvAccount::where('account', $item['account'])
                    ->where('user_id', $item['user_id'])
                    ->where('device_code', $item['device_code'])
                    ->limit(1)
                    ->find();
                $item['device_model'] = SvDevice::where('device_code', $item['device_code'])->value('device_model');
                $item['channel_name'] = $this->channel[$item['channel']] ?? '/';
                $item['device_name'] = SvDevice::where('device_code', $item['device_code'])->value('device_name');
                $item['task_name'] = SvCrawlingTask::where('id', $item['crawling_task_id'])->value('name') ?? '私信接管任务';
                $intentionTypeMap = [
                    -1 => '待处理',
                    0 => '其他',
                    1 => '成交意愿',
                    2 => '询价意愿',
                    3 => '想要加微信',
                    4 => '一般意愿',
                    5 => '明确拒绝'
                ];
                $intentionType = $item['intention_type'] ?? -1;
                $intentionTypeText = $intentionTypeMap[$intentionType] ?? '未知';
                $item['task_detail_described']  = '在'.$item['task_name'].'中匹配到【'.$item['original_message'].'】触发【'. $intentionTypeText  .'】';

            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $status = $this->request->get('status', '');
        if($status != ''){
            if ((int)$this->request->get('status', 0) === 0) {
                $this->searchWhere[] = ['status', 'in', [0, 3, 5]];
            } else {
                $this->searchWhere[] = ['status', '=', $this->request->get('status', 0)];
            }
        }
        return SvAddWechatRecord::field('id')
            ->where($this->searchWhere)
             ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->count();
    }


    /**
     * @notes 导出文件名
     * @return string
     * @author ljj
     * @date 2023/8/24 2:49 下午
     */
    public function setFileName(): string
    {
        return '自动加微列表';
    }

    /**
     * @notes 导出字段
     * @return string[]
     * @author ljj
     * @date 2023/8/24 2:49 下午
     */
    public function setExcelFields(): array
    {
        return [
            'channel_name' => '添加渠道',
            'original_message' => '匹配内容',
            'reg_wechat' => '线索内容',
        ];
    }
}
