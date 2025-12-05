<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\AddPipelineResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\AddPipelineResponseBody\pipeline\notifyConfig;
use AlibabaCloud\Tea\Model;

class pipeline extends Model
{
    /**
     * @description The ID of the MPS queue.
     *
     * @example ed450ea0bfbd41e29f80a401fb4d****
     *
     * @var string
     */
    public $id;

    /**
     * @description The name of the MPS queue.
     *
     * @example Media Fingerprint
     *
     * @var string
     */
    public $name;

    /**
     * @description The MNS configuration.
     *
     * @var notifyConfig
     */
    public $notifyConfig;

    /**
     * @description The quota that is allocated to the MPS queue.
     *
     * @example 10
     *
     * @var int
     */
    public $quotaAllocate;

    /**
     * @description The role.
     *
     * @example AliyunMTSDefaultRole
     *
     * @var string
     */
    public $role;

    /**
     * @description The type of the MPS queue.
     *
     * @example Standard
     *
     * @var string
     */
    public $speed;

    /**
     * @description The level of the MPS queue.
     *
     * @example 1
     *
     * @var int
     */
    public $speedLevel;

    /**
     * @description The state of the MPS queue.
     *
     *   Active: The MPS queue is active. The jobs in the MPS queue are scheduled and transcoded by MPS.
     *   Paused: The MPS queue is paused. Jobs in the MPS queue are no longer scheduled for transcoding by MPS. All of the jobs in the MPS queue remain in the Submitted state. Jobs that are being transcoded are not affected.
     *
     * @example Active
     *
     * @var string
     */
    public $state;
    protected $_name = [
        'id'            => 'Id',
        'name'          => 'Name',
        'notifyConfig'  => 'NotifyConfig',
        'quotaAllocate' => 'QuotaAllocate',
        'role'          => 'Role',
        'speed'         => 'Speed',
        'speedLevel'    => 'SpeedLevel',
        'state'         => 'State',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->id) {
            $res['Id'] = $this->id;
        }
        if (null !== $this->name) {
            $res['Name'] = $this->name;
        }
        if (null !== $this->notifyConfig) {
            $res['NotifyConfig'] = null !== $this->notifyConfig ? $this->notifyConfig->toMap() : null;
        }
        if (null !== $this->quotaAllocate) {
            $res['QuotaAllocate'] = $this->quotaAllocate;
        }
        if (null !== $this->role) {
            $res['Role'] = $this->role;
        }
        if (null !== $this->speed) {
            $res['Speed'] = $this->speed;
        }
        if (null !== $this->speedLevel) {
            $res['SpeedLevel'] = $this->speedLevel;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return pipeline
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Id'])) {
            $model->id = $map['Id'];
        }
        if (isset($map['Name'])) {
            $model->name = $map['Name'];
        }
        if (isset($map['NotifyConfig'])) {
            $model->notifyConfig = notifyConfig::fromMap($map['NotifyConfig']);
        }
        if (isset($map['QuotaAllocate'])) {
            $model->quotaAllocate = $map['QuotaAllocate'];
        }
        if (isset($map['Role'])) {
            $model->role = $map['Role'];
        }
        if (isset($map['Speed'])) {
            $model->speed = $map['Speed'];
        }
        if (isset($map['SpeedLevel'])) {
            $model->speedLevel = $map['SpeedLevel'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }

        return $model;
    }
}
