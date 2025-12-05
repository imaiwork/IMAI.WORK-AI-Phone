<?php


namespace app\api\controller;


use app\api\logic\KnowledgeLogic;
/**
 * index
 * Class KnowledgeController
 * @package app\api\controller
 */
class KnowledgeController extends BaseApiController
{


    public array $notNeedLogin = [];


    /**
     * 知识库列表
     *
     * @return void
     */
    public function lists()
    {
        
        $data = $this->request->get();
        $result = KnowledgeLogic::getListData($data);
        return $this->data($result);
    }


    /**.
     * 知识库创建
     */
    public function add(){
        $data = $this->request->post();
        $result = KnowledgeLogic::add($data);
        if ($result) {
            return $this->data(KnowledgeLogic::getReturnData());
        }
        return $this->fail(KnowledgeLogic::getError());
    }


    public function edit(){
        $data = $this->request->post();
        $result = KnowledgeLogic::edit($data);
        if ($result) {
            return $this->data(KnowledgeLogic::getReturnData());
        }
        return $this->fail(KnowledgeLogic::getError());
    }

    /**
     * 知识库详情
     *
     * @return void
     */
    public function detail(){
        $data = $this->request->get();
        $result = KnowledgeLogic::detail($data);
        return $this->data($result);
    }

    public function indexFileList(){
        $data = $this->request->get();
        $result = KnowledgeLogic::indexFileList($data);
        return $this->data($result);
    }

    /**
     * 知识库删除
     *
     * @return void
     */
    public function delete(){
        $data = $this->request->post();

        return KnowledgeLogic::delete($data) ? $this->success('ok') : $this->fail(KnowledgeLogic::getError());
    }

    /**
     * 知识库检索
     *
     * @return void
     */
    public function retrieve(){
        $data = $this->request->post();
        $result = KnowledgeLogic::retrieve($data);
        return $this->data($result);
    }

    public function historyTest(){
        $data = $this->request->post();
        $result = KnowledgeLogic::historyTest($data);
        return $this->data($result);
    }

    public function testDetail(){
        $data = $this->request->get();
        $result = KnowledgeLogic::testDetail($data);
        return $this->data($result);
    }

    /**
     * 知识库分片
     *
     * @return void
     */
    public function chunkLists(){
        $data = $this->request->get();
        $result = KnowledgeLogic::chunkLists($data);
        return $this->data($result);
    }


    public function fileUpload(){
        $data = $this->request->post();
        $result = KnowledgeLogic::fileUpload($data);
        return $this->data($result);
    }


    public function fileLists(){
        $data = $this->request->get();
        $result = KnowledgeLogic::fileLists($data);
        return $this->data($result);
    }

    public function fileAdd(){
        $data = $this->request->post();
        return KnowledgeLogic::fileAdd($data) ? $this->success() : $this->fail(KnowledgeLogic::getError());
    }

    public function fileDetial(){
        $data = $this->request->post();
        $result = KnowledgeLogic::fileDetial($data);
        return $this->data($result);
    }

    public function fileChunkLists(){
        $data = $this->request->get();
        $result = KnowledgeLogic::fileChunkLists($data);
        return $this->data($result);
    }

    public function fileDelete(){
        $data = $this->request->post();
        return KnowledgeLogic::fileDelete($data) ? $this->success() : $this->fail(KnowledgeLogic::getError());
    }

    public function updateTagFile(){
        $data = $this->request->post();
        $result = KnowledgeLogic::updateTagFile($data);
        return $this->data($result);
    }

    public function setFileStatus(){
        $data = $this->request->post();
        return KnowledgeLogic::setFileStatus() ? $this->success() : $this->fail(KnowledgeLogic::getError());
    }
    
    public function fileChunksPull(){
        $data = $this->request->post();
        return KnowledgeLogic::fileChunksPull() ? $this->success() : $this->fail(KnowledgeLogic::getError());
    }




    public function chat(){
        $params = $this->request->post();
        return KnowledgeLogic::chat($params) ? $this->data(KnowledgeLogic::getReturnData()) : $this->fail(KnowledgeLogic::getError());
    }

    public function ladderPlayerUpload(){
        $params = $this->request->post();
        return KnowledgeLogic::ladderPlayerUpload($params) ? $this->data(KnowledgeLogic::getReturnData()) : $this->fail(KnowledgeLogic::getError());
    }


}