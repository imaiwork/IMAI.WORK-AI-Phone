<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class ListJobRequest extends Model
{
    /**
     * @description The end of the time range to query. Specify the time in the ISO 8601 standard in the `YYYY-MM-DDThh:mm:ssZ` format. The time must be in UTC.
     *
     * @example 2014-01-11T12:00:00Z
     *
     * @var string
     */
    public $endOfJobCreatedTimeRange;

    /**
     * @description The number of entries per page.
     *
     *   Default value: **10**.
     *   Valid values: **1 to 100**.
     *
     * @example 10
     *
     * @var int
     */
    public $maximumPageSize;

    /**
     * @description The token that is used to retrieve the next page of the query results. You do not need to specify this parameter for the first request. You must specify the token that is obtained from the previous query as the value of NextPageToken.
     *
     * @example 16f01ad6175e4230ac42bb5182cd****
     *
     * @var string
     */
    public $nextPageToken;

    /**
     * @var string
     */
    public $ownerAccount;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @description The ID of the MPS queue to which the job is submitted. To obtain the ID of an MPS queue, you can log on to the [MPS console](https://mps.console.aliyun.com/overview) and choose **Global Settings** > **MPS Queue and Callback** in the left-side navigation pane.
     *
     * @example 88c6ca184c0e424d5w5b665e2a12****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @var string
     */
    public $resourceOwnerAccount;

    /**
     * @var int
     */
    public $resourceOwnerId;

    /**
     * @description The beginning of the time range to query. Specify the time in the ISO 8601 standard in the `YYYY-MM-DDThh:mm:ssZ` format. The time must be in UTC.
     *
     * @example 2014-01-10T12:00:00Z
     *
     * @var string
     */
    public $startOfJobCreatedTimeRange;

    /**
     * @description The state of the transcoding job. Default value: **All**. Valid values:
     *
     *   **All**
     *   **Submitted**
     *   **Transcoding**
     *   **TranscodeSuccess**
     *   **TranscodeFail**
     *   **TranscodeCancelled**
     *
     * @example All
     *
     * @var string
     */
    public $state;
    protected $_name = [
        'endOfJobCreatedTimeRange'   => 'EndOfJobCreatedTimeRange',
        'maximumPageSize'            => 'MaximumPageSize',
        'nextPageToken'              => 'NextPageToken',
        'ownerAccount'               => 'OwnerAccount',
        'ownerId'                    => 'OwnerId',
        'pipelineId'                 => 'PipelineId',
        'resourceOwnerAccount'       => 'ResourceOwnerAccount',
        'resourceOwnerId'            => 'ResourceOwnerId',
        'startOfJobCreatedTimeRange' => 'StartOfJobCreatedTimeRange',
        'state'                      => 'State',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->endOfJobCreatedTimeRange) {
            $res['EndOfJobCreatedTimeRange'] = $this->endOfJobCreatedTimeRange;
        }
        if (null !== $this->maximumPageSize) {
            $res['MaximumPageSize'] = $this->maximumPageSize;
        }
        if (null !== $this->nextPageToken) {
            $res['NextPageToken'] = $this->nextPageToken;
        }
        if (null !== $this->ownerAccount) {
            $res['OwnerAccount'] = $this->ownerAccount;
        }
        if (null !== $this->ownerId) {
            $res['OwnerId'] = $this->ownerId;
        }
        if (null !== $this->pipelineId) {
            $res['PipelineId'] = $this->pipelineId;
        }
        if (null !== $this->resourceOwnerAccount) {
            $res['ResourceOwnerAccount'] = $this->resourceOwnerAccount;
        }
        if (null !== $this->resourceOwnerId) {
            $res['ResourceOwnerId'] = $this->resourceOwnerId;
        }
        if (null !== $this->startOfJobCreatedTimeRange) {
            $res['StartOfJobCreatedTimeRange'] = $this->startOfJobCreatedTimeRange;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return ListJobRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['EndOfJobCreatedTimeRange'])) {
            $model->endOfJobCreatedTimeRange = $map['EndOfJobCreatedTimeRange'];
        }
        if (isset($map['MaximumPageSize'])) {
            $model->maximumPageSize = $map['MaximumPageSize'];
        }
        if (isset($map['NextPageToken'])) {
            $model->nextPageToken = $map['NextPageToken'];
        }
        if (isset($map['OwnerAccount'])) {
            $model->ownerAccount = $map['OwnerAccount'];
        }
        if (isset($map['OwnerId'])) {
            $model->ownerId = $map['OwnerId'];
        }
        if (isset($map['PipelineId'])) {
            $model->pipelineId = $map['PipelineId'];
        }
        if (isset($map['ResourceOwnerAccount'])) {
            $model->resourceOwnerAccount = $map['ResourceOwnerAccount'];
        }
        if (isset($map['ResourceOwnerId'])) {
            $model->resourceOwnerId = $map['ResourceOwnerId'];
        }
        if (isset($map['StartOfJobCreatedTimeRange'])) {
            $model->startOfJobCreatedTimeRange = $map['StartOfJobCreatedTimeRange'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }

        return $model;
    }
}
