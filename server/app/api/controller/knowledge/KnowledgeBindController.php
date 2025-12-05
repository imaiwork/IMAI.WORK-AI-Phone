<?php



namespace app\api\controller\knowledge;


use app\common\controller\BaseCommonAdminController;
use app\api\lists\knowledge\KnowledgeBindLists;
use app\api\logic\knowledge\KnowledgeBindLogic;
use app\api\validate\knowledge\KnowledgeBindValidate;


/**
 * KnowledgeBind控制器
 * Class KnowledgeBindController
 * @package app\api\controller\knowledge
 */
class KnowledgeBindController extends BaseCommonAdminController
{


    /**
     * @notes 获取列表
     * @return \think\response\Json
     * @author my
     * @date 2025/04/18 16:19
     */
    public function lists()
    {
        return $this->dataLists(new KnowledgeBindLists());
    }


    /**
     * @notes 添加
     * @return \think\response\Json
     * @author my
     * @date 2025/04/18 16:19
     */
    public function add()
    {
        $params = (new KnowledgeBindValidate())->post()->goCheck('add');
        $result = KnowledgeBindLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(KnowledgeBindLogic::getError());
    }


    /**
     * @notes 编辑
     * @return \think\response\Json
     * @author my
     * @date 2025/04/18 16:19
     */
    public function edit()
    {
        $params = (new KnowledgeBindValidate())->post()->goCheck('edit');
        $result = KnowledgeBindLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(KnowledgeBindLogic::getError());
    }


    /**
     * @notes 删除
     * @return \think\response\Json
     * @author my
     * @date 2025/04/18 16:19
     */
    public function delete()
    {
        $params = (new KnowledgeBindValidate())->post()->goCheck('delete');
        KnowledgeBindLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }


    /**
     * @notes 获取详情
     * @return \think\response\Json
     * @author my
     * @date 2025/04/18 16:19
     */
    public function detail()
    {
        $params = (new KnowledgeBindValidate())->goCheck('detail');
        $result = KnowledgeBindLogic::detail($params);
        return $this->data($result);
    }


}