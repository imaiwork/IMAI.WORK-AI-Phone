<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class AddPipelineRequest extends Model
{
    /**
     * @description The name of the MPS queue. The name can be up to 128 bytes in size.
     *
     * This parameter is required.
     * @example test-pipeline
     *
     * @var string
     */
    public $name;

    /**
     * @description The Message Service (MNS) configuration.
     *
     * @example {"Topic":"mts-topic-1"}
     *
     * @var string
     */
    public $notifyConfig;

    /**
     * @var string
     */
    public $ownerAccount;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @var string
     */
    public $resourceOwnerAccount;

    /**
     * @var int
     */
    public $resourceOwnerId;

    /**
     * @description The role.
     *
     * @example AliyunMTSDefaultRole
     *
     * @var string
     */
    public $role;

    /**
     * @description The type of the MPS queue. Valid values:
     *
     *   **Boost**: MPS queue with transcoding speed boosted.
     *   **Standard**: standard MPS queue.
     *   **NarrowBandHDV2**: MPS queue that supports Narrowband HD 2.0.
     *   **AIVideoCover**: MPS queue for intelligent snapshot capture.
     *   **AIVideoTag**: MPS queue for video tagging. The supported regions are China (Shanghai), China (Beijing), and China (Hangzhou).
     *
     * Default value: **Standard**.
     * @example Standard
     *
     * @var string
     */
    public $speed;

    /**
     * @description The level of the MPS queue. Valid values: **1 to 3**.
     *
     * @example 1
     *
     * @var int
     */
    public $speedLevel;
    protected $_name = [
        'name'                 => 'Name',
        'notifyConfig'         => 'NotifyConfig',
        'ownerAccount'         => 'OwnerAccount',
        'ownerId'              => 'OwnerId',
        'resourceOwnerAccount' => 'ResourceOwnerAccount',
        'resourceOwnerId'      => 'ResourceOwnerId',
        'role'                 => 'Role',
        'speed'                => 'Speed',
        'speedLevel'           => 'SpeedLevel',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->name) {
            $res['Name'] = $this->name;
        }
        if (null !== $this->notifyConfig) {
            $res['NotifyConfig'] = $this->notifyConfig;
        }
        if (null !== $this->ownerAccount) {
            $res['OwnerAccount'] = $this->ownerAccount;
        }
        if (null !== $this->ownerId) {
            $res['OwnerId'] = $this->ownerId;
        }
        if (null !== $this->resourceOwnerAccount) {
            $res['ResourceOwnerAccount'] = $this->resourceOwnerAccount;
        }
        if (null !== $this->resourceOwnerId) {
            $res['ResourceOwnerId'] = $this->resourceOwnerId;
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

        return $res;
    }

    /**
     * @param array $map
     *
     * @return AddPipelineRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Name'])) {
            $model->name = $map['Name'];
        }
        if (isset($map['NotifyConfig'])) {
            $model->notifyConfig = $map['NotifyConfig'];
        }
        if (isset($map['OwnerAccount'])) {
            $model->ownerAccount = $map['OwnerAccount'];
        }
        if (isset($map['OwnerId'])) {
            $model->ownerId = $map['OwnerId'];
        }
        if (isset($map['ResourceOwnerAccount'])) {
            $model->resourceOwnerAccount = $map['ResourceOwnerAccount'];
        }
        if (isset($map['ResourceOwnerId'])) {
            $model->resourceOwnerId = $map['ResourceOwnerId'];
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

        return $model;
    }
}
