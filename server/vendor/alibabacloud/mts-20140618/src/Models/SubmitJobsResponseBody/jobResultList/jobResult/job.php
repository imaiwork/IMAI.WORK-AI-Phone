<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\input;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\MNSMessageResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output;
use AlibabaCloud\Tea\Model;

class job extends Model
{
    /**
     * @description The error code returned if the job failed. This parameter is not returned if the job was successful.
     *
     * @example InternalError
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
     * @description The time when the job was complete.
     *
     * @example 2014-01-10T12:20:00Z
     *
     * @var string
     */
    public $finishTime;

    /**
     * @description The information about the job input.
     *
     * @var input
     */
    public $input;

    /**
     * @description The job ID.
     *
     * @example 31fa3c9ca8134f9cec2b4b0b0f78****
     *
     * @var string
     */
    public $jobId;

    /**
     * @description The message sent by MNS to notify users of the job result.
     *
     * @var MNSMessageResult
     */
    public $MNSMessageResult;

    /**
     * @description The error message returned if the job failed. This parameter is not returned if the job was successful.
     *
     * @example The operation has failed due to some unknown error, exception or failure.
     *
     * @var string
     */
    public $message;

    /**
     * @description The output of the job.
     *
     * @var output
     */
    public $output;

    /**
     * @description The transcoding progress.
     *
     * @example 100
     *
     * @var int
     */
    public $percent;

    /**
     * @description The ID of the MPS queue.
     *
     * @example 88c6ca184c0e47098a5b665e2a126797
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @description The state of the job. Valid values:
     *
     *   **Submitted**
     *   **TranscodeFail**
     *
     * @example Submitted
     *
     * @var string
     */
    public $state;
    protected $_name = [
        'code'             => 'Code',
        'creationTime'     => 'CreationTime',
        'finishTime'       => 'FinishTime',
        'input'            => 'Input',
        'jobId'            => 'JobId',
        'MNSMessageResult' => 'MNSMessageResult',
        'message'          => 'Message',
        'output'           => 'Output',
        'percent'          => 'Percent',
        'pipelineId'       => 'PipelineId',
        'state'            => 'State',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->code) {
            $res['Code'] = $this->code;
        }
        if (null !== $this->creationTime) {
            $res['CreationTime'] = $this->creationTime;
        }
        if (null !== $this->finishTime) {
            $res['FinishTime'] = $this->finishTime;
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
        if (null !== $this->output) {
            $res['Output'] = null !== $this->output ? $this->output->toMap() : null;
        }
        if (null !== $this->percent) {
            $res['Percent'] = $this->percent;
        }
        if (null !== $this->pipelineId) {
            $res['PipelineId'] = $this->pipelineId;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return job
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Code'])) {
            $model->code = $map['Code'];
        }
        if (isset($map['CreationTime'])) {
            $model->creationTime = $map['CreationTime'];
        }
        if (isset($map['FinishTime'])) {
            $model->finishTime = $map['FinishTime'];
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
        if (isset($map['Output'])) {
            $model->output = output::fromMap($map['Output']);
        }
        if (isset($map['Percent'])) {
            $model->percent = $map['Percent'];
        }
        if (isset($map['PipelineId'])) {
            $model->pipelineId = $map['PipelineId'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }

        return $model;
    }
}
