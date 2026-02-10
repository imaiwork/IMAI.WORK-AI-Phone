<?php


namespace app\api\controller;

use app\api\lists\digitalHuman\DigitalHumanAnchorLists;
use app\api\logic\DigitalHumanLogic;
use think\response\Json;

/**
 * HumanController
 * @desc 公共数字人
 */
class DigitalHumanController extends BaseApiController
{

    public array $notNeedLogin = ['lists','supplement'];

    /**
     * @desc 公共数字人形象列表
     * @return Json
     * @date 2024/9/28 18:10
     */
    public function anchorPublicLists()
    {
        return $this->dataLists(new DigitalHumanAnchorLists());
    }

    /**
     * @desc 公共数字人形象列表、兼容旧数据
     * @return Json
     * @date 2024/9/28 18:10
     */
    public function anchorLists()
    {
        $params = $this->request->get();
        $result = DigitalHumanLogic::getDigitalHumanAnchorList($params);
        return $this->data($result);
    }

    /**
     * @desc 创建1刻3形象任务
     * @return \think\response\Json
     */
    public function createAnchor()
    {
        $data   = $this->request->post();
        $result = DigitalHumanLogic::createPublicAnchor($data);
        if ($result) {
            return $this->data(DigitalHumanLogic::getReturnData());
        }
        return $this->fail(DigitalHumanLogic::getError());
    }

    public function deletePublicAnchor()
    {
        $params = $this->request->post();
        return DigitalHumanLogic::deletePublicAnchor($params) ? $this->success('ok') : $this->fail(DigitalHumanLogic::getError());
    }

    public function supplement()
    {
        return DigitalHumanLogic::supplement() ? $this->success('ok') : $this->fail(DigitalHumanLogic::getError());
    }
}
