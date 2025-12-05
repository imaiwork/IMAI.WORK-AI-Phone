<?php


namespace app\adminapi\lists\knowledge;

use  app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\knowledge\KnowledgeFile;
use app\common\model\ModelConfig;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;


/**
 * 形象
 */
class FileLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['name']
        ];
    }

    public function lists(): array
    {
        

        return KnowledgeFile::alias('kf')
            ->field('kf.id,kf.kid,kf.name,kf.type,kf.size,kf.parser,kf.status,kf.file_url,kf.create_time')
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('kf.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]); 
            })
            ->when($this->request->get('status'), function ($query) {
                $query->where('kf.status', '=', $this->request->get('status'));
            })
            ->when($this->request->get('id'), function ($query) {
                $query->where('kf.kid', '=', $this->request->get('id'));
            })
            ->where($this->searchWhere)
            ->order(['kf.create_time' => 'desc'])
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
        return KnowledgeFile::alias('kf')
            ->field('kf.*') 
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('kf.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]); 
            })
            ->when($this->request->get('status'), function ($query) {
                $query->where('kf.status', '=', $this->request->get('status'));
            })
            ->when($this->request->get('id'), function ($query) {
                $query->where('kf.kid', '=', $this->request->get('id'));
            })
            ->where($this->searchWhere)->count();
    }
}
