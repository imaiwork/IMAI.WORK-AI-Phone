<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class QueryIProductionJobResponseBody extends Model
{
    /**
     * @example ImageCartoonize
     *
     * @var string
     */
    public $functionName;

    /**
     * @example oss://example-****.oss-cn-hangzhou.aliyuncs.com/example.mp4
     *
     * @var string
     */
    public $input;

    /**
     * @example 88c6ca184c0e432bbf5b665e2a15****
     *
     * @var string
     */
    public $jobId;

    /**
     * @example {mode:"gif"}
     *
     * @var string
     */
    public $jobParams;

    /**
     * @example oss://example-****.oss-cn-hangzhou.aliyuncs.com/iproduction/{source}-{timestamp}-{sequenceId}.srt
     *
     * @var string
     */
    public $output;

    /**
     * @example 39f8e0bc005e4f309379701645f4****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @example D127C68E-F1A1-4CE5-A874-8FF724881A12
     *
     * @var string
     */
    public $requestId;

    /**
     * @example {"Code":"Success","Data":"{\\"result\\":[{\\"file\\":\\"iproduction/test-result.jpg\\"},{\\"file\\":\\"iproduction/test-origin.jpg\\"}]}","Message":"Successful."}
     *
     * @var string
     */
    public $result;

    /**
     * @example Success
     *
     * @var string
     */
    public $state;

    /**
     * @example null
     *
     * @var string
     */
    public $userData;
    protected $_name = [
        'functionName' => 'FunctionName',
        'input'        => 'Input',
        'jobId'        => 'JobId',
        'jobParams'    => 'JobParams',
        'output'       => 'Output',
        'pipelineId'   => 'PipelineId',
        'requestId'    => 'RequestId',
        'result'       => 'Result',
        'state'        => 'State',
        'userData'     => 'UserData',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->functionName) {
            $res['FunctionName'] = $this->functionName;
        }
        if (null !== $this->input) {
            $res['Input'] = $this->input;
        }
        if (null !== $this->jobId) {
            $res['JobId'] = $this->jobId;
        }
        if (null !== $this->jobParams) {
            $res['JobParams'] = $this->jobParams;
        }
        if (null !== $this->output) {
            $res['Output'] = $this->output;
        }
        if (null !== $this->pipelineId) {
            $res['PipelineId'] = $this->pipelineId;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }
        if (null !== $this->result) {
            $res['Result'] = $this->result;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }
        if (null !== $this->userData) {
            $res['UserData'] = $this->userData;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryIProductionJobResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FunctionName'])) {
            $model->functionName = $map['FunctionName'];
        }
        if (isset($map['Input'])) {
            $model->input = $map['Input'];
        }
        if (isset($map['JobId'])) {
            $model->jobId = $map['JobId'];
        }
        if (isset($map['JobParams'])) {
            $model->jobParams = $map['JobParams'];
        }
        if (isset($map['Output'])) {
            $model->output = $map['Output'];
        }
        if (isset($map['PipelineId'])) {
            $model->pipelineId = $map['PipelineId'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['Result'])) {
            $model->result = $map['Result'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }
        if (isset($map['UserData'])) {
            $model->userData = $map['UserData'];
        }

        return $model;
    }
}
