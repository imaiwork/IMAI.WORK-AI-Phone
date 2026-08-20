<?php

namespace app\common\model\phoneAgent;

class PhoneAgentTask extends PhoneAgentBaseModel
{
    public const STATUS_CREATED = 'created';
    public const STATUS_OBSERVING = 'observing';
    public const STATUS_MODEL_PENDING = 'model_pending';
    public const STATUS_DISPATCHING = 'dispatching';
    public const STATUS_WAITING_REPORT = 'waiting_report';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';

    public const FINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELED,
    ];

    public function isFinal(): bool
    {
        return in_array((string)$this->status, self::FINAL_STATUSES, true);
    }
}
