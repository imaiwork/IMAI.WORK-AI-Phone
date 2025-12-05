<?php

namespace app\adminapi\controller\assistants;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\assistants\AssistantsLists;
use app\adminapi\logic\assistants\AssistantsLogic;
use app\adminapi\validate\assistants\AssistantsValidate;
use think\response\Json;


/**
 * 微信
 * Class WechatController
 * @package app\api\controller
 */
class AssistantsController extends BaseAdminController
{
    /**
     * @notes 助手列表
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {
        return $this->dataLists(new AssistantsLists());
    }

    /**
     * @notes 添加助手
     * @return Json
     * @author 段誉
     * @date 2022/9/20 17:01
     */
    public function add(): Json
    {
        $params = (new AssistantsValidate())->post()->goCheck("add", ['admin_id' => $this->adminId]);
        return AssistantsLogic::add($params) ? $this->success(data: AssistantsLogic::getReturnData()) : $this->fail(AssistantsLogic::getError());
    }

    /**
     * @notes 助手详情
     * @return Json
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public function detail(): Json
    {
        $id = $this->request->get('id/d');
        if (empty($id)) {
            return $this->fail("参数丢失");
        }
        $result = AssistantsLogic::detail($id);
        return $this->data($result);
    }

    /**
     * @notes 修改
     * @return Json
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public function edit(): Json
    {
        $params = (new AssistantsValidate())->post()->goCheck("edit");
        $result = AssistantsLogic::edit($params);
        return $result ? $this->success(data: AssistantsLogic::getReturnData()) : $this->fail(AssistantsLogic::getError());
    }

    /**
     * @notes 删除助手
     * @return Json
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public function delete(): Json
    {
        $id = $this->request->post('id/d');
        if (empty($id)) {
            return $this->fail("参数丢失");
        }
        return AssistantsLogic::delete($id) ? $this->success() : $this->fail(AssistantsLogic::getError());
    }

    /**
     * 修改状态的
     * @return Json
     * @author L
     * @data 2024/7/2 15:12
     */
    public function changeStatus(): Json
    {
        $id = $this->request->post('id/d');
        if (empty($id)) {
            return $this->fail("参数丢失");
        }
        return AssistantsLogic::changeStatus($id) ? $this->success() : $this->fail(AssistantsLogic::getError());
    }


    /**
     * @notes 通用聊天
     * @author L
     * @date 2024-07-02 16:25:03    
     */
    public function chat()
    {
        $result = AssistantsLogic::chat();
        return $result ? $this->data(AssistantsLogic::getReturnData()) : $this->fail(AssistantsLogic::getError());
    }

    /**
     * @notes 更新通用聊天
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function updateChat()
    {
        $params = $this->request->post();
        $result = AssistantsLogic::updateChat($params);
        return $result ? $this->success(data: AssistantsLogic::getReturnData()) : $this->fail(AssistantsLogic::getError());
    }


    public function check(){
        try {

            $res = AssistantsLogic::check();
            $data['import'] = $res;
            if ($res){
                return $this->success('可以导入',$data,1,0);
            }
            return $this->fail('文件已导入',$data,0,0);
        } catch (\Exception $e) {
            return $this->fail('错误: ' . $e->getMessage(),['import'=>false],0,0);
        }
    }

    public function import(){
        try {
            $res = AssistantsLogic::import();
            $data['import'] = $res;
            if ($res){
                return $this->success('导入成功',$data,1,0);
            }
            $this->fail('导入失败',$data,0,0);
        } catch (\Exception $e) {
            return $this->fail('导入失败: ' . $e->getMessage(),['import'=>false],0,0);
        }
    }
}
