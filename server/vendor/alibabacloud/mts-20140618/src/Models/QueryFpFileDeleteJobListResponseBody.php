<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpFileDeleteJobListResponseBody\fpFileDeleteJobList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpFileDeleteJobListResponseBody\nonExistIds;
use AlibabaCloud\Tea\Model;

class QueryFpFileDeleteJobListResponseBody extends Model
{
    /**
     * @description The jobs of deleting media files from a media fingerprint library. For more information, see the "FpFileDeleteJob" section of the [Data types](https://help.aliyun.com/document_detail/93555.html) topic.
     *
     * @var fpFileDeleteJobList
     */
    public $fpFileDeleteJobList;

    /**
     * @description The response parameters.
     *
     * @var nonExistIds
     */
    public $nonExistIds;

    /**
     * @description The ID of the request.
     *
     * @example D127C68E-F1A1-4CE5-A874-8FF724881A12
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'fpFileDeleteJobList' => 'FpFileDeleteJobList',
        'nonExistIds'         => 'NonExistIds',
        'requestId'           => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpFileDeleteJobList) {
            $res['FpFileDeleteJobList'] = null !== $this->fpFileDeleteJobList ? $this->fpFileDeleteJobList->toMap() : null;
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
     * @return QueryFpFileDeleteJobListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpFileDeleteJobList'])) {
            $model->fpFileDeleteJobList = fpFileDeleteJobList::fromMap($map['FpFileDeleteJobList']);
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
