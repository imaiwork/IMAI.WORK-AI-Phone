<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotConfig;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\inputFile;
use AlibabaCloud\Tea\Model;

class fpShotJob extends Model
{
    /**
     * @description The error code returned if the job fails.
     *
     * @example InvalidParameter.UUIDFormatInvalid
     *
     * @var string
     */
    public $code;

    /**
     * @description The time when the job was created.
     *
     * @example 2017-01-10T12:00:00Z
     *
     * @var string
     */
    public $creationTime;

    /**
     * @description The length of the input file.
     * Unit: seconds.
     * @example 5
     *
     * @var int
     */
    public $duration;

    /**
     * @description The ID of the uploaded file.
     *
     * @example ebb51ee30f0b49aba959823fa991****
     *
     * @var string
     */
    public $fileId;

    /**
     * @description The time when the job was complete.
     *
     * @example 0
     *
     * @var string
     */
    public $finishTime;

    /**
     * @description The configurations of the job.
     *
     * @var fpShotConfig
     */
    public $fpShotConfig;

    /**
     * @description The results of the media fingerprint analysis job.
     *
     * @var fpShotResult
     */
    public $fpShotResult;

    /**
     * @description The ID of the job.
     *
     * @example 88c6ca184c0e47098a5b665e2a12****
     *
     * @var string
     */
    public $id;

    /**
     * @description The information about the job input.
     *
     * @example {"Bucket":"oss-test","Location":"oss-cn-beijing","Object":"test.mp4"}
     *
     * @var string
     */
    public $input;

    /**
     * @description The information about the job input.
     *
     * @var inputFile
     */
    public $inputFile;

    /**
     * @description The error message returned if the job fails. This parameter is not returned if the job is successful.
     *
     * @example The parameter \\"Id\\" is invalid.A uuid must:1)be comprised of chracters[a-f],numbers[0-9];2)be 32 characters long
     *
     * @var string
     */
    public $message;

    /**
     * @description The ID of the MPS queue to which the analysis job is submitted.
     *
     * @example 88c6ca184c0e47098a5b665e2a12****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @description The status of the job. Valid values:
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
        'code'         => 'Code',
        'creationTime' => 'CreationTime',
        'duration'     => 'Duration',
        'fileId'       => 'FileId',
        'finishTime'   => 'FinishTime',
        'fpShotConfig' => 'FpShotConfig',
        'fpShotResult' => 'FpShotResult',
        'id'           => 'Id',
        'input'        => 'Input',
        'inputFile'    => 'InputFile',
        'message'      => 'Message',
        'pipelineId'   => 'PipelineId',
        'state'        => 'State',
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
        if (null !== $this->duration) {
            $res['Duration'] = $this->duration;
        }
        if (null !== $this->fileId) {
            $res['FileId'] = $this->fileId;
        }
        if (null !== $this->finishTime) {
            $res['FinishTime'] = $this->finishTime;
        }
        if (null !== $this->fpShotConfig) {
            $res['FpShotConfig'] = null !== $this->fpShotConfig ? $this->fpShotConfig->toMap() : null;
        }
        if (null !== $this->fpShotResult) {
            $res['FpShotResult'] = null !== $this->fpShotResult ? $this->fpShotResult->toMap() : null;
        }
        if (null !== $this->id) {
            $res['Id'] = $this->id;
        }
        if (null !== $this->input) {
            $res['Input'] = $this->input;
        }
        if (null !== $this->inputFile) {
            $res['InputFile'] = null !== $this->inputFile ? $this->inputFile->toMap() : null;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->pipelineId) {
            $res['PipelineId'] = $this->pipelineId;
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
     * @return fpShotJob
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
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['FileId'])) {
            $model->fileId = $map['FileId'];
        }
        if (isset($map['FinishTime'])) {
            $model->finishTime = $map['FinishTime'];
        }
        if (isset($map['FpShotConfig'])) {
            $model->fpShotConfig = fpShotConfig::fromMap($map['FpShotConfig']);
        }
        if (isset($map['FpShotResult'])) {
            $model->fpShotResult = fpShotResult::fromMap($map['FpShotResult']);
        }
        if (isset($map['Id'])) {
            $model->id = $map['Id'];
        }
        if (isset($map['Input'])) {
            $model->input = $map['Input'];
        }
        if (isset($map['InputFile'])) {
            $model->inputFile = inputFile::fromMap($map['InputFile']);
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['PipelineId'])) {
            $model->pipelineId = $map['PipelineId'];
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
