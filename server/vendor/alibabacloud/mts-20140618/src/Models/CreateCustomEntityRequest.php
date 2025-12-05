<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class CreateCustomEntityRequest extends Model
{
    /**
     * @description This parameter is required.
     *
     * @example landmark
     *
     * @var string
     */
    public $algorithm;

    /**
     * @example { "finegrainName":"examplName" }
     *
     * @var string
     */
    public $customEntityInfo;

    /**
     * @description This parameter is required.
     *
     * @var string
     */
    public $customEntityName;

    /**
     * @description This parameter is required.
     *
     * @example 1
     *
     * @var string
     */
    public $customGroupId;

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
    protected $_name = [
        'algorithm'            => 'Algorithm',
        'customEntityInfo'     => 'CustomEntityInfo',
        'customEntityName'     => 'CustomEntityName',
        'customGroupId'        => 'CustomGroupId',
        'ownerAccount'         => 'OwnerAccount',
        'ownerId'              => 'OwnerId',
        'resourceOwnerAccount' => 'ResourceOwnerAccount',
        'resourceOwnerId'      => 'ResourceOwnerId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->algorithm) {
            $res['Algorithm'] = $this->algorithm;
        }
        if (null !== $this->customEntityInfo) {
            $res['CustomEntityInfo'] = $this->customEntityInfo;
        }
        if (null !== $this->customEntityName) {
            $res['CustomEntityName'] = $this->customEntityName;
        }
        if (null !== $this->customGroupId) {
            $res['CustomGroupId'] = $this->customGroupId;
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

        return $res;
    }

    /**
     * @param array $map
     *
     * @return CreateCustomEntityRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Algorithm'])) {
            $model->algorithm = $map['Algorithm'];
        }
        if (isset($map['CustomEntityInfo'])) {
            $model->customEntityInfo = $map['CustomEntityInfo'];
        }
        if (isset($map['CustomEntityName'])) {
            $model->customEntityName = $map['CustomEntityName'];
        }
        if (isset($map['CustomGroupId'])) {
            $model->customGroupId = $map['CustomGroupId'];
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

        return $model;
    }
}
