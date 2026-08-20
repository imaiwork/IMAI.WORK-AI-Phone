<?php

namespace app\common\model\phoneAgent;

class PhoneAgentObservation extends PhoneAgentBaseModel
{
    public function getAccessibilityTreeAttr($value): array
    {
        return $this->decodeJsonField($value);
    }

    public function setAccessibilityTreeAttr($value): string
    {
        return $this->encodeJsonField($value);
    }

    public function getRawDataAttr($value): array
    {
        return $this->decodeJsonField($value);
    }

    public function setRawDataAttr($value): string
    {
        return $this->encodeJsonField($value);
    }
}
