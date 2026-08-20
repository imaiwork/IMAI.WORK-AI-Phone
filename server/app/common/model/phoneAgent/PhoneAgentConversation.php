<?php

namespace app\common\model\phoneAgent;

use think\model\concern\SoftDelete;

class PhoneAgentConversation extends PhoneAgentBaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}
