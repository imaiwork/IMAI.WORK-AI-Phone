<?php


namespace app\api\lists\kb;

use app\api\lists\BaseApiDataLists;
use app\common\enum\kb\KnowEnum;
use app\common\lists\ListsExcelInterface;
use app\common\model\kb\KbKnowQa;
use app\common\pgsql\KbEmbedding;
use app\queue\BaseQueue;

/**
 * 知识库训练数据列表
 */
class KbTeachLists extends BaseApiDataLists implements ListsExcelInterface
{
    /**
     * @notes 条件
     * @return array
     * @author kb
     */
    public function where(): array
    {
        $where[] = ['is_delete', '=', 0];
        $where[] = ['kb_id', '=', intval($this->params['kb_id'])];
        $where[] = ['fd_id', '=', intval($this->params['fd_id'])];

        if (isset($this->params['status']) && is_numeric($this->params['status'])) {
            $where[] = ['status', '=', intval($this->params['status'])];
        }

        if (isset($this->params['keyword']) && $this->params['keyword']) {
            $where[] = ['question|answer', 'like', '%'.$this->params['keyword'].'%'];
        }

        if (isset($this->params['page_type']) and $this->params['page_type']) {
            $page_start = intval($this->params['page_start'] ?? 1);
            $page_end   = intval($this->params['page_end']   ?? 1);
            $this->limitOffset = ($page_start - 1) * 15;
            $this->limitLength = $page_end * 15;
        }

        return $where;
    }

    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function lists(): array
    {
        $model = new KbEmbedding();
        $lists = $model
//            ->field(['uuid,question,answer,model,tokens,error,status,update_time,create_time'])
            ->field(true)
            ->where($this->where())
            ->limit($this->limitOffset, $this->limitLength)
            ->order('create_time desc, index asc')
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['tokens'] = format_amount_zero($item['tokens']);
            $item['status_msg'] = KnowEnum::getRunStatusDesc($item['status']);
            if (!$item['error']) {
                unset($item['error']);
            }
        }

        // 加载向量任务
        $jonCount = BaseQueue::getEmbJobLength();
        if ($jonCount <= 0) {
            $ems = $model
                ->field(['uuid'])
                ->where(['is_delete'=>0])
                ->where(['status'=>KnowEnum::RUN_WAIT])
                ->limit(50)
                ->select()
                ->toArray();

            foreach ($ems as $e) {
                BaseQueue::pushEM(['uuid'=>$e['uuid']]);
            }
        }

        // 加载QA任务
        $qaCount = BaseQueue::getQaJobLength();
        if ($qaCount <= 0) {
            $modelKbKnowQa = new KbKnowQa();
            $qaList = $modelKbKnowQa
                ->where(['status'=>KnowEnum::QA_WAIT])
                ->limit(40)
                ->select()
                ->toArray();

            foreach ($qaList as $q) {
                BaseQueue::pushQA(['id'=>$q['id']]);
            }
        }

        return $lists;
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public function count(): int
    {
        $model = new KbEmbedding();
        return $model
            ->where($this->where())
            ->count();
    }

    /**
     * @notes 导出名称
     * @return string
     * @author kb
     */
    public function setFileName(): string
    {
        return '训练数据列表';
    }

    /**
     * @notes 导出字段
     * @return array
     * @author kb
     */
    public function setExcelFields(): array
    {
        return [
//            'uuid'      => 'uuid',
//            'kb_id'     => '所属知识库',
//            'fd_id'     => '所属文件夹',
//            'user_id'   => '所属用户',
//            'emb_model_id' => '模型编号',
//            'code'      => '批次编号',
//            'salt'      => '问题的盐',
            'question'  => '训练问题',
            'answer'    => '答复内容',
            'model'     => '训练模型',
//            'embedding' => '训练结果',
//            'dimension' => '向量维度',
//            'annex'     => '携带附件',
//            'tokens'    => '消耗tokens',
//            'status'    => '训练状态',
//            'create_time' => '创建时间',
            'update_time' => '更新时间'
        ]??[];
    }
}