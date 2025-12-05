<?php

namespace app\adminapi\lists\cardcode;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\CardCodeEnum;
use app\common\lists\ListsExcelInterface;
use app\common\model\cardcode\CardCodeRecord;
use app\common\service\FileService;

/**
 * 卡密记录列表类
 * Class CardCodeRecordLists
 * @package app\lists\cardcode
 */
class CardCodeRecordLists extends BaseAdminDataLists implements ListsExcelInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     * @author kb
     * @date 2023/7/10 18:28
     */
    public function setSearch()
    {
        $where = [];
        if(isset($this->params['id']) && $this->params['id']){
            $where[] = ['CC.id','=',$this->params['id']];
        }
        if(isset($this->params['status']) && '' != $this->params['status']){
            $where[] = ['CCR.status','=',$this->params['status']];
        }
        if(isset($this->params['keyword']) && $this->params['keyword'] != ''){
            $where[] = ['CCR.sn|CC.sn','like','%'.$this->params['keyword'].'%'];
        }
        if(isset($this->params['user_keyword']) && $this->params['user_keyword']){
            $where[] = ['U.sn|U.nickname|U.mobile','like','%'.$this->params['user_keyword'].'%'];
        }
        if(isset($this->params['type']) && $this->params['type']){
            $where[] = ['CC.type','in',$this->params['type']];
        }
        if(isset($this->params['start_time']) && $this->params['start_time']){
            $where[] = ['CCR.create_time','>=',strtotime($this->params['start_time'])];
        }
        if(isset($this->params['end_time']) && $this->params['end_time']){
            $where[] = ['CCR.create_time','<=',strtotime($this->params['end_time'])];
        }
        return $where;
    }


    /**
     * @notes 实现数据列表
     * @return array
     * @author 令狐冲
     * @date 2021/7/6 00:33
     */
    public function lists(): array
    {
        $lists = CardCodeRecord::alias('CCR')
            ->join('card_code CC', 'CC.id = CCR.card_id')
            ->leftjoin('user U', 'CCR.user_id = U.id')
            ->where($this->setSearch())
            ->field('CCR.id,CC.sn,CCR.status,CCR.sn as record_sn,CC.balance,CC.relation_id,U.nickname,U.avatar,CC.type,CCR.use_time')
            ->limit($this->limitOffset, $this->limitLength)
            ->order('use_time desc')
            ->select()
            ->toArray();

        foreach ($lists as $key => $list){
            $content = '';
            switch ($list['type']){
                case CardCodeEnum::TYPE_TOKENS:
                    $content = $list['balance'];
                    break;
            }
            $lists[$key]['content'] = $content;
            $lists[$key]['type_desc'] = CardCodeEnum::getTypeDesc($list['type']);
            if($list['avatar']){
                $lists[$key]['avatar'] = FileService::getFileUrl($list['avatar']);
            }
            if($list['use_time']){
                $lists[$key]['use_time'] = date('Y-m-d H:i:s',$list['use_time']);
            }
            $statusDesc = '未使用';
            if($list['status']){
                $statusDesc = '已使用';
            }
            $lists[$key]['status_desc'] = $statusDesc;

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
        return  $lists = CardCodeRecord::alias('CCR')
            ->join('card_code CC', 'CC.id = CCR.card_id')
            ->leftjoin('user U', 'CCR.user_id = U.id')
            ->where($this->setSearch())
            ->count();
    }

    /**
     * @notes 设置导出字段
     * @return array
     * @author 令狐冲
     * @date 2021/7/21 16:04
     */
    public function setExcelFields(): array
    {
        return [
            'sn'            => '批次编号',
            'record_sn'     => '卡密编号',
            'nickname'      => '使用人',
            'type_desc'     => '卡密类型',
            'status_desc'   => '使用状态',
            'content'       => '面额',
            'use_time'      => '使用时间',
        ];
    }

    /**
     * @notes 设置导出文件名
     * @return string
     * @author 令狐冲
     * @date 2021/7/26 17:47
     */
    public function setFileName(): string
    {
        return '卡密记录';
    }
}