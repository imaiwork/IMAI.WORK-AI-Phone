<?php

namespace app\adminapi\lists\cardcode;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\CardCodeEnum;
use app\common\enum\CardCodeRecordEnum;
use app\common\lists\ListsExcelInterface;
use app\common\model\cardcode\CardCode;
use app\common\model\cardcode\CardCodeRecord;

/**
 * 卡密列表类
 * Class CardCodeLists
 * @package app\lists\cardcode
 */
class CardCodeLists extends BaseAdminDataLists implements ListsExcelInterface
{


    /**
     * @notes 实现数据列表
     * @return array
     * @author 令狐冲
     * @date 2021/7/6 00:33
     */
    public function lists(): array
    {
        $lists = (new CardCode())
            ->field('id,sn,type,balance,card_num,relation_id,valid_start_time,valid_end_time,create_time,remark')
            ->limit($this->limitOffset, $this->limitLength)
            ->where($this->setSearch())
            ->order('id desc')
            ->select()
            ->toArray();

        $id = array_column($lists,'id');

        $recordList= CardCodeRecord::where(['card_id'=>$id])
            ->where(['status'=>CardCodeRecordEnum::STATYS_YES])
            ->group('card_id')
            ->field('count(id) as num,card_id')
            ->select()->toarray();
        $recordList = array_column($recordList,'num','card_id');

        foreach ($lists as $key => $list){
            $content = '';
            switch ($list['type']){

                case CardCodeEnum::TYPE_TOKENS:
                    $content = $list['balance'].'条';
                    break;
            }
            $lists[$key]['content'] = $content;
            $lists[$key]['type_desc'] = CardCodeEnum::getTypeDesc($list['type']);
            $useDesc = $recordList[$list['id']] ?? 0;
            $lists[$key]['num_use_desc'] = $useDesc.'/'.$list['card_num'];
            $lists[$key]['valid_start_time_desc'] = date('Y-m-d H:i:s',$list['valid_start_time']);
            $lists[$key]['valid_end_time_desc'] = date('Y-m-d H:i:s',$list['valid_end_time']);

        }
        return $lists;
    }

    /**
     * @notes 实现数据列表记录数
     * @return int
     * @author 令狐冲
     * @date 2021/7/6 00:34
     */
    public function count(): int
    {
        return (new CardCode())
            ->where($this->setSearch())
            ->count();
    }


    /**
     * @notes 设置搜索条件
     * @return array
     * @author kb
     * @date 2023/7/17 14:57
     */
    public function setSearch()
    {
        $where = [];
        if(isset($this->params['sn']) && $this->params['sn']){
            $where[] = ['sn','like','%'.$this->params['sn'].'%'];
        }
        if(isset($this->params['type']) && $this->params['type']){
            $where[] = ['type','=',$this->params['type']];
        }
        if(isset($this->params['start_time']) && $this->params['start_time']){
            $where[] = ['valid_start_time','<=',strtotime($this->params['start_time'])];
        }
        if(isset($this->params['end_time']) && $this->params['end_time']){
            $where[] = ['valid_end_time','>=',strtotime($this->params['end_time'])];
        }
        return $where;

    }

    /**
     * @notes 导出文件名
     * @return string
     * @author ljj
     * @date 2023/8/24 2:49 下午
     */
    public function setFileName(): string
    {
        return '卡密列表';
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
            'sn' => '批次编号',
            'type_desc' => '卡密类型',
            'content' => '卡密内容',
            'num_use_desc' => '已使用/数量',
            'valid_start_time_desc' => '生效时间',
            'valid_end_time_desc' => '失效时间',
            'create_time' => '创建时间',
            'remark' => '备注',
        ];
    }
}