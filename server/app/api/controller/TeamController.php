<?php

namespace app\api\controller;

use app\api\logic\TeamLogic;
use app\api\validate\TeamValidate;
use app\api\lists\team\TeamMemberConsumptionLists;

/**
 * 团队(团队版)——团队主/成员作用域接口
 * Class TeamController
 * @package app\api\controller
 */
class TeamController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @notes 开通团队(当前用户成为团队主)
     */
    public function create()
    {
        $params = (new TeamValidate())->post()->goCheck('create');
        $res = TeamLogic::create($this->userId, $params);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('开通成功', $res, 1, 0);
    }

    /**
     * @notes 当前用户的团队信息
     */
    public function info()
    {
        return $this->data(TeamLogic::info($this->userId));
    }

    /**
     * @notes 生成邀请码(团队成员均可)
     */
    public function invite()
    {
        $params = (new TeamValidate())->post()->goCheck('invite');
        $res = TeamLogic::invite($this->userId, $params);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('生成成功', $res, 1, 0);
    }

    /**
     * @notes 通过邀请码加入团队
     */
    public function join()
    {
        $params = (new TeamValidate())->post()->goCheck('join');
        $res = TeamLogic::join($this->userId, $params['code']);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('加入成功', [], 1, 0);
    }

    /**
     * @notes 团队主/管理员查看成员列表(分页,支持按成员名/手机号搜索)
     */
    public function members()
    {
        return $this->dataLists(new \app\api\lists\team\TeamMemberLists());
    }

    /**
     * @notes 成员下拉选项(全量,供消耗明细按成员筛选用)
     */
    public function memberOptions()
    {
        return $this->data(TeamLogic::memberOptions($this->userId));
    }

    /**
     * @notes 成员主动退团(团队主不可退)
     */
    public function leave()
    {
        $res = TeamLogic::leave($this->userId);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('已退出团队', [], 1, 0);
    }

    /**
     * @notes 我加入/创建的全部企业(自己创建的排第一)
     */
    public function myTeams()
    {
        return $this->data(TeamLogic::myTeams($this->userId));
    }

    /**
     * @notes 切换当前企业
     */
    public function switchTeam()
    {
        $teamId = (int)$this->request->post('team_id');
        $res = TeamLogic::switchTeam($this->userId, $teamId);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('已切换', [], 1, 0);
    }

    /**
     * @notes 申请开通某个授权功能
     */
    public function requestFeature()
    {
        $key = (string)$this->request->post('key');
        $res = TeamLogic::requestFeature($this->userId, $key);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('已提交开通申请', [], 1, 0);
    }

    /**
     * @notes 升级企业OEM(扣算力预缴费→待站长审核)
     */
    public function upgradeOem()
    {
        $mobile = trim((string)$this->request->post('mobile', ''));
        $code = trim((string)$this->request->post('code', ''));
        $res = TeamLogic::upgradeOem($this->userId, $mobile, $code);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('已提交申请，等待站长审核', $res, 1, 0);
    }

    /**
     * @notes OEM 站点归属用户列表(散客,区别于成员)
     */
    public function attributedUsers()
    {
        return $this->data(TeamLogic::attributedUsers($this->userId));
    }

    /**
     * @notes OEM 站长调整站点用户算力
     */
    public function setSiteUserTokens()
    {
        $userId = (int)$this->request->post('user_id');
        $tokens = (float)$this->request->post('tokens');
        $res = TeamLogic::setSiteUserTokens($this->userId, $userId, $tokens);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('已调整', [], 1, 0);
    }

    /**
     * @notes 团队主修改团队名称
     */
    public function setName()
    {
        $params = (new TeamValidate())->post()->goCheck('create');
        $res = TeamLogic::setName($this->userId, (string)$params['name']);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('修改成功', [], 1, 0);
    }

    /**
     * @notes 团队主解散团队
     */
    public function disband()
    {
        $res = TeamLogic::disband($this->userId);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('企业已解散', [], 1, 0);
    }

    /**
     * @notes 超管修改成员角色(成员/管理员)
     */
    public function setMemberRole()
    {
        $userId = (int)$this->request->post('user_id');
        $role = (int)$this->request->post('role');
        $res = TeamLogic::setMemberRole($this->userId, $userId, $role);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('已修改角色', [], 1, 0);
    }

    /**
     * @notes 管理员及以上修改成员企业算力(设为目标值)
     */
    public function setMemberTokens()
    {
        $userId = (int)$this->request->post('user_id');
        $tokens = (float)$this->request->post('tokens');
        $res = TeamLogic::setMemberTokens($this->userId, $userId, $tokens);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('已修改算力', [], 1, 0);
    }

    /**
     * @notes 团队主移除成员/解除散客归属
     */
    public function removeMember()
    {
        $params = (new TeamValidate())->post()->goCheck('removeMember');
        $res = TeamLogic::removeMember($this->userId, (int)$params['user_id']);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('已移除', [], 1, 0);
    }

    /**
     * @notes 团队主查看本团队租户配置(域名/品牌/小程序)
     */
    public function tenant()
    {
        return $this->data(TeamLogic::getTenant($this->userId));
    }

    /**
     * @notes 团队主设置本团队租户配置
     */
    public function setTenant()
    {
        $res = TeamLogic::setTenant($this->userId, $this->request->post());
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('保存成功', [], 1, 0);
    }

    /**
     * @notes 团队主给团队用户划拨算力(从团队主名下划出，不增发)
     */
    public function allocateTokens()
    {
        $params = (new TeamValidate())->post()->goCheck('allocate');
        $res = TeamLogic::allocateTokens($this->userId, (int)$params['user_id'], (float)$params['tokens']);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('划拨成功', [], 1, 0);
    }

    /**
     * @notes 团队主设置成员到期时间
     */
    public function setMemberExpire()
    {
        $params = (new TeamValidate())->post()->goCheck('setExpire');
        $res = TeamLogic::setMemberExpire($this->userId, (int)$params['user_id'], (int)($params['expire'] ?? 0));
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('设置成功', [], 1, 0);
    }

    /**
     * @notes 团队主查看某成员的算力消耗明细
     */
    public function memberConsumption()
    {
        return $this->dataLists(new TeamMemberConsumptionLists());
    }

    /**
     * @notes 企业算力消耗明细(全员合集)
     */
    public function consumption()
    {
        return $this->dataLists(new \app\api\lists\team\TeamConsumptionLists());
    }

    /**
     * @notes 某条消耗记录的产出结果
     */
    public function consumptionOutput()
    {
        $logId = (int)$this->request->get('log_id');
        return $this->data(TeamLogic::consumptionOutput($this->userId, $logId));
    }

    /**
     * @notes 团队自有小程序：建议下一版本号
     */
    public function mnpVersion()
    {
        return $this->data(TeamLogic::mnpVersion($this->userId));
    }

    /**
     * @notes 团队主上传自有小程序代码到微信
     */
    public function uploadMnp()
    {
        $res = TeamLogic::uploadMnp($this->userId, $this->request->post());
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('上传成功', $res, 1, 0);
    }

    /**
     * @notes 团队主上传自有小程序代码包(zip)，解压到团队专属目录
     */
    public function uploadMnpCode()
    {
        $res = TeamLogic::uploadMnpCode($this->userId);
        if ($res === false) {
            return $this->fail(TeamLogic::getError());
        }
        return $this->success('上传成功', $res, 1, 0);
    }
}
