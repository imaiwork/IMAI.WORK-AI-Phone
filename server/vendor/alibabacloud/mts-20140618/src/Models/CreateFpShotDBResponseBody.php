<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\CreateFpShotDBResponseBody\fpShotDB;
use AlibabaCloud\Tea\Model;

class CreateFpShotDBResponseBody extends Model
{
    /**
     * @description The details of the media fingerprint library.
     *
     * @var fpShotDB
     */
    public $fpShotDB;

    /**
     * @description The ID of the request.
     *
     * @example 25818875-5F78-4A13-BEF6-D7393642CA58
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'fpShotDB'  => 'FpShotDB',
        'requestId' => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpShotDB) {
            $res['FpShotDB'] = null !== $this->fpShotDB ? $this->fpShotDB->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return CreateFpShotDBResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpShotDB'])) {
            $model->fpShotDB = fpShotDB::fromMap($map['FpShotDB']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
