<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\nonExistMediaInfoJobIds;
use AlibabaCloud\Tea\Model;

class QueryMediaInfoJobListResponseBody extends Model
{
    /**
     * @description The details of each returned media information analysis job.
     *
     * @var mediaInfoJobList
     */
    public $mediaInfoJobList;

    /**
     * @description Nonexistent media information analysis jobs.
     *
     * @var nonExistMediaInfoJobIds
     */
    public $nonExistMediaInfoJobIds;

    /**
     * @description The ID of the request.
     *
     * @example 46A04AA5-B119-41BB-B750-7C5327AC3E7A
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'mediaInfoJobList'        => 'MediaInfoJobList',
        'nonExistMediaInfoJobIds' => 'NonExistMediaInfoJobIds',
        'requestId'               => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaInfoJobList) {
            $res['MediaInfoJobList'] = null !== $this->mediaInfoJobList ? $this->mediaInfoJobList->toMap() : null;
        }
        if (null !== $this->nonExistMediaInfoJobIds) {
            $res['NonExistMediaInfoJobIds'] = null !== $this->nonExistMediaInfoJobIds ? $this->nonExistMediaInfoJobIds->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryMediaInfoJobListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaInfoJobList'])) {
            $model->mediaInfoJobList = mediaInfoJobList::fromMap($map['MediaInfoJobList']);
        }
        if (isset($map['NonExistMediaInfoJobIds'])) {
            $model->nonExistMediaInfoJobIds = nonExistMediaInfoJobIds::fromMap($map['NonExistMediaInfoJobIds']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
