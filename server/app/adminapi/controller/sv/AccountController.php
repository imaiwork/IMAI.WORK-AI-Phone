<?php
namespace app\adminapi\controller\sv;
use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\sv\AccountLists;
use think\exception\HttpResponseException;
/**
 *
 * Class WechatController
 * @package app\adminapi\controller
 */
class AccountController extends BaseAdminController
{ 
  /**
     * @notes 列表
     * @author Lee
     * @date 2025-05-14 09:40:09
     */
    public function lists()
    {  
       return $this->dataLists(new AccountLists());
    }

}