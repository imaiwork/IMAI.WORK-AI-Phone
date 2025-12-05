<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList;
use AlibabaCloud\Tea\Model;

class SubmitJobsResponseBody extends Model
{
    /**
     * @description The transcoding jobs that are generated.
     *
     * @var jobResultList
     */
    public $jobResultList;

    /**
     * @description The request ID.
     *
     * @example 25818875-5F78-4A45S71F6-D73936451234
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'jobResultList' => 'JobResultList',
        'requestId'     => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->jobResultList) {
            $res['JobResultList'] = null !== $this->jobResultList ? $this->jobResultList->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SubmitJobsResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['JobResultList'])) {
            $model->jobResultList = jobResultList::fromMap($map['JobResultList']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
