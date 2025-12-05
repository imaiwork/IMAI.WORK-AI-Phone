<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobListResponseBody\mediaCensorJobList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobListResponseBody\nonExistIds;
use AlibabaCloud\Tea\Model;

class QueryMediaCensorJobListResponseBody extends Model
{
    /**
     * @description The content moderation jobs.
     *
     * @var mediaCensorJobList
     */
    public $mediaCensorJobList;

    /**
     * @description The token that is used to retrieve the next page of the query results. The value is a UUID that contains 32 characters. If the returned query results cannot be displayed within one page, this parameter is returned. The value of this parameter is updated for each query.
     *
     * @example 9b1a42bc6e8d46e6a1383b7e7f01****
     *
     * @var string
     */
    public $nextPageToken;

    /**
     * @description The IDs of the jobs that do not exist. This parameter is not returned if all specified jobs are found.
     *
     * @var nonExistIds
     */
    public $nonExistIds;

    /**
     * @description The ID of the request.
     *
     * @example D1D5C080-8E2F-5030-8AB4-13092F17631B
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'mediaCensorJobList' => 'MediaCensorJobList',
        'nextPageToken'      => 'NextPageToken',
        'nonExistIds'        => 'NonExistIds',
        'requestId'          => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaCensorJobList) {
            $res['MediaCensorJobList'] = null !== $this->mediaCensorJobList ? $this->mediaCensorJobList->toMap() : null;
        }
        if (null !== $this->nextPageToken) {
            $res['NextPageToken'] = $this->nextPageToken;
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
     * @return QueryMediaCensorJobListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaCensorJobList'])) {
            $model->mediaCensorJobList = mediaCensorJobList::fromMap($map['MediaCensorJobList']);
        }
        if (isset($map['NextPageToken'])) {
            $model->nextPageToken = $map['NextPageToken'];
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
