<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class RegisterCustomFaceResponseBody extends Model
{
    /**
     * @description The ID of the face.
     *
     * @example c6cc71cb44a9491093818faf9d60****
     *
     * @var string
     */
    public $faceId;

    /**
     * @description The ID of the request.
     *
     * @example 91AEA76D-25B5-50DF-9126-AA6BB10FDAF4
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'faceId'    => 'FaceId',
        'requestId' => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->faceId) {
            $res['FaceId'] = $this->faceId;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return RegisterCustomFaceResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FaceId'])) {
            $model->faceId = $map['FaceId'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
