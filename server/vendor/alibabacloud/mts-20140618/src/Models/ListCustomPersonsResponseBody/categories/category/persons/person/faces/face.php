<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomPersonsResponseBody\categories\category\persons\person\faces;

use AlibabaCloud\Tea\Model;

class face extends Model
{
    /**
     * @description The ID of the face.
     *
     * @example 15****
     *
     * @var string
     */
    public $faceId;

    /**
     * @description The URL of the facial image that was registered for the figure.
     *
     * @example http://example-****.jpeg
     *
     * @var string
     */
    public $imageUrl;
    protected $_name = [
        'faceId'   => 'FaceId',
        'imageUrl' => 'ImageUrl',
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
        if (null !== $this->imageUrl) {
            $res['ImageUrl'] = $this->imageUrl;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return face
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FaceId'])) {
            $model->faceId = $map['FaceId'];
        }
        if (isset($map['ImageUrl'])) {
            $model->imageUrl = $map['ImageUrl'];
        }

        return $model;
    }
}
