<?php

namespace app\api\controller\team;

use app\api\controller\BaseApiController;
use app\api\logic\team\TeamCardLogic;
use app\api\validate\team\TeamCardValidate;
use app\api\lists\team\TeamCardPackagesLists;
use app\api\lists\team\TeamCardLists;

/**
 * 团队制卡(团队主生成卡密)
 * Class TeamCardController
 * @package app\api\controller\team
 */
class TeamCardController extends BaseApiController
{
    /**
     * @notes 团队自有套餐(管理与制卡下拉共用)
     */
    public function packages()
    {
        return $this->dataLists(new TeamCardPackagesLists());
    }

    /**
     * @notes 新增/编辑自有套餐
     */
    public function packageSave()
    {
        $res = TeamCardLogic::packageSave($this->userId, $this->request->post());
        if ($res === true) {
            return $this->success('保存成功');
        }
        return $this->fail(TeamCardLogic::getError() ?: '保存失败');
    }

    /**
     * @notes 删除自有套餐
     */
    public function packageDelete()
    {
        $res = TeamCardLogic::packageDelete($this->userId, (int)$this->request->post('id'));
        if ($res === true) {
            return $this->success('删除成功');
        }
        return $this->fail(TeamCardLogic::getError() ?: '删除失败');
    }

    /**
     * @notes 生成卡密(算力卡 / 会员兑换码)
     */
    public function generate()
    {
        (new TeamCardValidate())->post()->goCheck('generate');
        $result = TeamCardLogic::generate($this->userId, $this->request->post());
        if ($result === true) {
            return $this->success('卡密生成成功');
        }
        return $this->fail(TeamCardLogic::getError() ?: '生成失败');
    }

    /**
     * @notes 会员等级选项(生成会员兑换码用)
     */
    public function memberLevels()
    {
        return $this->data(TeamCardLogic::memberLevels($this->userId));
    }

    /**
     * @notes 卡密列表
     */
    public function lists()
    {
        return $this->dataLists(new TeamCardLists());
    }

    /**
     * @notes 转移未使用卡密给成员
     */
    public function transfer()
    {
        $params = (new TeamCardValidate())->post()->goCheck('transfer');
        $result = TeamCardLogic::transfer($this->userId, $params);
        if ($result === true) {
            return $this->success('转移成功');
        }
        return $this->fail(TeamCardLogic::getError() ?: '转移失败');
    }

    /**
     * @notes 删除未使用卡密
     */
    public function delete()
    {
        $params = (new TeamCardValidate())->post()->goCheck('delete');
        $result = TeamCardLogic::delete($this->userId, $params);
        if ($result === true) {
            return $this->success('删除成功');
        }
        return $this->fail(TeamCardLogic::getError() ?: '删除失败');
    }
}
