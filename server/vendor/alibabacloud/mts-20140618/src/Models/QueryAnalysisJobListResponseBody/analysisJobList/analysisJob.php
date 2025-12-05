<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\analysisConfig;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\inputFile;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\MNSMessageResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\templateList;
use AlibabaCloud\Tea\Model;

class analysisJob extends Model
{
    /**
     * @description The job configurations.
     *
     * @var analysisConfig
     */
    public $analysisConfig;

    /**
     * @description The error code returned if the job fails.
     *
     * @example InvalidParameter.ResourceNotFound
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
     * @description The ID of the template analysis job.
     *
     * @example 57f6aa3f84824309bcba67231b406****
     *
     * @var string
     */
    public $id;

    /**
     * @description The information about the job input.
     *
     * @var inputFile
     */
    public $inputFile;

    /**
     * @description The message sent by Message Service (MNS) to notify users of the job result.
     *
     * @var MNSMessageResult
     */
    public $MNSMessageResult;

    /**
     * @description The error message returned if the job failed.
     *
     * @example The resource operated \\"PipelineId\\" cannot be found
     *
     * @var string
     */
    public $message;

    /**
     * @description The transcoding progress.
     *
     * @example 86
     *
     * @var int
     */
    public $percent;

    /**
     * @description The ID of the MPS queue to which the job was submitted.
     *
     * @example bb558c1cc25b45309aab5be44d19****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @description The priority of the job in the ApsaraVideo Media Processing (MPS) queue to which the job was submitted.
     *
     *   Valid values: **1 to 10**. The value 10 indicates the highest priority.
     *   Default value: **10**.
     *
     * @example 8
     *
     * @var string
     */
    public $priority;

    /**
     * @description The job status.
     *
     *   **Submitted**: The job was submitted.
     *   **Analyzing**: The job is in progress.
     *   **Success**: The job was successful.
     *   **Fail**: The job failed.
     *
     * @example Success
     *
     * @var string
     */
    public $state;

    /**
     * @description The matched preset templates.
     *
     * @var templateList
     */
    public $templateList;

    /**
     * @description The custom data.
     *
     * @example testid-001
     *
     * @var string
     */
    public $userData;
    protected $_name = [
        'analysisConfig'   => 'AnalysisConfig',
        'code'             => 'Code',
        'creationTime'     => 'CreationTime',
        'id'               => 'Id',
        'inputFile'        => 'InputFile',
        'MNSMessageResult' => 'MNSMessageResult',
        'message'          => 'Message',
        'percent'          => 'Percent',
        'pipelineId'       => 'PipelineId',
        'priority'         => 'Priority',
        'state'            => 'State',
        'templateList'     => 'TemplateList',
        'userData'         => 'UserData',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->analysisConfig) {
            $res['AnalysisConfig'] = null !== $this->analysisConfig ? $this->analysisConfig->toMap() : null;
        }
        if (null !== $this->code) {
            $res['Code'] = $this->code;
        }
        if (null !== $this->creationTime) {
            $res['CreationTime'] = $this->creationTime;
        }
        if (null !== $this->id) {
            $res['Id'] = $this->id;
        }
        if (null !== $this->inputFile) {
            $res['InputFile'] = null !== $this->inputFile ? $this->inputFile->toMap() : null;
        }
        if (null !== $this->MNSMessageResult) {
            $res['MNSMessageResult'] = null !== $this->MNSMessageResult ? $this->MNSMessageResult->toMap() : null;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->percent) {
            $res['Percent'] = $this->percent;
        }
        if (null !== $this->pipelineId) {
            $res['PipelineId'] = $this->pipelineId;
        }
        if (null !== $this->priority) {
            $res['Priority'] = $this->priority;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }
        if (null !== $this->templateList) {
            $res['TemplateList'] = null !== $this->templateList ? $this->templateList->toMap() : null;
        }
        if (null !== $this->userData) {
            $res['UserData'] = $this->userData;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return analysisJob
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AnalysisConfig'])) {
            $model->analysisConfig = analysisConfig::fromMap($map['AnalysisConfig']);
        }
        if (isset($map['Code'])) {
            $model->code = $map['Code'];
        }
        if (isset($map['CreationTime'])) {
            $model->creationTime = $map['CreationTime'];
        }
        if (isset($map['Id'])) {
            $model->id = $map['Id'];
        }
        if (isset($map['InputFile'])) {
            $model->inputFile = inputFile::fromMap($map['InputFile']);
        }
        if (isset($map['MNSMessageResult'])) {
            $model->MNSMessageResult = MNSMessageResult::fromMap($map['MNSMessageResult']);
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['Percent'])) {
            $model->percent = $map['Percent'];
        }
        if (isset($map['PipelineId'])) {
            $model->pipelineId = $map['PipelineId'];
        }
        if (isset($map['Priority'])) {
            $model->priority = $map['Priority'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }
        if (isset($map['TemplateList'])) {
            $model->templateList = templateList::fromMap($map['TemplateList']);
        }
        if (isset($map['UserData'])) {
            $model->userData = $map['UserData'];
        }

        return $model;
    }
}
