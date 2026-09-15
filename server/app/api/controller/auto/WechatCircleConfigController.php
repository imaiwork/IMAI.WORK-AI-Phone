<?php

namespace app\api\controller\auto;

use app\api\controller\BaseApiController;
use app\api\logic\auto\AutoDeviceWechatCircleConfigLogic;
use app\api\validate\auto\AutoDeviceWechatCircleConfigValidate;
use app\api\lists\auto\AutoDeviceWechatCircleConfigLists;

/**
 * 微信朋友圈自动化配置控制器
 * Class WechatCircleConfigController
 * @package app\api\controller\auto
 */
class WechatCircleConfigController extends BaseApiController
{
    /**
     * 获取微信朋友圈自动化配置列表
     */
    public function lists()
    {
        return $this->dataLists(new AutoDeviceWechatCircleConfigLists());
    }

    /**
     * 新增微信朋友圈自动化配置
     */
    public function add()
    {
        try {
            $params = $this->request->param();
            $validate = new AutoDeviceWechatCircleConfigValidate();
            if (!$validate->scene('add')->check($params)) {
                return $this->fail($validate->getError());
            }
            $result = AutoDeviceWechatCircleConfigLogic::add($params);
            if ($result) {
                return $this->success('添加成功', AutoDeviceWechatCircleConfigLogic::getReturnData());
            }
            return $this->fail(AutoDeviceWechatCircleConfigLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 更新微信朋友圈自动化配置
     */
    public function update()
    {
        try {
            $params = $this->request->param();
            $validate = new AutoDeviceWechatCircleConfigValidate();
            if (!$validate->scene('update')->check($params)) {
                return $this->fail($validate->getError());
            }
            $result = AutoDeviceWechatCircleConfigLogic::update($params);
            if ($result) {
                return $this->success('更新成功', AutoDeviceWechatCircleConfigLogic::getReturnData());
            }
            return $this->fail(AutoDeviceWechatCircleConfigLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 删除微信朋友圈自动化配置
     */
    public function delete()
    {
        try {
            $id = $this->request->param('id/d', 0);
            if (!$id) {
                return $this->fail('参数错误');
            }
            $result = AutoDeviceWechatCircleConfigLogic::delete($id);
            if ($result) {
                return $this->success('删除成功');
            }
            return $this->fail(AutoDeviceWechatCircleConfigLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取微信朋友圈自动化配置详情
     */
    public function detail()
    {
        try {
            $params = $this->request->param();
            $validate = new AutoDeviceWechatCircleConfigValidate();
            if (!$validate->scene('detail')->check($params)) {
                return $this->fail($validate->getError());
            }
            $result = AutoDeviceWechatCircleConfigLogic::detail($params);
            if ($result) {
                return $this->success('获取成功', AutoDeviceWechatCircleConfigLogic::getReturnData());
            }
            return $this->fail(AutoDeviceWechatCircleConfigLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
