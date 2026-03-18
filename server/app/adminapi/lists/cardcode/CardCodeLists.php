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
        $lists = CardCode::alias('CC')
            ->leftJoin('user U', 'CC.user_id = U.id')
            ->field('CC.id,CC.sn,CC.type,CC.balance,CC.card_num,CC.used_num,CC.user_id,CC.relation_id,CC.valid_start_time,CC.valid_end_time,CC.create_time,CC.remark,U.nickname')
            ->limit($this->limitOffset, $this->limitLength)
            ->where($this->setSearch())
            ->order('CC.id desc')
            ->select()
            ->toArray();

        $id = array_column($lists, 'id');

        $recordList = CardCodeRecord::where(['card_id' => $id])
            ->where(['status' => CardCodeRecordEnum::STATYS_YES])
            ->group('card_id')
            ->field('count(id) as num,card_id')
            ->select()->toarray();
        $recordList = array_column($recordList, 'num', 'card_id');

        foreach ($lists as $key => $list) {
            $content = '';
            switch ($list['type']) {
                case CardCodeEnum::TYPE_TOKENS:
                    $content = $list['balance'] . '条';
                    break;
                case CardCodeEnum::TYPE_DISTRIBUTION_TOKENS:
                    $content = '1条';
                    break;
            }
            $lists[$key]['content'] = $content;
            $lists[$key]['type_desc'] = CardCodeEnum::getTypeDesc($list['type']);

            // 使用次数显示兼容新版
            if ($list['type'] == CardCodeEnum::TYPE_DISTRIBUTION_TOKENS) {
                $lists[$key]['num_use_desc'] = $list['used_num'] . '/' . $list['card_num'];
            } else {
                $useDesc = $recordList[$list['id']] ?? 0;
                $lists[$key]['num_use_desc'] = $useDesc . '/' . $list['card_num'];
            }

            // 创建人显示
            $lists[$key]['creator_desc'] = $list['user_id'] ? ($list['nickname'] ?: '未知用户') : '系统';

            $lists[$key]['valid_start_time_desc'] = date('Y-m-d H:i:s', $list['valid_start_time']);
            $lists[$key]['valid_end_time_desc'] = date('Y-m-d H:i:s', $list['valid_end_time']);
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
        return CardCode::alias('CC')
            ->leftJoin('user U', 'CC.user_id = U.id')
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
        $where = [["CC.type", "!=", 5]];
        if (isset($this->params['sn']) && $this->params['sn']) {
            $where[] = ['CC.sn', 'like', '%' . $this->params['sn'] . '%'];
        }
        if (isset($this->params['type']) && $this->params['type']) {
            $where[] = ['CC.type', '=', $this->params['type']];
        }
        // 如果有输入建立人的搜索支持
        if (isset($this->params['creator_keyword']) && $this->params['creator_keyword']) {
            if ($this->params['creator_keyword'] === '系统') {
                $where[] = ['CC.user_id', '=', 0];
            } else {
                $where[] = ['U.nickname', 'like', '%' . $this->params['creator_keyword'] . '%'];
            }
        }
        if (isset($this->params['start_time']) && $this->params['start_time']) {
            $where[] = ['CC.valid_start_time', '<=', strtotime($this->params['start_time'])];
        }
        if (isset($this->params['end_time']) && $this->params['end_time']) {
            $where[] = ['CC.valid_end_time', '>=', strtotime($this->params['end_time'])];
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
            'creator_desc' => '创建来源',
            'valid_start_time_desc' => '生效时间',
            'valid_end_time_desc' => '失效时间',
            'create_time' => '创建时间',
            'remark' => '备注',
        ];
    }
}