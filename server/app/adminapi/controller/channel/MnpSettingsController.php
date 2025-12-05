<?php

namespace app\adminapi\controller\channel;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\channel\MnpSettingsLogic;
use app\adminapi\validate\channel\MnpSettingsValidate;
use app\adminapi\validate\channel\MnpShareSettingsValidate;
use app\common\service\wechat\WeChatUrllinkService;

/**
 * 小程序设置
 * Class MnpSettingsController
 * @package app\adminapi\controller\channel
 */
class MnpSettingsController extends BaseAdminController
{
    /**
     * @notes 获取小程序配置
     * @return \think\response\Json
     * @author ljj
     * @date 2022/2/16 9:38 上午
     */
    public function getConfig()
    {
        $result = (new MnpSettingsLogic())->getConfig();
        return $this->data($result);
    }

    /**
     * @notes 设置小程序配置
     * @return \think\response\Json
     * @author ljj
     * @date 2022/2/16 9:51 上午
     */
    public function setConfig()
    {
        $params = (new MnpSettingsValidate())->post()->goCheck();
        (new MnpSettingsLogic())->setConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }

    /**
     * @notes 上传小程序
     * @return \think\response\Json
     * @author mjf
     * @date 2025/1/8 17:28
     */
    public function uploadMnp(): \think\response\Json
    {
        $params = $this->request->post();
        $result = (new MnpSettingsLogic())->uploadMnp($params);
        if (false === $result) {
            return $this->fail(MnpSettingsLogic::getError());
        }
        return $this->success('操作成功', $result);
    }

    /**
     * @notes 获取小程序客户端本地版本号
     * @return \think\response\Json
     * @throws \Exception
     * @author mjf
     * @date 2025/1/8 17:28
     */
    public function getMnpLocalVersion(): \think\response\Json
    {
        $result = (new MnpSettingsLogic())->getMnpLocalVersion();
        return $result ? $this->success('', $result) : $this->fail(MnpSettingsLogic::getError());
    }

    /**
     * @notes 获取小程序客户端版本号
     * @return \think\response\Json
     * @throws \Exception
     * @author mjf
     * @date 2025/1/8 17:28
     */
    public function getMnpVersion(): \think\response\Json
    {
        $result = (new WeChatUrllinkService())->getVersionList();
        return $result ? $this->success('', $result) : $this->fail(MnpSettingsLogic::getError());
    }

    /**
     * @notes 获取小程序分享配置
     * @return \think\response\Json
     * @author ljj
     * @date 2022/2/16 9:38 上午
     */
    public function getShareConfig()
    {
        $result = (new MnpSettingsLogic())->getShareConfig();
        return $this->data($result);
    }

    /**
     * @notes 设置小程序配置
     * @return \think\response\Json
     * @author ljj
     * @date 2022/2/16 9:51 上午
     */
    public function setShareConfig()
    {
        $params = (new MnpShareSettingsValidate())->post()->goCheck();
        (new MnpSettingsLogic())->setShareConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }

}
