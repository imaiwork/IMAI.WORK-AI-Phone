<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListResponseBody\mediaList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListResponseBody\nonExistMediaIds;
use AlibabaCloud\Tea\Model;

class QueryMediaListResponseBody extends Model
{
    /**
     * @description The list of media files.
     *
     * @var mediaList
     */
    public $mediaList;

    /**
     * @description The IDs of the media files that do not exist. This parameter is not returned when all specified media files exist.
     *
     * @var nonExistMediaIds
     */
    public $nonExistMediaIds;

    /**
     * @description The ID of the request.
     *
     * @example 283DC68C-146F-4489-A2A1-2F88F1472A56
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'mediaList'        => 'MediaList',
        'nonExistMediaIds' => 'NonExistMediaIds',
        'requestId'        => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaList) {
            $res['MediaList'] = null !== $this->mediaList ? $this->mediaList->toMap() : null;
        }
        if (null !== $this->nonExistMediaIds) {
            $res['NonExistMediaIds'] = null !== $this->nonExistMediaIds ? $this->nonExistMediaIds->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryMediaListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaList'])) {
            $model->mediaList = mediaList::fromMap($map['MediaList']);
        }
        if (isset($map['NonExistMediaIds'])) {
            $model->nonExistMediaIds = nonExistMediaIds::fromMap($map['NonExistMediaIds']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
