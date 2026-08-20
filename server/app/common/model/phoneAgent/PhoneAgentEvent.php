<?php

namespace app\common\model\phoneAgent;

class PhoneAgentEvent extends PhoneAgentBaseModel
{
    public function getEventDataAttr($value): array
    {
        return $this->decodeJsonField($value);
    }

    public function setEventDataAttr($value): string
    {
        return $this->encodeJsonField($value);
    }
}
