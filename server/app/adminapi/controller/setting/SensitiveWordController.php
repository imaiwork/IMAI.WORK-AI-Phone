<?php


namespace app\adminapi\controller\setting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\setting\SensitiveWordLists;
use app\adminapi\logic\setting\SensitiveWordLogic;
use app\adminapi\validate\setting\SensitiveWordValidate;
use think\response\Json;

/**
 * 敏感词库控制器类
 */
class SensitiveWordController extends BaseAdminController
{
    /**
     * @notes 敏感词列表
     * @return Json
     * @author kb
     */
    public function lists(): Json
    {
        return $this->dataLists((new SensitiveWordLists()));
    }

    /**
     * @notes 敏感词详情
     * @return Json
     * @author kb
     */
    public function detail(): Json
    {
        $params = (new SensitiveWordValidate())->get()->goCheck('id');
        $result = SensitiveWordLogic::detail(intval($params['id']));
        return $this->data($result);
    }

    /**
     * @notes 敏感词新增
     * @return Json
     * @author kb
     */
    public function add(): Json
    {
        $params = (new SensitiveWordValidate())->post()->goCheck('add');
        $result = SensitiveWordLogic::add($params);
        if ($result === false) {
            return $this->fail(SensitiveWordLogic::getError());
        }
        return $this->success('新增成功', [], 1, 1);
    }

    /**
     * @notes 敏感词编辑
     * @return Json
     * @author kb
     */
    public function edit(): Json
    {
        $params = (new SensitiveWordValidate())->post()->goCheck('edit');
        $result = SensitiveWordLogic::edit($params);
        if ($result === false) {
            return $this->fail(SensitiveWordLogic::getError());
        }
        return $this->success('编辑成功', [], 1, 1);
    }

    /**
     * @notes 敏感词删除
     * @return Json
     * @author kb
     */
    public function del(): Json
    {
        $params = (new SensitiveWordValidate())->post()->goCheck('id');
        $result = SensitiveWordLogic::del(intval($params['id']));
        if ($result === false) {
            return $this->fail(SensitiveWordLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 敏感词状态
     * @return Json
     * @author kb
     */
    public function status(): Json
    {
        $params = (new SensitiveWordValidate())->post()->goCheck('id');
        $result = SensitiveWordLogic::status(intval($params['id']));
        if ($result === false) {
            return $this->fail(SensitiveWordLogic::getError());
        }
        return $this->success('修改成功', [], 1, 1);
    }

    /**
     * @notes 配置详情
     * @return Json
     * @author kb
     */
    public function getConfig(): Json
    {
        $result = SensitiveWordLogic::getConfig();
        return $this->data($result);
    }

    /**
     * @notes 配置保存
     * @return Json
     * @author kb
     */
    public function setConfig(): Json
    {
        $params = (new SensitiveWordValidate())->post()->goCheck('setConfig');
        SensitiveWordLogic::setConfig($params);
        return $this->success('设置成功', [], 1, 1);
    }
}