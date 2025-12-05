<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpFileDeleteJobListResponseBody\fpFileDeleteJobList;

use AlibabaCloud\Tea\Model;

class fpFileDeleteJob extends Model
{
    /**
     * @description The error code returned if the job fails. This parameter is not returned if the job is successful.
     *
     * @example ServiceUnavailable
     *
     * @var string
     */
    public $code;

    /**
     * @description The time when the job was created.
     *
     * @example 2020-06-30T00:33:18Z
     *
     * @var string
     */
    public $creationTime;

    /**
     * @description The ID of the file.
     *
     * @example 41e6536e4f2250e2e9bf26cdea19****
     *
     * @var string
     */
    public $fileIds;

    /**
     * @description The time when the job was complete.
     *
     * @example 2020-06-30T00:34:02Z
     *
     * @var string
     */
    public $finishTime;

    /**
     * @description The ID of the media fingerprint library.
     *
     * @example 88c6ca184c0e47098a5b665e2a12****
     *
     * @var string
     */
    public $fpDBId;

    /**
     * @description The ID of the job.
     *
     * @example 25bacf2824614bcf9273dc0744db****
     *
     * @var string
     */
    public $id;

    /**
     * @description The error message returned if the job fails. This parameter is not returned if the job is successful.
     *
     * @example The request has failed due to a temporary failure of the server.
     *
     * @var string
     */
    public $message;

    /**
     * @description The ID of the ApsaraVideo Media Processing (MPS) queue to which the job was submitted.
     *
     * @example fb712a6890464059b1b2ea7c8647****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @description The status of the job. Valid values: Valid values:
     *
     *   **Queuing**: The job is waiting in the queue.
     *   **Analysing**: The job is in progress.
     *   **Success**: The job is successful.
     *   **Fail**: The job fails.
     *
     * @example Success
     *
     * @var string
     */
    public $status;

    /**
     * @description The user-defined data.
     *
     * @example example data
     *
     * @var string
     */
    public $userData;
    protected $_name = [
        'code'         => 'Code',
        'creationTime' => 'CreationTime',
        'fileIds'      => 'FileIds',
        'finishTime'   => 'FinishTime',
        'fpDBId'       => 'FpDBId',
        'id'           => 'Id',
        'message'      => 'Message',
        'pipelineId'   => 'PipelineId',
        'status'       => 'Status',
        'userData'     => 'UserData',
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
        if (null !== $this->fileIds) {
            $res['FileIds'] = $this->fileIds;
        }
        if (null !== $this->finishTime) {
            $res['FinishTime'] = $this->finishTime;
        }
        if (null !== $this->fpDBId) {
            $res['FpDBId'] = $this->fpDBId;
        }
        if (null !== $this->id) {
            $res['Id'] = $this->id;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->pipelineId) {
            $res['PipelineId'] = $this->pipelineId;
        }
        if (null !== $this->status) {
            $res['Status'] = $this->status;
        }
        if (null !== $this->userData) {
            $res['UserData'] = $this->userData;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpFileDeleteJob
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
        if (isset($map['FileIds'])) {
            $model->fileIds = $map['FileIds'];
        }
        if (isset($map['FinishTime'])) {
            $model->finishTime = $map['FinishTime'];
        }
        if (isset($map['FpDBId'])) {
            $model->fpDBId = $map['FpDBId'];
        }
        if (isset($map['Id'])) {
            $model->id = $map['Id'];
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['PipelineId'])) {
            $model->pipelineId = $map['PipelineId'];
        }
        if (isset($map['Status'])) {
            $model->status = $map['Status'];
        }
        if (isset($map['UserData'])) {
            $model->userData = $map['UserData'];
        }

        return $model;
    }
}
