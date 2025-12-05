<?php


namespace app\api\controller;


use app\api\logic\PcLogic;
use think\response\Json;


/**
 * PC
 * Class PcController
 * @package app\api\controller
 */
class PcController extends BaseApiController
{

    public array $notNeedLogin = ['index', 'config', 'infoCenter', 'articleDetail', 'customer'];


    /**
     * @notes 首页数据
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/21 19:15
     */
    public function index()
    {
        $result = PcLogic::getIndexData();
        return $this->data($result);
    }


    /**
     * @notes 全局配置
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/21 19:41
     */
    public function config()
    {
        $result = PcLogic::getConfigData();
        return $this->data($result);
    }


    /**
     * @notes 资讯中心
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/10/19 16:55
     */
    public function infoCenter()
    {
        $result = PcLogic::getInfoCenter();
        return $this->data($result);
    }


    /**
     * @notes 获取文章详情
     * @return Json
     * @author 段誉
     * @date 2022/10/20 15:18
     */
    public function articleDetail()
    {
        $id = $this->request->get('id/d', 0);
        $source = $this->request->get('source/s', 'default');
        $result = PcLogic::getArticleDetail($this->userId, $id, $source);
        return $this->data($result);
    }

    public function customer()
    {
        $result = PcLogic::getCustomerService();
        return $this->data($result);
    }
}
