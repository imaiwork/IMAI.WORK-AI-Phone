<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExcelInterface;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvDevice;
use app\common\service\FileService;


/**
 * 爬取记录列表
 * Class CrawlingRecordLists
 */
class CrawlingRecordLists extends BaseApiDataLists implements ListsSearchInterface,ListsExcelInterface
{
    private array $clueType = array(
        0 => '/',
        1 => '微信号',
        2 => '手机号',
    );

    public function setSearch(): array
    {
        return [
            '='      => ['user_id', 'exec_keyword', 'device_code', 'status'],
            //'%like%' => ['device_code', 'keywords'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['task_id', '=', $this->request->get('task_id', 0)];
        $this->searchWhere[] = ['reg_content', 'not in', ['', null]];

        $this->searchWhere[] = ['task_id', '=', $this->request->get('task_id', 0)];
        $this->searchWhere[] = ['hash', 'not in', ['', null]];
        
        // print_r(SvCrawlingRecord::field('*, max(exec_time) as exec_time')->where($this->searchWhere)
        //     ->order('exec_time', 'desc')
        //     ->limit($this->limitOffset, $this->limitLength)
        //     ->group('task_id, exec_keyword,reg_content')
        //     ->fetchSql(true)
        //     ->select());die;
        
        $list = SvCrawlingRecord::field('*, max(exec_time) as exectime')->withoutField('exec_time')->where($this->searchWhere)
            ->order('exectime', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->group('task_id, exec_keyword,reg_content')
            ->select()
            ->each(function ($item) {
                $item['device_model'] = SvDevice::where('device_code', $item['device_code'])->value('device_model');
                $item['clue_type_name'] = $this->clueType[$item['clue_type']];
                $item['image'] = FileService::getFileUrl($item['image']);
                $item['exec_time'] = $item['exectime'];
                $item['create_time'] = $item['exectime'];
            })
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['task_id', '=', $this->request->get('task_id', 0)];
        $this->searchWhere[] = ['reg_content', 'not in', ['', null]];
        return SvCrawlingRecord::where($this->searchWhere)->group('reg_content')->count();
    }

    /**
     * @notes 导出文件名
     * @return string
     * @author ljj
     * @date 2023/8/24 2:49 下午
     */
    public function setFileName(): string
    {
        return '获客列表';
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
            'username' => '用户名称',
            'address' => '所属IP',
            'exec_keyword' => '执行线索词',
            'crawl_content' => '获取内容',
            'device_model' => '执行设备名称',
            'reg_content' => '所提取内容',
            'clue_type_name' => '线索类型',
            'image' => '识别图片',
            //'tokens' => '算力消耗',
            'exec_time' => '执行时间',
        ];
    }
}
