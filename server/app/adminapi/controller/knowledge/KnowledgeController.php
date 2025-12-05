<?php


namespace app\adminapi\controller\knowledge;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\knowledge\KnowledgeLists;
use app\adminapi\lists\knowledge\KnowledgeChunkLists;
use app\adminapi\logic\knowledge\KnowledgeLogic;



class KnowledgeController extends BaseAdminController
{
    /**
     * @notes 助手列表
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {
        return $this->dataLists(new KnowledgeLists());
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function delete()
    {
        $params = $this->request->post();
        return KnowledgeLogic::delete($params) ? $this->success() : $this->fail(KnowledgeLogic::getError());
    }

    public function chunkLists() {
        $params = $this->request->get();
        if(!isset($params['id']) || empty($params['id'])) {
            return $this->fail('请选择知识库'); 
        }
        return $this->dataLists(new KnowledgeChunkLists());
    }
}