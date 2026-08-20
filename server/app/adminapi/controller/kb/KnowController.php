<?php


namespace app\adminapi\controller\kb;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\kb\KbKnowLists;
use app\adminapi\logic\kb\KbKnowLogic;
use app\adminapi\validate\IDMustValidate;
use think\response\Json;

/**
 * 知识库管理
 */
class KnowController extends BaseAdminController
{
    /**
     * @notes 知识库列表
     * @return Json
     * @author kb
     */
    public function lists(): Json
    {
        return $this->dataLists((new KbKnowLists()));
    }

    /**
     * @notes 知识库详情
     * @return Json
     * @author kb
     */
    public function detail(): Json
    {
        (new IDMustValidate())->goCheck();
        $id = intval($this->request->get('id'));

        $result = KbKnowLogic::detail($id);
        return $this->data($result);
    }

    /**
     * @notes 知识库创建
     * @return Json
     */
    public function add(): Json
    {
        $params = $this->request->post();
        $results = KbKnowLogic::add($params, 0);
        if ($results === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('创建成功', $results);
    }

    /**
     * @notes 知识库编辑
     * @return Json
     * @author kb
     */
    public function edit(): Json
    {
        $params = $this->request->post();
        $results = KbKnowLogic::edit($params, 0);
        if ($results === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('编辑成功');
    }

    /**
     * @notes 知识库删除
     * @return Json
     * @author kb
     */
    public function del(): Json
    {
        (new IDMustValidate())->post()->goCheck();
        $id = $this->request->post('id');

        $result = KbKnowLogic::del($id);
        if ($result === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 知识库转移
     * @return Json
     * @author kb
     */
    public function transfer(): Json
    {
        (new IDMustValidate())->post()->goCheck();
        $id = intval($this->request->post('id'));
        $type = trim($this->request->post('type', ''));
        $toUserId = intval($this->request->post('user_id', 0));

        $result = KbKnowLogic::transfer($type, $id, $toUserId);
        if ($result === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('转移成功', [], 1, 1);
    }

    /**
     * @notes 修改知识库状态
     * @return Json
     * @author kb
     */
    public function changeStatus(): Json
    {
        (new IDMustValidate())->post()->goCheck();
        $id = intval($this->request->post('id'));

        $result = KbKnowLogic::changeStatus($id);
        if ($result === false) {
            return $this->fail(KbKnowLogic::getError());
        }

        return $this->success(KbKnowLogic::getError(), [], 1, 1);
    }

    /**
     * @notes 文件列表
     * @return Json
     * @author kb
     */
    public function files(): Json
    {
        $get = $this->request->get();
        $result = KbKnowLogic::files($get);
        return $this->data($result);
    }

    /**
     * @notes 文件命名
     * @return Json
     * @author kb
     */
    public function fileRename(): Json
    {
        $params = $this->request->post();
        $results = KbKnowLogic::fileRename(intval($params['fd_id']), $params['name'], 0);
        if ($results === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('命名成功');
    }

    /**
     * @notes 文件删除
     * @return Json
     * @author kb
     */
    public function fileRemove(): Json
    {
        $fids = $this->request->post('fids', []);

        $result = KbKnowLogic::fileRemove($fids);
        if ($result === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 文件数据
     * @return Json
     * @author kb
     */
    public function fileDatas(): Json
    {
        $get = $this->request->get();
        $result = KbKnowLogic::fileDatas($get);
        return $this->data($result);
    }
}