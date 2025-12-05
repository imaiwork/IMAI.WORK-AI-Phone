<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpDBDeleteJobListResponseBody\fpDBDeleteJobList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpDBDeleteJobListResponseBody\nonExistIds;
use AlibabaCloud\Tea\Model;

class QueryFpDBDeleteJobListResponseBody extends Model
{
    /**
     * @description The jobs of deleting a media fingerprint library. For more information, see the "FpDBDeleteJob" section of the [Data types](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/datatypes) topic.
     *
     * @var fpDBDeleteJobList
     */
    public $fpDBDeleteJobList;

    /**
     * @description The IDs of the jobs that do not exist.
     *
     * @var nonExistIds
     */
    public $nonExistIds;

    /**
     * @description The ID of the request.
     *
     * @example 4247B23C-26DE-529F-8D9F-FD6811AE979B
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'fpDBDeleteJobList' => 'FpDBDeleteJobList',
        'nonExistIds'       => 'NonExistIds',
        'requestId'         => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpDBDeleteJobList) {
            $res['FpDBDeleteJobList'] = null !== $this->fpDBDeleteJobList ? $this->fpDBDeleteJobList->toMap() : null;
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
     * @return QueryFpDBDeleteJobListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpDBDeleteJobList'])) {
            $model->fpDBDeleteJobList = fpDBDeleteJobList::fromMap($map['FpDBDeleteJobList']);
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
