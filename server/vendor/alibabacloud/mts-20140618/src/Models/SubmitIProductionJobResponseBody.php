<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class SubmitIProductionJobResponseBody extends Model
{
    /**
     * @example 39f8e0bc005e4f309379701645f4****
     *
     * @var string
     */
    public $jobId;

    /**
     * @example 5210DBB0-E327-4D45-ADBC-0B83C8796E26
     *
     * @var string
     */
    public $requestId;

    /**
     * @example { "Code":"Success", "Details":[], "FunctionName":"ImageCartoonize", "JobId":"39f8e0bc005e4f309379701645f4****", "Message":"success", "State":"Success", "Type":"IProduction" }
     *
     * @var string
     */
    public $result;
    protected $_name = [
        'jobId'     => 'JobId',
        'requestId' => 'RequestId',
        'result'    => 'Result',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->jobId) {
            $res['JobId'] = $this->jobId;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }
        if (null !== $this->result) {
            $res['Result'] = $this->result;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SubmitIProductionJobResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['JobId'])) {
            $model->jobId = $map['JobId'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['Result'])) {
            $model->result = $map['Result'];
        }

        return $model;
    }
}
