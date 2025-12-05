<?php


namespace app\adminapi\lists\knowledge;

use  app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\knowledge\Knowledge;
use app\common\model\knowledge\KnowledgeFile;
use app\common\model\ModelConfig;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;


/**
 * 形象
 */
class KnowledgeLists extends BaseAdminDataLists implements ListsSearchInterface
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
        $params = $this->request->get();
        $query =  Knowledge::alias('k')
                ->field('k.name,k.id,k.description,k.rerank_min_score,k.create_time,k.tokens,k.request_count,k.is_bind, u.nickname,u.avatar')
                ->join('user u', 'u.id = k.user_id')
                ->when($this->request->get('name'), function ($query) {
                    $query->where('k.name', 'like', '%'. $this->request->get('name'). '%');
                })
                ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                    $query->whereBetween('k.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
                })
                // ->when((int)$this->request->get('is_bind'), function ($query) {
                //     $query->where('k.is_bind', '=', $this->request->get('is_bind'));
                // })
                ->where($this->searchWhere);
        
        if(isset($params['is_bind']) && $params['is_bind'] != ''){
            $query->where('k.is_bind', $params['is_bind']);
        }
                
        
        return $query->order(['k.create_time' => 'desc'])
                ->limit($this->limitOffset, $this->limitLength)
                ->select()
                ->each(function ($item) {
                    $item['file_count'] = KnowledgeFile::where('kid', $item['id'])->count();  
                    
                    if($item['is_bind'] == 0 && $item['file_count'] > 0){
                        $item['is_bind'] = 1;
                        $item['update_time'] = time();
                        $item->save();
                    }
                    if ( $item['tokens'] > 0){
                        $item['tokens'] = $item['tokens'] / 200;
                    }
                })
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
        $params = $this->request->get();
        
        $query = Knowledge::alias('k')
            ->field('k.*, u.nickname,u.avatar')
            ->join('user u', 'u.id = k.user_id')
            ->when($this->request->get('name'), function ($query) {
                $query->where('k.name', 'like', '%'. $this->request->get('name'). '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('k.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when((int)$this->request->get('is_bind'), function ($query) {
                $query->where('k.is_bind', '=', $this->request->get('is_bind'));
            })
            ->where($this->searchWhere);
            
        if(isset($params['is_bind'])  && $params['is_bind'] != '' ){
            $query->where('k.is_bind', $params['is_bind']);
        }
        
        return $query->count();
    }
}
