<?php

namespace app\adminapi\controller\team;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\team\TeamLists;
use app\adminapi\logic\team\TeamLogic;

/**
 * 团队(企业OEM)管理 —— 站长后台
 * 对齐 company-web/admin 的 team.team 契约。
 * Class TeamController
 * @package app\adminapi\controller\team
 */
class TeamController extends BaseAdminController
{
    /**
     * @notes 团队列表(支持 oem_status 过滤)
     */
    public function lists()
    {
        return $this->dataLists(new TeamLists());
    }

    /**
     * @notes OEM剩余名额
     */
    public function getInfo()
    {
        return $this->data(TeamLogic::getInfo());
    }

    /**
     * @notes 创建团队(指定归属用户)
     */
    public function create()
    {
        $params = $this->request->post();
        $res = TeamLogic::create($params);
        return $res ? $this->success('创建成功', [], 1, 0) : $this->fail(TeamLogic::getError());
    }

    /**
     * @notes 团队详情
     */
    public function detail()
    {
        return $this->data(TeamLogic::detail((int)$this->request->get('id')));
    }

    /**
     * @notes 设置坐席上限
     */
    public function setSeat()
    {
        $id   = (int)$this->request->post('id');
        $seat = (int)$this->request->post('seat_limit');
        $res = TeamLogic::setSeat($id, $seat);
        return $res ? $this->success('设置成功', [], 1, 0) : $this->fail(TeamLogic::getError());
    }

    /**
     * @notes 启用/停用团队
     */
    public function changeStatus()
    {
        $id = (int)$this->request->post('id');
        $status = $this->request->has('status', 'post') ? (int)$this->request->post('status') : null;
        $res = TeamLogic::changeStatus($id, $status);
        return $res ? $this->success('操作成功', [], 1, 0) : $this->fail(TeamLogic::getError());
    }

    /**
     * @notes 站长后台直接开通企业OEM(免费版→已开通,不扣团队主算力)
     */
    public function openOem()
    {
        $id = (int)$this->request->post('id');
        $res = TeamLogic::openOem($id);
        return $res ? $this->success('开通成功', [], 1, 0) : $this->fail(TeamLogic::getError());
    }

    /**
     * @notes 审核企业OEM升级(approve: 1=通过 0=拒绝并退款)
     */
    public function oemReview()
    {
        $id      = (int)$this->request->post('id');
        $approve = (int)$this->request->post('approve');
        $res = TeamLogic::oemReview($id, $approve);
        return $res ? $this->success('审核完成', [], 1, 0) : $this->fail(TeamLogic::getError());
    }

    /**
     * @notes 强制取消团队OEM(refund: 1=退预缴 0=不退)
     */
    public function cancelOem()
    {
        $id     = (int)$this->request->post('id');
        $refund = (int)$this->request->post('refund', 0);
        $res = TeamLogic::cancelOem($id, $refund);
        return $res ? $this->success('已取消该团队OEM', [], 1, 0) : $this->fail(TeamLogic::getError());
    }

    /**
     * @notes 删除(解散)团队
     */
    public function delete()
    {
        $id = (int)$this->request->post('id');
        $res = TeamLogic::delete($id);
        return $res ? $this->success('删除成功', [], 1, 0) : $this->fail(TeamLogic::getError());
    }

    /**
     * @notes 团队租户配置(域名/品牌/小程序)
     */
    public function tenant()
    {
        return $this->data(TeamLogic::tenant((int)$this->request->get('id')));
    }

    /**
     * @notes 保存团队租户配置
     */
    public function setTenant()
    {
        $params = $this->request->post();
        $id = (int)($params['id'] ?? 0);
        $res = TeamLogic::setTenant($id, $params);
        return $res ? $this->success('保存成功', [], 1, 0) : $this->fail(TeamLogic::getError());
    }

    /**
     * @notes 指定团队成员列表
     */
    public function members()
    {
        return $this->data(TeamLogic::members((int)$this->request->get('id')));
    }

    /**
     * @notes 团队算力钱包
     */
    public function wallet()
    {
        return $this->data(TeamLogic::wallet((int)$this->request->get('id')));
    }

    /**
     * @notes 读取OEM收费配置
     */
    public function oemPricing()
    {
        return $this->data(TeamLogic::oemPricing());
    }

    /**
     * @notes 保存OEM收费配置
     */
    public function saveOemPricing()
    {
        $res = TeamLogic::saveOemPricing($this->request->post());
        return $res ? $this->success('保存成功', [], 1, 0) : $this->fail(TeamLogic::getError());
    }
}
