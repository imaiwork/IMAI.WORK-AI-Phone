<?php


namespace app\api\controller\notice;

use app\api\controller\BaseApiController;
use app\api\lists\notice\NoticeMnpSettingLists;

/**
 * 通知控制器
 * Class NoticeController
 * @package app\api\controller\notice
 */
class NoticeController extends BaseApiController
{
    /**
     * @notes 查看通知设置列表
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 11:18
     */
    public function settingMnpLists()
    {
        return $this->dataLists(new NoticeMnpSettingLists());
    }
}
