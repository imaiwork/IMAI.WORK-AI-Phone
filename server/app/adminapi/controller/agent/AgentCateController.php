<?php
namespace app\adminapi\controller\agent;


use app\adminapi\lists\coze\AgentCateLists;
use app\adminapi\logic\coze\AgentCateLogic;
use app\adminapi\validate\coze\AgentCateValidate;
use app\adminapi\controller\BaseAdminController;
use think\response\Json;



/**
 * 
 * @package app\adminapi\controller
 */
class AgentCateController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function lists()
    {
        return $this->dataLists(new AgentCateLists());
    }


    /**
     * @notes 添加
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function add():Json
    {
        $params = (new AgentCateValidate())->post()->goCheck('add');
        return AgentCateLogic::add($params) ? $this->success(data: AgentCateLogic::getReturnData()) : $this->fail(AgentCateLogic::getError());
    }


    /**
     * @notes 删除
     * @return Json
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function delete():Json
    {
        $params = $this->request->post();
        return AgentCateLogic::delete($params) ? $this->success() : $this->fail(AgentCateLogic::getError());
    }


    /**
     * @notes 详情
     * @return Json
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function detail():Json
    {
        $id = $this->request->get('id/d');
        if (empty($id)) {
            return $this->fail('参数丢失');
        }
        $result = AgentCateLogic::detail($id);
        return $result ? $this->data(AgentCateLogic::getReturnData()) : $this->fail(AgentCateLogic::getError());
    }


    /**
     * @notes 修改
     * @return Json
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function update():Json
    {
        $postData = (new AgentCateValidate())->post()->goCheck('edit');
        $edit      = AgentCateLogic::edit($postData);
        return $edit ? $this->success() : $this->fail(AgentCateLogic::getError());
    }

    /**
     * 修改状态的
     * @return Json
     * @author L
     * @data 2024/7/2 15:12
     */
    public function changeStatus():Json
    {
        $id = $this->request->post('id/d');
        if (empty($id)) {
            return $this->fail("参数丢失");
        }
        return AgentCateLogic::changeStatus($id) ? $this->success() : $this->fail(AgentCateLogic::getError());
    }

    public function import(){
        try {
            $res = AgentCateLogic::import();
            $data['import'] = $res;
            if ($res){
                return $this->success('导入成功',$data,1,0);
            }
            $this->fail('导入失败',$data,0,0);
        } catch (\Exception $e) {
            return $this->fail('导入失败: ' . $e->getMessage(),['import'=>false],0,0);
        }
    }

    public function check(){
        try {
            $res = AgentCateLogic::check();
            $data['import'] = $res;
            if ($res){
                return $this->success('可以导入',$data,1,0);
            }
            return $this->fail('文件已导入',$data,0,0);
        } catch (\Exception $e) {
            return $this->fail('错误: ' . $e->getMessage(),['import'=>false],0,0);
        }
    }
}
                        