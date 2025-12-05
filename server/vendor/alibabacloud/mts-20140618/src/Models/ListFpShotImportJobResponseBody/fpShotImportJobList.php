<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotImportJobResponseBody;

use AlibabaCloud\Tea\Model;

class fpShotImportJobList extends Model
{
    /**
     * @description The error code returned when the job fails.
     *
     * @example 0
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
    public $createTime;

    /**
     * @description The time when the job was completed.
     *
     * @example 2020-06-30T00:34:02Z
     *
     * @var string
     */
    public $finishTime;

    /**
     * @description The ID of the text fingerprint library.
     *
     * @example 2288c6ca184c0e47098a5b665e2a12****
     *
     * @var string
     */
    public $fpDBId;

    /**
     * @description The import configuration.
     *
     * @example ""
     *
     * @var string
     */
    public $fpImportConfig;

    /**
     * @description The job ID.
     *
     * @example 25bacf2824614bcf9273dc0744db****
     *
     * @var string
     */
    public $id;

    /**
     * @description The input file.
     *
     * @example {\\"Bucket\\":\\"mts-example****\\",\\"Location\\":\\"oss-cn-shanghai\\",\\"Object\\":\\"test-0828/video/test.mp4\\"}
     *
     * @var string
     */
    public $input;

    /**
     * @description The error message returned when the job fails.
     *
     * @example Success
     *
     * @var string
     */
    public $message;

    /**
     * @description The ID of the ApsaraVideo Media Processing (MPS) queue to which the job is submitted.
     *
     * @example ebb51ee30f0b49aba959823fa991****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @description The processing information of the job.
     *
     * @example http://testbucket.oss-cn-shanghai.aliyuncs.com/932ajjw***32ssoj_importResult.txt
     *
     * @var string
     */
    public $processMessage;

    /**
     * @description The status of the job. Valid values:
     *
     *   Processing: The job is in progress.
     *   Fail: The job fails.
     *   Success: The job is successful.
     *
     * @example Success
     *
     * @var string
     */
    public $status;

    /**
     * @description The user-defined data.
     *
     * @example 001
     *
     * @var string
     */
    public $userData;
    protected $_name = [
        'code'           => 'Code',
        'createTime'     => 'CreateTime',
        'finishTime'     => 'FinishTime',
        'fpDBId'         => 'FpDBId',
        'fpImportConfig' => 'FpImportConfig',
        'id'             => 'Id',
        'input'          => 'Input',
        'message'        => 'Message',
        'pipelineId'     => 'PipelineId',
        'processMessage' => 'ProcessMessage',
        'status'         => 'Status',
        'userData'       => 'UserData',
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
        if (null !== $this->createTime) {
            $res['CreateTime'] = $this->createTime;
        }
        if (null !== $this->finishTime) {
            $res['FinishTime'] = $this->finishTime;
        }
        if (null !== $this->fpDBId) {
            $res['FpDBId'] = $this->fpDBId;
        }
        if (null !== $this->fpImportConfig) {
            $res['FpImportConfig'] = $this->fpImportConfig;
        }
        if (null !== $this->id) {
            $res['Id'] = $this->id;
        }
        if (null !== $this->input) {
            $res['Input'] = $this->input;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->pipelineId) {
            $res['PipelineId'] = $this->pipelineId;
        }
        if (null !== $this->processMessage) {
            $res['ProcessMessage'] = $this->processMessage;
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
     * @return fpShotImportJobList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Code'])) {
            $model->code = $map['Code'];
        }
        if (isset($map['CreateTime'])) {
            $model->createTime = $map['CreateTime'];
        }
        if (isset($map['FinishTime'])) {
            $model->finishTime = $map['FinishTime'];
        }
        if (isset($map['FpDBId'])) {
            $model->fpDBId = $map['FpDBId'];
        }
        if (isset($map['FpImportConfig'])) {
            $model->fpImportConfig = $map['FpImportConfig'];
        }
        if (isset($map['Id'])) {
            $model->id = $map['Id'];
        }
        if (isset($map['Input'])) {
            $model->input = $map['Input'];
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['PipelineId'])) {
            $model->pipelineId = $map['PipelineId'];
        }
        if (isset($map['ProcessMessage'])) {
            $model->processMessage = $map['ProcessMessage'];
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
