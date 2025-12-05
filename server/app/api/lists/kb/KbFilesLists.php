<?php


namespace app\api\lists\kb;

use app\api\lists\BaseApiDataLists;
use app\common\enum\kb\KnowEnum;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbKnowFiles;
use app\common\model\kb\KbKnowQa;
use app\common\model\kb\KbKnowTeam;
use app\common\pgsql\KbEmbedding;
use Exception;

/**
 * 知识库文件列表
 */
class KbFilesLists extends BaseApiDataLists
{
    /**
     * @notes 查询条件
     * @return array
     * @throws Exception
     */
    public function where(): array
    {
        $where[] = ['know_id', '=', intval($this->params['kb_id'])];
        if (isset($this->params['keyword']) && $this->params['keyword']) {
            $where[] = ['name', 'like', '%'.$this->params['keyword'].'%'];
        }

        // 验证是否有权限
        $know = (new KbKnow())->where(['id'=>intval($this->params['kb_id'])])->findOrEmpty()->toArray();
        if (!$know) {
            throw new Exception('知识库已不存在');
        }

        if ($know['user_id'] !== $this->userId) {
            $team = (new KbKnowTeam())
                ->where(['kb_id'=>$this->params['kb_id']])
                ->where(['user_id'=>$this->userId])
                ->findOrEmpty()
                ->toArray();
            if (!$team) {
                throw new Exception('无权限查看');
            }
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
//        $modelKbKnow = new KbKnow();
//        $kbKnow = $modelKbKnow->where(['id'=>intval($this->params['kb_id'])])->findOrEmpty();
//        if ($kbKnow->isEmpty()) {
//            return [];
//        }

        $model = new KbKnowFiles();
        $lists = $model
            ->field(['id,name,size,type,user_id,know_id,is_qa,is_default,update_time,create_time'])
            ->where($this->where())
            ->order('is_default desc, id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $kbQa = new KbKnowQa();
        $pgKbEmbedding = new KbEmbedding();
        foreach ($lists as &$item) {
            $baseWhere = ['kb_id'=>$item['know_id'], 'fd_id'=>intval($item['id']), 'is_delete'=>0];
            $item['ok_sum']    = $pgKbEmbedding->where($baseWhere)->where(['status'=>KnowEnum::RUN_OK])->count();
            $item['wait_sum']  = $pgKbEmbedding->where($baseWhere)->where(['status'=>KnowEnum::RUN_WAIT])->count();
            $item['total_sum'] = $pgKbEmbedding->where($baseWhere)->count();

            if ($item['is_qa']) {
                $qa = $kbQa
                    ->where(['kb_id' => $item['know_id']])
                    ->where(['fd_id' => $item['id']])
                    ->findOrEmpty()
                    ->toArray();

                if ($qa) {
                    if (strtotime($qa['create_time'])+(60*30) < time() && $qa['status'] == KnowEnum::QA_ING) {
                        $qa['status'] = KnowEnum::QA_FAIL;
                        $qa['error'] = '拆分任务超时中断';
                        KbKnowQa::update([
                            'status'      => KnowEnum::QA_FAIL,
                            'error'       => '拆分任务超时中断',
                            'update_time' => time()
                        ], ['id'=>$qa['id']]);
                    }
                    $item['qa'] = [
                        'error'      => $qa['error'] ?? '',
                        'status'     => $qa['status'],
                        'status_msg' => KnowEnum::getQaStatusDesc($qa['status']),
                        'task_time'  => $qa['task_time'],
                    ];
                }
            }
        }

        return $lists;
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     */
    public function count(): int
    {
        $model = new KbKnowFiles();
        return $model
            ->where($this->where())
            ->count();
    }
}