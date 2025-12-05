<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\nonExistSnapshotJobIds;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList;
use AlibabaCloud\Tea\Model;

class QuerySnapshotJobListResponseBody extends Model
{
    /**
     * @description The OSS object that is used as the input file.
     *
     * @example b11c171cced04565b1f38f1ecc39****
     *
     * @var string
     */
    public $nextPageToken;

    /**
     * @description The OSS object that is generated as the output file of the tiling job.
     *
     * @var nonExistSnapshotJobIds
     */
    public $nonExistSnapshotJobIds;

    /**
     * @description The ID of the snapshot job.
     *
     * @example 34BCAB31-2833-43A7-9FBD-B34302AB23EQ
     *
     * @var string
     */
    public $requestId;

    /**
     * @description The distance between images.
     *
     *   Default value: **0**.
     *   Unit: pixel.
     *
     * @var snapshotJobList
     */
    public $snapshotJobList;
    protected $_name = [
        'nextPageToken'          => 'NextPageToken',
        'nonExistSnapshotJobIds' => 'NonExistSnapshotJobIds',
        'requestId'              => 'RequestId',
        'snapshotJobList'        => 'SnapshotJobList',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->nextPageToken) {
            $res['NextPageToken'] = $this->nextPageToken;
        }
        if (null !== $this->nonExistSnapshotJobIds) {
            $res['NonExistSnapshotJobIds'] = null !== $this->nonExistSnapshotJobIds ? $this->nonExistSnapshotJobIds->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }
        if (null !== $this->snapshotJobList) {
            $res['SnapshotJobList'] = null !== $this->snapshotJobList ? $this->snapshotJobList->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QuerySnapshotJobListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['NextPageToken'])) {
            $model->nextPageToken = $map['NextPageToken'];
        }
        if (isset($map['NonExistSnapshotJobIds'])) {
            $model->nonExistSnapshotJobIds = nonExistSnapshotJobIds::fromMap($map['NonExistSnapshotJobIds']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['SnapshotJobList'])) {
            $model->snapshotJobList = snapshotJobList::fromMap($map['SnapshotJobList']);
        }

        return $model;
    }
}
