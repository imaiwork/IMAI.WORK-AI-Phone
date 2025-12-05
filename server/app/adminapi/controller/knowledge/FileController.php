<?php


namespace app\adminapi\controller\knowledge;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\knowledge\FileLists;
use app\adminapi\logic\knowledge\FileLogic;
use app\adminapi\lists\knowledge\FileChunkLists;




class FileController extends BaseAdminController
{
    /**
     * @notes 助手列表
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {
        return $this->dataLists(new FileLists());
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function delete()
    {
        $params = $this->request->post();
        return FileLogic::delete($params) ? $this->success() : $this->fail(FileLogic::getError());
    }

    public function chunkLists() {
        $params = $this->request->get();
        if(!isset($params['id']) || empty($params['id'])) {
            return $this->fail('请选择文档'); 
        }
        return $this->dataLists(new FileChunkLists());
    }
}