<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\audioCensorResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\barrageCensorResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\coverImageCensorResults;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\descCensorResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\input;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\titleCensorResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\vensorCensorResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\videoCensorConfig;
use AlibabaCloud\Tea\Model;

class mediaCensorJobDetail extends Model
{
    /**
     * @var audioCensorResult
     */
    public $audioCensorResult;

    /**
     * @description The moderation result of live comments.
     *
     * @var barrageCensorResult
     */
    public $barrageCensorResult;

    /**
     * @description The error code returned if the job fails. This parameter is not returned if the job is successful.
     *
     * @example InvalidParameter.ResourceNotFound
     *
     * @var string
     */
    public $code;

    /**
     * @description The moderation results of thumbnails.
     *
     * @var coverImageCensorResults
     */
    public $coverImageCensorResults;

    /**
     * @description The time when the job was created.
     *
     * @example 2018-09-13T16:32:24Z
     *
     * @var string
     */
    public $creationTime;

    /**
     * @description The moderation result of the description.
     *
     * @var descCensorResult
     */
    public $descCensorResult;

    /**
     * @description The time when the job was complete.
     *
     * @example 2018-09-21
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
     * @description The ID of the content moderation job.
     *
     * @example f8f166eea7a44e9bb0a4aecf9543****
     *
     * @var string
     */
    public $jobId;

    /**
     * @description The error message returned if the job fails. This parameter is not returned if the job is successful.
     *
     * @example The resource operated cannot be found
     *
     * @var string
     */
    public $message;

    /**
     * @description The ID of the ApsaraVideo Media Processing (MPS) queue to which the content moderation job is submitted.
     *
     * @example c5b30b7c0d0e4a0abde1d5f9e751****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @description The status of the job. Valid values:
     *
     * @example Success
     *
     * @var string
     */
    public $state;

    /**
     * @description The overall result of the job. Valid values:
     *
     *   **pass**: The content passes the moderation.
     *   **review**: The content needs to be manually reviewed again.
     *   **block**: The content needs to be blocked.
     *
     * If the moderation result of one type of the moderated content is review, the overall result is review. If the moderation result of one type of the moderated content is block, the overall result is block.
     * @example block
     *
     * @var string
     */
    public $suggestion;

    /**
     * @description The moderation result of the title.
     *
     * @var titleCensorResult
     */
    public $titleCensorResult;

    /**
     * @description The custom data.
     *
     * @example example userdata ****
     *
     * @var string
     */
    public $userData;

    /**
     * @description The moderation results of the video.
     *
     * @var vensorCensorResult
     */
    public $vensorCensorResult;

    /**
     * @description The video moderation configurations.
     *
     * @var videoCensorConfig
     */
    public $videoCensorConfig;
    protected $_name = [
        'audioCensorResult'       => 'AudioCensorResult',
        'barrageCensorResult'     => 'BarrageCensorResult',
        'code'                    => 'Code',
        'coverImageCensorResults' => 'CoverImageCensorResults',
        'creationTime'            => 'CreationTime',
        'descCensorResult'        => 'DescCensorResult',
        'finishTime'              => 'FinishTime',
        'input'                   => 'Input',
        'jobId'                   => 'JobId',
        'message'                 => 'Message',
        'pipelineId'              => 'PipelineId',
        'state'                   => 'State',
        'suggestion'              => 'Suggestion',
        'titleCensorResult'       => 'TitleCensorResult',
        'userData'                => 'UserData',
        'vensorCensorResult'      => 'VensorCensorResult',
        'videoCensorConfig'       => 'VideoCensorConfig',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->audioCensorResult) {
            $res['AudioCensorResult'] = null !== $this->audioCensorResult ? $this->audioCensorResult->toMap() : null;
        }
        if (null !== $this->barrageCensorResult) {
            $res['BarrageCensorResult'] = null !== $this->barrageCensorResult ? $this->barrageCensorResult->toMap() : null;
        }
        if (null !== $this->code) {
            $res['Code'] = $this->code;
        }
        if (null !== $this->coverImageCensorResults) {
            $res['CoverImageCensorResults'] = null !== $this->coverImageCensorResults ? $this->coverImageCensorResults->toMap() : null;
        }
        if (null !== $this->creationTime) {
            $res['CreationTime'] = $this->creationTime;
        }
        if (null !== $this->descCensorResult) {
            $res['DescCensorResult'] = null !== $this->descCensorResult ? $this->descCensorResult->toMap() : null;
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
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->pipelineId) {
            $res['PipelineId'] = $this->pipelineId;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }
        if (null !== $this->suggestion) {
            $res['Suggestion'] = $this->suggestion;
        }
        if (null !== $this->titleCensorResult) {
            $res['TitleCensorResult'] = null !== $this->titleCensorResult ? $this->titleCensorResult->toMap() : null;
        }
        if (null !== $this->userData) {
            $res['UserData'] = $this->userData;
        }
        if (null !== $this->vensorCensorResult) {
            $res['VensorCensorResult'] = null !== $this->vensorCensorResult ? $this->vensorCensorResult->toMap() : null;
        }
        if (null !== $this->videoCensorConfig) {
            $res['VideoCensorConfig'] = null !== $this->videoCensorConfig ? $this->videoCensorConfig->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return mediaCensorJobDetail
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AudioCensorResult'])) {
            $model->audioCensorResult = audioCensorResult::fromMap($map['AudioCensorResult']);
        }
        if (isset($map['BarrageCensorResult'])) {
            $model->barrageCensorResult = barrageCensorResult::fromMap($map['BarrageCensorResult']);
        }
        if (isset($map['Code'])) {
            $model->code = $map['Code'];
        }
        if (isset($map['CoverImageCensorResults'])) {
            $model->coverImageCensorResults = coverImageCensorResults::fromMap($map['CoverImageCensorResults']);
        }
        if (isset($map['CreationTime'])) {
            $model->creationTime = $map['CreationTime'];
        }
        if (isset($map['DescCensorResult'])) {
            $model->descCensorResult = descCensorResult::fromMap($map['DescCensorResult']);
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
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['PipelineId'])) {
            $model->pipelineId = $map['PipelineId'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }
        if (isset($map['Suggestion'])) {
            $model->suggestion = $map['Suggestion'];
        }
        if (isset($map['TitleCensorResult'])) {
            $model->titleCensorResult = titleCensorResult::fromMap($map['TitleCensorResult']);
        }
        if (isset($map['UserData'])) {
            $model->userData = $map['UserData'];
        }
        if (isset($map['VensorCensorResult'])) {
            $model->vensorCensorResult = vensorCensorResult::fromMap($map['VensorCensorResult']);
        }
        if (isset($map['VideoCensorConfig'])) {
            $model->videoCensorConfig = videoCensorConfig::fromMap($map['VideoCensorConfig']);
        }

        return $model;
    }
}
