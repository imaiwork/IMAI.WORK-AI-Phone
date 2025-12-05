<?php


namespace app\adminapi\controller\setting\web;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\web\WebSettingLogic;
use app\adminapi\validate\setting\WebSettingValidate;

/**
 * 网站设置
 * Class WebSettingController
 * @package app\adminapi\controller\setting
 */
class WebSettingController extends BaseAdminController
{

    /**
     * @notes 获取网站信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/28 15:44
     */
    public function getWebsite()
    {
        $result = WebSettingLogic::getWebsiteInfo();
        return $this->data($result);
    }


    /**
     * @notes 设置网站信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/28 15:45
     */
    public function setWebsite()
    {
        $params = (new WebSettingValidate())->post()->goCheck('website');
        WebSettingLogic::setWebsiteInfo($params);
        return $this->success('设置成功', [], 1, 1);
    }



    /**
     * @notes 获取备案信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/28 16:10
     */
    public function getCopyright()
    {
        $result = WebSettingLogic::getCopyright();
        return $this->data($result);
    }


    /**
     * @notes 设置备案信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/28 16:10
     */
    public function setCopyright()
    {
        $params = $this->request->post();
        $result = WebSettingLogic::setCopyright($params);
        if (false === $result) {
            return $this->fail(WebSettingLogic::getError() ?: '操作失败');
        }
        return $this->success('设置成功', [], 1, 1);
    }


    /**
     * @notes 设置政策协议
     * @return \think\response\Json
     * @author ljj
     * @date 2022/2/15 11:00 上午
     */
    public function setAgreement()
    {
        $params = $this->request->post();
        WebSettingLogic::setAgreement($params);
        return $this->success('设置成功', [], 1, 1);
    }


    /**
     * @notes 获取政策协议
     * @return \think\response\Json
     * @author ljj
     * @date 2022/2/15 11:16 上午
     */
    public function getAgreement()
    {
        $result = WebSettingLogic::getAgreement();
        return $this->data($result);
    }


    public function getClient()
    {
        $result = WebSettingLogic::getClient();
        return $this->data($result);
    }


    /**
     * @notes 设置备案信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/28 16:10
     */
    public function setClient()
    {
        $params = $this->request->post();
        $result = WebSettingLogic::setClient($params);
        if (false === $result) {
            return $this->fail(WebSettingLogic::getError() ?: '操作失败');
        }
        return $this->success('设置成功', [], 1, 1);
    }
}
