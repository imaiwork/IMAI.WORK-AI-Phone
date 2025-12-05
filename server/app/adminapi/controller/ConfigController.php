<?php

namespace app\adminapi\controller;

use app\adminapi\logic\ConfigLogic;
use think\response\Json;

/**
 * 配置控制器
 * Class ConfigController
 * @package app\adminapi\controller
 */
class ConfigController extends BaseAdminController
{
    public array $notNeedLogin = ['getConfig', 'dict'];


    /**
     * @notes 基础配置
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/31 11:01
     */
    public function getConfig()
    {
        $data = ConfigLogic::getConfig();
        return $this->data($data);
    }


    /**
     * @notes 更新配置
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/31 11:01
     */
    public function setConfig()
    {
        $postData = $this->request->post();
        $type = $postData['type'] ?? '';
        $name = $postData['name'] ?? '';
        $data = $postData['data'] ?? [];
        ConfigLogic::setConfig($type, $name, $data);
        return $this->success("编辑成功", [], 1, 1);
    }

    /**
     * 获取模型配置信息
     * @return Json
     * @author L
     * @data 2024/8/1 10:36
     */
    public function getModelConfig(): Json
    {
        $data = ConfigLogic::getModelConfig();
        return $this->data($data);
    }

    /**
     * 写入模型配置信息
     * @return Json
     * @author L
     * @data 2024/8/1 10:36
     */
    public function setModelConfig(): Json
    {
        $postData = $this->request->post();
        ConfigLogic::setModelConfig($postData);
        return $this->success();
    }


    /**
     * @notes 根据类型获取字典数据
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/27 19:10
     */
    public function dict()
    {
        $type = $this->request->get('type', '');
        $data = ConfigLogic::getDictByType($type);
        return $this->data($data);
    }


    public function updateByname()
    {
        $postData = $this->request->post();
        $data = ConfigLogic::updateByname($postData);
        if ($data == 10000){
            return $this->success('更新成功');
        }

        return $this->fail('更新失败');
    }
}
