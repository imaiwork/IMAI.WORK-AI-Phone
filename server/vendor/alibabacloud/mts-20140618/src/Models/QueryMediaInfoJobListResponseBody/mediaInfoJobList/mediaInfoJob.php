<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList\mediaInfoJob\input;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList\mediaInfoJob\MNSMessageResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList\mediaInfoJob\properties;
use AlibabaCloud\Tea\Model;

class mediaInfoJob extends Model
{
    /**
     * @description Indicates whether the job is in asynchronous mode.
     *
     * @example true
     *
     * @var bool
     */
    public $async;

    /**
     * @description The error code returned if the job fails.
     *
     * @example InvalidParameter.JsonObjectFormatInvalid
     *
     * @var string
     */
    public $code;

    /**
     * @description The time when the job was created.
     *
     * @example 2014-01-10T12:00:00Z
     *
     * @var string
     */
    public $creationTime;

    /**
     * @description The information about the job input.
     *
     * @var input
     */
    public $input;

    /**
     * @description The ID of the job.
     *
     * @example 23ca1d184c0e4341e5b665e2a12****
     *
     * @var string
     */
    public $jobId;

    /**
     * @description The message sent by MNS to notify you of the job result.
     *
     * @var MNSMessageResult
     */
    public $MNSMessageResult;

    /**
     * @description The error message returned if the job fails.
     *
     * @example The parameter ”*” does not conform to the JSON Object specification
     *
     * @var string
     */
    public $message;

    /**
     * @description The ID of the MPS queue.
     *
     * @example 88c6ca184c0e432bbf5b665e2a15****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @description The information about the input file. For more information, see [AliyunProperties](https://help.aliyun.com/document_detail/29251.html).
     *
     * @var properties
     */
    public $properties;

    /**
     * @description The status of the job.
     *
     *   **Analyzing**: The job is being run.
     *   **Success**: The job is successful.
     *   **Fail**: The job fails.
     *
     * @example Success
     *
     * @var string
     */
    public $state;

    /**
     * @description The custom data.
     *
     * @example testid-001
     *
     * @var string
     */
    public $userData;
    protected $_name = [
        'async'            => 'Async',
        'code'             => 'Code',
        'creationTime'     => 'CreationTime',
        'input'            => 'Input',
        'jobId'            => 'JobId',
        'MNSMessageResult' => 'MNSMessageResult',
        'message'          => 'Message',
        'pipelineId'       => 'PipelineId',
        'properties'       => 'Properties',
        'state'            => 'State',
        'userData'         => 'UserData',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->async) {
            $res['Async'] = $this->async;
        }
        if (null !== $this->code) {
            $res['Code'] = $this->code;
        }
        if (null !== $this->creationTime) {
            $res['CreationTime'] = $this->creationTime;
        }
        if (null !== $this->input) {
            $res['Input'] = null !== $this->input ? $this->input->toMap() : null;
        }
        if (null !== $this->jobId) {
            $res['JobId'] = $this->jobId;
        }
        if (null !== $this->MNSMessageResult) {
            $res['MNSMessageResult'] = null !== $this->MNSMessageResult ? $this->MNSMessageResult->toMap() : null;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->pipelineId) {
            $res['PipelineId'] = $this->pipelineId;
        }
        if (null !== $this->properties) {
            $res['Properties'] = null !== $this->properties ? $this->properties->toMap() : null;
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
     * @return mediaInfoJob
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Async'])) {
            $model->async = $map['Async'];
        }
        if (isset($map['Code'])) {
            $model->code = $map['Code'];
        }
        if (isset($map['CreationTime'])) {
            $model->creationTime = $map['CreationTime'];
        }
        if (isset($map['Input'])) {
            $model->input = input::fromMap($map['Input']);
        }
        if (isset($map['JobId'])) {
            $model->jobId = $map['JobId'];
        }
        if (isset($map['MNSMessageResult'])) {
            $model->MNSMessageResult = MNSMessageResult::fromMap($map['MNSMessageResult']);
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['PipelineId'])) {
            $model->pipelineId = $map['PipelineId'];
        }
        if (isset($map['Properties'])) {
            $model->properties = properties::fromMap($map['Properties']);
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
