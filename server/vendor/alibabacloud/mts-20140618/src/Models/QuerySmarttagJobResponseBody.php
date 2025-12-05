<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySmarttagJobResponseBody\results;
use AlibabaCloud\Tea\Model;

class QuerySmarttagJobResponseBody extends Model
{
    /**
     * @example Success
     *
     * @var string
     */
    public $jobStatus;

    /**
     * @example 7B117AF5-2A16-412C-B127-FA6175ED1AD0
     *
     * @var string
     */
    public $requestId;

    /**
     * @var results
     */
    public $results;

    /**
     * @example example UserData ****
     *
     * @var string
     */
    public $userData;
    protected $_name = [
        'jobStatus' => 'JobStatus',
        'requestId' => 'RequestId',
        'results'   => 'Results',
        'userData'  => 'UserData',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->jobStatus) {
            $res['JobStatus'] = $this->jobStatus;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }
        if (null !== $this->results) {
            $res['Results'] = null !== $this->results ? $this->results->toMap() : null;
        }
        if (null !== $this->userData) {
            $res['UserData'] = $this->userData;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QuerySmarttagJobResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['JobStatus'])) {
            $model->jobStatus = $map['JobStatus'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['Results'])) {
            $model->results = results::fromMap($map['Results']);
        }
        if (isset($map['UserData'])) {
            $model->userData = $map['UserData'];
        }

        return $model;
    }
}
