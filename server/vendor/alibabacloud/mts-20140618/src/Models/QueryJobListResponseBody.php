<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\nonExistJobIds;
use AlibabaCloud\Tea\Model;

class QueryJobListResponseBody extends Model
{
    /**
     * @description The transcoding jobs.
     *
     * @var jobList
     */
    public $jobList;

    /**
     * @description The list of nonexistent job IDs. If all queried job IDs exist, the response does not contain this parameter.
     *
     * @var nonExistJobIds
     */
    public $nonExistJobIds;

    /**
     * @description The ID of the request.
     *
     * @example 197ADF44-104C-514C-9F92-D8924CB34E2A
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'jobList'        => 'JobList',
        'nonExistJobIds' => 'NonExistJobIds',
        'requestId'      => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->jobList) {
            $res['JobList'] = null !== $this->jobList ? $this->jobList->toMap() : null;
        }
        if (null !== $this->nonExistJobIds) {
            $res['NonExistJobIds'] = null !== $this->nonExistJobIds ? $this->nonExistJobIds->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryJobListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['JobList'])) {
            $model->jobList = jobList::fromMap($map['JobList']);
        }
        if (isset($map['NonExistJobIds'])) {
            $model->nonExistJobIds = nonExistJobIds::fromMap($map['NonExistJobIds']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
