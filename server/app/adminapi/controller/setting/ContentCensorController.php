<?php


namespace app\adminapi\controller\setting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\ContentCensorLogic;
use think\response\Json;

/**
 * 内容审核控制器
 */
class ContentCensorController extends BaseAdminController
{
    /**
     * @notes 内容审核配置详情
     * @return Json
     * @author kb
     */
    public function detail(): Json
    {
        $config = ContentCensorLogic::detail();
        return $this->data($config);
    }

    /**
     * @notes 内容审核配置保存
     * @return Json
     * @author kb
     */
    public function save(): Json
    {
        ContentCensorLogic::save($this->request->post());
        return $this->success('设置成功',[],1,1);
    }
}