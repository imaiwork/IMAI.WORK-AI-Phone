<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SearchPipelineResponseBody\pipelineList;

use AlibabaCloud\SDK\Mts\V20140618\Models\SearchPipelineResponseBody\pipelineList\pipeline\notifyConfig;
use AlibabaCloud\Tea\Model;

class pipeline extends Model
{
    /**
     * @var string
     */
    public $creationTime;

    /**
     * @description The ID of the MPS queue.
     *
     * @example d1ce4d3efcb549419193f50f1fcd****
     *
     * @var string
     */
    public $id;

    /**
     * @description The name of the MPS queue.
     *
     * @example example-pipeline-****
     *
     * @var string
     */
    public $name;

    /**
     * @description The Message Service (MNS) configuration.
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
     * @description The role that is assigned to the current RAM user.
     *
     * @example AliyunMTSDefaultRole
     *
     * @var string
     */
    public $role;

    /**
     * @description The type of the MPS queue. Default value: **Standard**. Valid values:
     *
     *   **Boost**: MPS queue with transcoding speed boosted
     *   **Standard**: standard MPS queue
     *   **NarrowBandHDV2**: MPS queue that supports Narrowband HD 2.0
     *   **AIVideoCover**: MPS queue for intelligent snapshot capture
     *   **AIVideoFPShot**: MPS queue for media fingerprinting
     *   **AIVideoCensor**: MPS queue for automated review
     *   **AIVideoMCU**: MPS queue for smart tagging
     *   **AIVideoSummary**: MPS queue for video synopsis
     *   **AIVideoPorn**: MPS queue for pornography detection in videos
     *   **AIAudioKWS**: MPS queue for keyword recognition in audio
     *   **AIAudioASR**: MPS queue for speech-to-text conversion
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
     * @description The state of the MPS queue. Valid values:
     *
     *   **Active**: The MPS queue is active.
     *   **Paused**: The MPS queue is paused.
     *
     * @example Paused
     *
     * @var string
     */
    public $state;
    protected $_name = [
        'creationTime'  => 'CreationTime',
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
        if (null !== $this->creationTime) {
            $res['CreationTime'] = $this->creationTime;
        }
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
        if (isset($map['CreationTime'])) {
            $model->creationTime = $map['CreationTime'];
        }
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
