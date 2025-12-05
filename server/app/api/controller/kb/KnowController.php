<?php


namespace app\api\controller\kb;

use app\api\controller\BaseApiController;
use app\api\lists\kb\KbFilesLists;
use app\api\lists\kb\KbKnowLists;
use app\api\logic\kb\KbKnowLogic;
use app\api\validate\kb\KbKnowValidate;
use Exception;
use think\db\exception\DbException;
use think\response\Json;

/**
 * 知识库管理
 */
class KnowController extends BaseApiController
{
    public array $notNeedLogin = ['lists'];

    /**
     * @notes 所有知识库
     * @return Json
     * @author kb
     */
    public function all(): Json
    {
        $lists = KbKnowLogic::all($this->userId);
        return $this->data($lists);
    }

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
     * @throws Exception
     * @author kb
     */
    public function detail(): Json
    {
//        $params = (new KbKnowValidate())->get()->goCheck('id');
        $params = $this->request->get();
        $results = KbKnowLogic::detail(intval($params['id']), $this->userId);
        if (!$results) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->data($results);
    }

    /**
     * @notes 知识库创建
     * @return Json
     */
    public function add(): Json
    {
//        $params = (new KbKnowValidate())->post()->goCheck('add');
        $params = $this->request->post();
        $results = KbKnowLogic::add($params, $this->userId);
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
//        $params = (new KbKnowValidate())->post()->goCheck('edit');
        $params = $this->request->post();
        $results = KbKnowLogic::edit($params, $this->userId);
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
//        $params = (new KbKnowValidate())->post()->goCheck('id');
        $params = $this->request->post();
        $results = KbKnowLogic::del(intval($params['id']), $this->userId);
        if ($results === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('删除成功');
    }

    /**
     * @notes 知识库转移
     * @return Json
     * @author kb
     */
    public function transfer(): Json
    {
//        $params = (new KbKnowValidate())->post()->goCheck('kid');
        $params = $this->request->post();
        $type     = trim($this->request->post('type', ''));
        $toUserSn = trim($this->request->post('sn', 0));

        $results = KbKnowLogic::transfer(intval($params['kb_id']), $type, $this->userId, $toUserSn);
        if ($results === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('转移成功');
    }

    /**
     * @notes 文件列表
     * @return Json
     * @author kb
     */
    public function files(): Json
    {
        (new KbKnowValidate())->get()->goCheck('kid', [], 1);
        return $this->dataLists((new KbFilesLists()));
    }

    /**
     * @notes 文件命名
     * @return Json
     * @author kb
     */
    public function fileRename(): Json
    {
//        $params = (new KbKnowValidate())->post()->goCheck('rename');
        $params = $this->request->post();
        $results = KbKnowLogic::fileRename(intval($params['fd_id']), $params['name'], $this->userId);
        if ($results === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('命名成功');
    }

    /**
     * @notes 文件移除
     * @return Json
     * @author kb
     */
    public function fileRemove(): Json
    {
//        $params = (new KbKnowValidate())->post()->goCheck('fid');
        $params = $this->request->post();
        $results = KbKnowLogic::fileRemove(intval($params['fd_id']), $this->userId);
        if ($results === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('移除成功');
    }

    /**
     * @notes 共享用户筛选
     * @return Json
     * @throws DbException
     * @author kb
     */
    public function teamUsers(): Json
    {
        $lists = KbKnowLogic::teamUsers($this->request->get(), $this->userId);
        return $this->success('OK', $lists);
    }

    /**
     * @notes 团队成员列表
     * @return Json
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function teamLists(): Json
    {
        $kbId = intval($this->request->get('kb_id', 0));
        $lists = KbKnowLogic::teamLists($kbId);
        if ($lists === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('OK', $lists);
    }

    /**
     * @notes 团队成员添加
     * @return Json
     * @author kb
     */
    public function teamAdd(): Json
    {
        $result = KbKnowLogic::teamAdd($this->request->post(), $this->userId);
        if ($result === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('添加成功');
    }

    /**
     * notes 团队成员编辑
     * @return Json
     * @author kb
     */
    public function teamEdit(): Json
    {
        $id = intval($this->request->post('id'));
        $power = intval($this->request->post('power'));

        $result = KbKnowLogic::teamEdit($id, $power, $this->userId);
        if ($result === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('更新成功');
    }

    /**
     * @notes 团队成员删除
     * @return Json
     * @author kb
     */
    public function teamDel(): Json
    {
        $id = intval($this->request->post('id'));
        $result = KbKnowLogic::teamDel($id, $this->userId);
        if ($result === false) {
            return $this->fail(KbKnowLogic::getError());
        }
        return $this->success('删除成功');
    }

}