<?php

namespace app\api\controller\agent;

use app\api\controller\BaseApiController;
use app\api\lists\coze\AgentCateLists;

class AgentCateController extends BaseApiController
{

    public array $notNeedLogin = ['lists'];


    public function lists()
    {
        return $this->dataLists(new AgentCateLists());
    }
}
