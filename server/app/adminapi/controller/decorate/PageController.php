<?php

namespace app\adminapi\controller\decorate;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\decorate\DecoratePageLogic;
use app\adminapi\validate\decorate\DecoratePageValidate;


/**
 * 装修页面
 * Class DecoratePageController
 * @package app\adminapi\controller\decorate
 */
class PageController extends BaseAdminController
{

    /**
     * @notes 获取装修修页面详情
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/14 18:43
     */
    public function detail()
    {
        $id = $this->request->get('id/d');
        $result = DecoratePageLogic::getDetail($id);
        return $this->data($result);
    }


    /**
     * @notes 保存装修配置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/15 9:57
     */
    public function save()
    {
        $params = (new DecoratePageValidate())->post()->goCheck();
        $result = DecoratePageLogic::save($params);
        if (false === $result) {
            return $this->fail(DecoratePageLogic::getError());
        }
        return $this->success('操作成功', [], 1, 1);
    }
}
