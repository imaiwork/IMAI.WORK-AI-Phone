<?php


namespace app\adminapi\controller\finance;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\finance\AccountLogLists;
use app\common\enum\user\AccountLogEnum;

/***
 * 账户流水控制器
 * Class AccountLogController
 * @package app\adminapi\controller
 */
class AccountLogController extends BaseAdminController
{


    /**
     * @notes 账户流水明细
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/24 15:25
     */
    public function lists()
    {
        return $this->dataLists(new AccountLogLists());
    }


    /**
     * @notes 用户余额变动类型
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/24 15:25
     */
    public function getUmChangeType()
    {
        return $this->data(AccountLogEnum::getUserMoneyChangeTypeDesc());
    }


    /**
     * @notes 用户余额变动类型
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/24 15:25
     */
    public function getTokensChangeType()
    {
        return $this->data(AccountLogEnum::getUserTokensChangeTypeDesc());
    }
}
