<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob\input;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob\MNSMessageResult;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob\snapshotConfig;
use AlibabaCloud\Tea\Model;

class snapshotJob extends Model
{
    /**
     * @description You can call this operation to query up to 10 snapshot jobs at a time.
     *
     *
     * You can call this operation up to 100 times per second per account. If the number of calls per second exceeds the limit, throttling is triggered. As a result, your business may be affected. We recommend that you take note of the limit when you call this operation. For more information, see [QPS limit](https://www.alibabacloud.com/help/en/apsaravideo-for-media-processing/latest/qps-limit).
     * @example InvalidParameter
     *
     * @var string
     */
    public $code;

    /**
     * @description The ID of the request.
     *
     * @example 1
     *
     * @var string
     */
    public $count;

    /**
     * @description The stride of a single image.
     *
     * @example 2021-06-30T12:34:29Z
     *
     * @var string
     */
    public $creationTime;

    /**
     * @description The OSS output file of the tiling job.
     *
     * @example cc6cbef8e8d5481ca536f5d2a466****
     *
     * @var string
     */
    public $id;

    /**
     * @description The number of snapshots that are contained in the tiled image.
     *
     * @var input
     */
    public $input;

    /**
     * @description The OSS object that is used as the input file.
     *
     * @var MNSMessageResult
     */
    public $MNSMessageResult;

    /**
     * @description The ARN of the specified RAM role. Format: acs:ram::$accountID:role/$roleName.
     *
     * @example The resource operated InputFile is bad
     *
     * @var string
     */
    public $message;

    /**
     * @description The start time for taking snapshots. Unit: milliseconds.
     *
     * @example b11c171cced04565b1f38f1ecc39****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @description The height of a single image. The default value is the height of the output snapshot.
     *
     * @var snapshotConfig
     */
    public $snapshotConfig;

    /**
     * @description The information about the job input.
     *
     * @example Snapshoting
     *
     * @var string
     */
    public $state;

    /**
     * @description The snapshot job IDs that do not exist. This parameter is not returned if all specified snapshot jobs are found.
     *
     * @example 7
     *
     * @var string
     */
    public $tileCount;

    /**
     * @description The token that is used to retrieve the next page of the query results. The value is a 32-bit UUID. If the returned query results cannot be displayed within one page, this parameter is returned. The value of this parameter is updated for each query.
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
