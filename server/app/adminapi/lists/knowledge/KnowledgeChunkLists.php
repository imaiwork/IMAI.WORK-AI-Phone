<?php


namespace app\adminapi\lists\knowledge;

use  app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\knowledge\Knowledge;
use app\common\model\knowledge\KnowledgeFileSlice;
use app\common\model\ModelConfig;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;


/**
 * 形象
 */
class KnowledgeChunkLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            //"%like%" => ['name']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function lists(): array
    { 
        return KnowledgeFileSlice::alias('k')
                ->field('k.*, u.nickname,u.avatar')
                ->join('user u', 'u.id = k.user_id')
                ->join('knowledge i', 'i.index_id = k.index_id', 'left')
                ->when($this->request->get('id'), function ($query) {
                    $query->where('i.id', '=', $this->request->get('id'));
                })
                ->when($this->request->get('name'), function ($query) {
                    $query->where('k.content', 'like', '%'. $this->request->get('name'). '%');
                })
                ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                    $query->whereBetween('k.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
                })
                ->where($this->searchWhere)
                ->order(['k.create_time' => 'desc'])
                ->limit($this->limitOffset, $this->limitLength)
                ->select()
                ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        return KnowledgeFileSlice::alias('k')
            ->field('k.*, u.nickname,u.avatar')
            ->join('user u', 'u.id = k.user_id')
            ->join('knowledge i', 'i.index_id = k.index_id', 'left')
            ->when($this->request->get('id'), function ($query) {
                $query->where('i.id', '=', $this->request->get('id'));
            })
            ->when($this->request->get('name'), function ($query) {
                $query->where('k.content', 'like', '%'. $this->request->get('name'). '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('k.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }
}
