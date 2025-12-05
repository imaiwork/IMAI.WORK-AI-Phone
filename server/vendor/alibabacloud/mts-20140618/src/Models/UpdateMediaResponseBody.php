<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaResponseBody\media;
use AlibabaCloud\Tea\Model;

class UpdateMediaResponseBody extends Model
{
    /**
     * @description The information about the media file.
     *
     * @var media
     */
    public $media;

    /**
     * @description The ID of the request.
     *
     * @example 6A88246F-C91F-42BD-BABE-DB0DF993F960
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'media'     => 'Media',
        'requestId' => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->media) {
            $res['Media'] = null !== $this->media ? $this->media->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return UpdateMediaResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Media'])) {
            $model->media = media::fromMap($map['Media']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
