<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class CreateCustomEntityResponseBody extends Model
{
    /**
     * @example 1
     *
     * @var string
     */
    public $customEntityId;

    /**
     * @example 580e8ce3-3b80-44c5-9f3f-36ac3cc5bdd5
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'customEntityId' => 'CustomEntityId',
        'requestId'      => 'RequestId',
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
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return CreateCustomEntityResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CustomEntityId'])) {
            $model->customEntityId = $map['CustomEntityId'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
