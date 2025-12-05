<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomEntitiesResponseBody\customEntities;

use AlibabaCloud\Tea\Model;

class customEntity extends Model
{
    /**
     * @example 1
     *
     * @var string
     */
    public $customEntityId;

    /**
     * @example { "finegrainName":"example" }
     *
     * @var string
     */
    public $customEntityInfo;

    /**
     * @example exampleName
     *
     * @var string
     */
    public $customEntityName;
    protected $_name = [
        'customEntityId'   => 'CustomEntityId',
        'customEntityInfo' => 'CustomEntityInfo',
        'customEntityName' => 'CustomEntityName',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->customEntityId) {
            $res['CustomEntityId'] = $this->customEntityId;
        }
        if (null !== $this->customEntityInfo) {
            $res['CustomEntityInfo'] = $this->customEntityInfo;
        }
        if (null !== $this->customEntityName) {
            $res['CustomEntityName'] = $this->customEntityName;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return customEntity
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CustomEntityId'])) {
            $model->customEntityId = $map['CustomEntityId'];
        }
        if (isset($map['CustomEntityInfo'])) {
            $model->customEntityInfo = $map['CustomEntityInfo'];
        }
        if (isset($map['CustomEntityName'])) {
            $model->customEntityName = $map['CustomEntityName'];
        }

        return $model;
    }
}
