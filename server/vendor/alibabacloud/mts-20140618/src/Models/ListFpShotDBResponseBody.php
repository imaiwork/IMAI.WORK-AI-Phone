<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotDBResponseBody\fpShotDBList;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotDBResponseBody\nonExistIds;
use AlibabaCloud\Tea\Model;

class ListFpShotDBResponseBody extends Model
{
    /**
     * @description The media fingerprint libraries.
     *
     * @var fpShotDBList
     */
    public $fpShotDBList;

    /**
     * @description The IDs of the media fingerprint libraries that do not exist.
     *
     * @var nonExistIds
     */
    public $nonExistIds;

    /**
     * @description The ID of the request.
     *
     * @example 25818875-5F78-4A13-BEF6-D7393642CA58
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'fpShotDBList' => 'FpShotDBList',
        'nonExistIds'  => 'NonExistIds',
        'requestId'    => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpShotDBList) {
            $res['FpShotDBList'] = null !== $this->fpShotDBList ? $this->fpShotDBList->toMap() : null;
        }
        if (null !== $this->nonExistIds) {
            $res['NonExistIds'] = null !== $this->nonExistIds ? $this->nonExistIds->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return ListFpShotDBResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpShotDBList'])) {
            $model->fpShotDBList = fpShotDBList::fromMap($map['FpShotDBList']);
        }
        if (isset($map['NonExistIds'])) {
            $model->nonExistIds = nonExistIds::fromMap($map['NonExistIds']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
