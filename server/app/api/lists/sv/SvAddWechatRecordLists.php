<?php


namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExcelInterface;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvAccount;
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
            '=' => ['account', 'wechat_no', 'action', 'device_code', 'channel', 'exec_type'],
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
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['account_detail'] = SvAccount::where('account', $item['account'])
                    ->where('user_id', $item['user_id'])
                    ->where('device_code', $item['device_code'])
                    ->limit(1)
                    ->find();
                $item['device_model'] = SvDevice::where('device_code', $item['device_code'])->value('device_model');
                $item['channel_name'] = $this->channel[$item['channel']] ?? '/';

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
