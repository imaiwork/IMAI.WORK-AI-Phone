<?php
namespace app\adminapi\controller\assistants;


use app\adminapi\lists\assistants\SceneLists;
use app\adminapi\logic\assistants\SceneLogic;
use app\adminapi\validate\assistants\SceneValidate;
use app\adminapi\controller\BaseAdminController;
use think\response\Json;



/**
 * 
 * Class WechatController
 * @package app\adminapi\controller
 */
class SceneController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function lists()
    {
        return $this->dataLists(new SceneLists());
    }


    /**
     * @notes 添加
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function add():Json
    {
        $params = (new SceneValidate())->post()->goCheck('add');
        return SceneLogic::add($params) ? $this->success(data: SceneLogic::getReturnData()) : $this->fail(SceneLogic::getError());
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
        return SceneLogic::delete($params) ? $this->success() : $this->fail(SceneLogic::getError());
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
        $result = SceneLogic::detail($id);
        return $this->data(SceneLogic::getReturnData());
    }     


    /**
     * @notes 修改
     * @return Json
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function edit():Json
    {
        $postData = (new SceneValidate())->post()->goCheck('edit');
        $edit      = SceneLogic::edit($postData);
        return $edit ? $this->success() : $this->fail(SceneLogic::getError());
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
        return SceneLogic::changeStatus($id) ? $this->success() : $this->fail(SceneLogic::getError());
    }

    public function import(){
        try {
            $res = SceneLogic::import();
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
            $res = SceneLogic::check();
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
                        