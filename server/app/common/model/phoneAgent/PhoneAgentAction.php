<?php

namespace app\common\model\phoneAgent;

class PhoneAgentAction extends PhoneAgentBaseModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_IGNORED = 'ignored';
    public const STATUS_CANCELED = 'canceled';

    public function getActionPayloadAttr($value): array
    {
        return $this->decodeJsonField($value);
    }

    public function setActionPayloadAttr($value): string
    {
        return $this->encodeJsonField($value);
    }

    public function getWsPayloadAttr($value): array
    {
        return $this->decodeJsonField($value);
    }

    public function setWsPayloadAttr($value): string
    {
        return $this->encodeJsonField($value);
    }

    public function getResultAttr($value): array
    {
        return $this->decodeJsonField($value);
    }

    public function setResultAttr($value): string
    {
        return $this->encodeJsonField($value);
    }
}
