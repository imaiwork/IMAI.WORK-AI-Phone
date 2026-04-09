<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\shanjian\ShanjianVideoTaskLists;

class VideoRecordController extends BaseApiController
{
    public function lists()
    {
        return $this->dataLists(new ShanjianVideoTaskLists());
    }
}
