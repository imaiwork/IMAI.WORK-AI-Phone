<?php

namespace app\adminapi\controller\audio;


use app\adminapi\lists\audio\AudioLists;
use app\adminapi\logic\audio\AudioLogic;
use app\adminapi\validate\audio\AudioValidate;
use app\adminapi\controller\BaseAdminController;
use think\response\Json;



/**
 *
 * Class WechatController
 * @package app\adminapi\controller
 */
class AudioController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function lists()
    {
        return $this->dataLists(new AudioLists());
    }


    /**
     * @desc 删除
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function delete()
    {
        $params = (new AudioValidate())->post()->goCheck('delete');
        return AudioLogic::delete($params) ? $this->success() : $this->fail(AudioLogic::getError());
    }


    /**
     * @desc 详情
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function detail()
    {
        $id = $this->request->get('id/d');
        if (empty($id)) {
            return $this->fail("参数丢失");
        }
        return AudioLogic::detail($id) ? $this->data(AudioLogic::getReturnData()) : $this->fail(AudioLogic::getError());
    }
}
