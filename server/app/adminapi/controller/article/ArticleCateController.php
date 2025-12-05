<?php

namespace app\adminapi\controller\article;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\article\ArticleCateLists;
use app\adminapi\logic\article\ArticleCateLogic;
use app\adminapi\validate\article\ArticleCateValidate;

/**
 * 资讯分类管理控制器
 * Class ArticleCateController
 * @package app\adminapi\controller\article
 */
class ArticleCateController extends BaseAdminController
{

    /**
     * @notes  查看资讯分类列表
     * @return \think\response\Json
     * @author heshihu
     * @date 2022/2/21 17:11
     */
    public function lists()
    {
        return $this->dataLists(new ArticleCateLists());
    }


    /**
     * @notes  添加资讯分类
     * @return \think\response\Json
     * @author heshihu
     * @date 2022/2/21 17:31
     */
    public function add()
    {
        $params = (new ArticleCateValidate())->post()->goCheck('add');
        ArticleCateLogic::add($params);
        return $this->success('添加成功', [], 1, 1);
    }


    /**
     * @notes  编辑资讯分类
     * @return \think\response\Json
     * @author heshihu
     * @date 2022/2/21 17:49
     */
    public function edit()
    {
        $params = (new ArticleCateValidate())->post()->goCheck('edit');
        $result = ArticleCateLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(ArticleCateLogic::getError());
    }


    /**
     * @notes  删除资讯分类
     * @return \think\response\Json
     * @author heshihu
     * @date 2022/2/21 17:52
     */
    public function delete()
    {
        $params = (new ArticleCateValidate())->post()->goCheck('delete');
        ArticleCateLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }


    /**
     * @notes  资讯分类详情
     * @return \think\response\Json
     * @author heshihu
     * @date 2022/2/21 17:54
     */
    public function detail()
    {
        $params = (new ArticleCateValidate())->goCheck('detail');
        $result = ArticleCateLogic::detail($params);
        return $this->data($result);
    }


    /**
     * @notes  更改资讯分类状态
     * @return \think\response\Json
     * @author heshihu
     * @date 2022/2/21 10:15
     */
    public function updateStatus()
    {
        $params = (new ArticleCateValidate())->post()->goCheck('status');
        $result = ArticleCateLogic::updateStatus($params);
        if (true === $result) {
            return $this->success('修改成功', [], 1, 1);
        }
        return $this->fail(ArticleCateLogic::getError());
    }


    /**
     * @notes 获取文章分类
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/10/13 10:54
     */
    public function all()
    {
        $result = ArticleCateLogic::getAllData();
        return $this->data($result);
    }
}
