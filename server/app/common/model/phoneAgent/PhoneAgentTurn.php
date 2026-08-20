<?php

namespace app\common\model\phoneAgent;

class PhoneAgentTurn extends PhoneAgentBaseModel
{
    public const STATUS_CREATED = 'created';
    public const STATUS_MODEL_PENDING = 'model_pending';
    public const STATUS_PARSED = 'parsed';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function getRequestMessagesAttr($value): array
    {
        return $this->decodeJsonField($value);
    }

    public function setRequestMessagesAttr($value): string
    {
        return $this->encodeJsonField($value);
    }

    public function getModelResponseAttr($value): array
    {
        return $this->decodeJsonField($value);
    }

    public function setModelResponseAttr($value): string
    {
        return $this->encodeJsonField($value);
    }

    public function getParsedActionAttr($value): array
    {
        return $this->decodeJsonField($value);
    }

    public function setParsedActionAttr($value): string
    {
        return $this->encodeJsonField($value);
    }

    public function getUsageAttr($value): array
    {
        return $this->decodeJsonField($value);
    }

    public function setUsageAttr($value): string
    {
        return $this->encodeJsonField($value);
    }
}
