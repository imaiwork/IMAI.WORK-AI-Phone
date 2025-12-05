<?php


namespace app\api\controller;


use app\api\logic\SearchLogic;

/**
 * 搜索
 * Class HotSearchController
 * @package app\api\controller
 */
class SearchController extends BaseApiController
{

    public array $notNeedLogin = ['hotLists'];

    /**
     * @notes 热门搜素
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/22 10:14
     */
    public function hotLists()
    {
        return $this->data(SearchLogic::hotLists());
    }
}
