<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\AddMediaResponseBody\media;
use AlibabaCloud\Tea\Model;

class AddMediaResponseBody extends Model
{
    /**
     * @description The detailed information about the media file.
     *
     * @var media
     */
    public $media;

    /**
     * @description The ID of the request.
     *
     * @example 05F8B913-E9F3-4A6F-9922-48CADA0FFAAD
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
     * @return AddMediaResponseBody
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
