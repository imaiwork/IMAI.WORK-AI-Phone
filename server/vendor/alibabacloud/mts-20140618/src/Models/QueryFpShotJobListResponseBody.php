<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\nonExistIds;
use AlibabaCloud\Tea\Model;

class QueryFpShotJobListResponseBody extends Model
{
    /**
     * @description The information about media fingerprint analysis jobs.
     *
     * @var fpShotJobList
     */
    public $fpShotJobList;

    /**
     * @description The token that is used to retrieve the next page of the query results. The value is a 32-bit UUID. If the returned query results cannot be displayed within one page, this parameter is returned. The value of this parameter is updated for each query.
     *
     * @example b11c171cced04565b1f38f1ecc39****
     *
     * @var string
     */
    public $nextPageToken;

    /**
     * @description The IDs of the jobs that do not exist.
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
        'fpShotJobList' => 'FpShotJobList',
        'nextPageToken' => 'NextPageToken',
        'nonExistIds'   => 'NonExistIds',
        'requestId'     => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpShotJobList) {
            $res['FpShotJobList'] = null !== $this->fpShotJobList ? $this->fpShotJobList->toMap() : null;
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
     * @return QueryFpShotJobListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpShotJobList'])) {
            $model->fpShotJobList = fpShotJobList::fromMap($map['FpShotJobList']);
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
