<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody\snapshotJob\input;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody\snapshotJob\MNSMessageResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody\snapshotJob\snapshotConfig;
use AlibabaCloud\Tea\Model;

class snapshotJob extends Model
{
    /**
     * @description The error code returned if the job fails. This parameter is not returned if the job is successful.
     *
     * @example ResourceContentBad
     *
     * @var string
     */
    public $code;

    /**
     * @description The number of snapshots that are captured.
     *
     * @example 1
     *
     * @var string
     */
    public $count;

    /**
     * @description The time when the job was created.
     *
     * @example 2021-05-19T03:11:48Z
     *
     * @var string
     */
    public $creationTime;

    /**
     * @description The ID of the snapshot job.
     *
     * @example f4e3b9ba9f3840c39d6e288056f0****
     *
     * @var string
     */
    public $id;

    /**
     * @description The information about the job input.
     *
     * @var input
     */
    public $input;

    /**
     * @description The message sent by MNS to notify the user of the job result.
     *
     * @var MNSMessageResult
     */
    public $MNSMessageResult;

    /**
     * @description The error message returned if the job fails. This parameter is not returned if the job is successful.
     *
     * @example The resource operated InputFile is bad
     *
     * @var string
     */
    public $message;

    /**
     * @description The ID of the MPS queue to which the snapshot job is submitted.
     *
     * @example dd3dae411e704030b921e52698e5****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @description The snapshot configurations.
     *
     * @var snapshotConfig
     */
    public $snapshotConfig;

    /**
     * @description The status of the snapshot job. Valid values:
     *
     *   **Submitted**: The job is submitted.
     *   **Snapshoting**: The job is being processed.
     *   **Success**: The job is successful.
     *   **Fail**: The job fails.
     *
     * @example Snapshoting
     *
     * @var string
     */
    public $state;

    /**
     * @description The number of single images that are contained in the tiled image.
     *
     * @example 5
     *
     * @var string
     */
    public $tileCount;

    /**
     * @description The custom data.
     *
     * @example testid-001
     *
     * @var string
     */
    public $userData;
    protected $_name = [
        'code'             => 'Code',
        'count'            => 'Count',
        'creationTime'     => 'CreationTime',
        'id'               => 'Id',
        'input'            => 'Input',
        'MNSMessageResult' => 'MNSMessageResult',
        'message'          => 'Message',
        'pipelineId'       => 'PipelineId',
        'snapshotConfig'   => 'SnapshotConfig',
        'state'            => 'State',
        'tileCount'        => 'TileCount',
        'userData'         => 'UserData',
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
        if (null !== $this->count) {
            $res['Count'] = $this->count;
        }
        if (null !== $this->creationTime) {
            $res['CreationTime'] = $this->creationTime;
        }
        if (null !== $this->id) {
            $res['Id'] = $this->id;
        }
        if (null !== $this->input) {
            $res['Input'] = null !== $this->input ? $this->input->toMap() : null;
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
        if (null !== $this->snapshotConfig) {
            $res['SnapshotConfig'] = null !== $this->snapshotConfig ? $this->snapshotConfig->toMap() : null;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }
        if (null !== $this->tileCount) {
            $res['TileCount'] = $this->tileCount;
        }
        if (null !== $this->userData) {
            $res['UserData'] = $this->userData;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return snapshotJob
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Code'])) {
            $model->code = $map['Code'];
        }
        if (isset($map['Count'])) {
            $model->count = $map['Count'];
        }
        if (isset($map['CreationTime'])) {
            $model->creationTime = $map['CreationTime'];
        }
        if (isset($map['Id'])) {
            $model->id = $map['Id'];
        }
        if (isset($map['Input'])) {
            $model->input = input::fromMap($map['Input']);
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
        if (isset($map['SnapshotConfig'])) {
            $model->snapshotConfig = snapshotConfig::fromMap($map['SnapshotConfig']);
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }
        if (isset($map['TileCount'])) {
            $model->tileCount = $map['TileCount'];
        }
        if (isset($map['UserData'])) {
            $model->userData = $map['UserData'];
        }

        return $model;
    }
}
