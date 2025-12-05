<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList;
use AlibabaCloud\Tea\Model;

class ListJobResponseBody extends Model
{
    /**
     * @description The transcoding jobs.
     *
     * @var jobList
     */
    public $jobList;

    /**
     * @description The pagination token. It can be used in the next request to retrieve a new page of results.
     *
     * @example 16f01ad6175e4230ac42bb5182cd****
     *
     * @var string
     */
    public $nextPageToken;

    /**
     * @description The request ID.
     *
     * @example BC860F04-778A-472F-AB39-E1BF329C1EA8
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'jobList'       => 'JobList',
        'nextPageToken' => 'NextPageToken',
        'requestId'     => 'RequestId',
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
        if (null !== $this->nextPageToken) {
            $res['NextPageToken'] = $this->nextPageToken;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return ListJobResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['JobList'])) {
            $model->jobList = jobList::fromMap($map['JobList']);
        }
        if (isset($map['NextPageToken'])) {
            $model->nextPageToken = $map['NextPageToken'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
