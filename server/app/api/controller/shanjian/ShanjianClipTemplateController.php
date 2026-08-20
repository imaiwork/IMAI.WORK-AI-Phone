<?php

namespace app\api\controller\shanjian;

use app\api\controller\BaseApiController;
use app\api\lists\shanjian\ShanjianClipTemplateLists;

class ShanjianClipTemplateController extends BaseApiController
{
    public function lists()
    {
        return $this->dataLists(new ShanjianClipTemplateLists());
    }
}
