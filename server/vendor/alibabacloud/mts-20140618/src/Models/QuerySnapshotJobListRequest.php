<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class QuerySnapshotJobListRequest extends Model
{
    /**
     * @description The snapshot configuration.
     *
     * @example 2014-01-12T12:00:00Z
     *
     * @var string
     */
    public $endOfJobCreatedTimeRange;

    /**
     * @description The ID of the MPS queue to which the snapshot jobs that you want to query are submitted. To obtain the ID, you can log on to the **MPS console** and choose **Global Settings** > **Pipelines** in the left-side navigation pane.
     *
     * @example 30
     *
     * @var int
     */
    public $maximumPageSize;

    /**
     * @description The end of the time range within which the creation time of snapshot jobs to be queried is.
     *
     *   Specify the time in the ISO 8601 standard in the
     *   YYYY-MM-DDThh:mm:ssZ format. The time must be in UTC.
     *
     * @example cc6cbef8e8d5481ca536f5d2a466****
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
     * @description The start time for taking snapshots. Unit: milliseconds.
     *
     * @example b11c171cced04565b1f38f1ecc39****
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
     * @description The beginning of the time range within which the creation time of snapshot jobs to be queried is.
     *
     *   Specify the time in the ISO 8601 standard in the
     *   YYYY-MM-DDThh:mm:ssZ format. The time must be in UTC.
     *
     * @example 72dfa5e679ab4be9a3ed9974c736****
     *
     * @var string
     */
    public $snapshotJobIds;

    /**
     * @description The time when the job was created.
     *
     * @example 2014-01-10T12:00:00Z
     *
     * @var string
     */
    public $startOfJobCreatedTimeRange;

    /**
     * @description The information about the snapshot jobs.
     *
     * @example Snapshoting
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
        'snapshotJobIds'             => 'SnapshotJobIds',
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
        if (null !== $this->snapshotJobIds) {
            $res['SnapshotJobIds'] = $this->snapshotJobIds;
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
     * @return QuerySnapshotJobListRequest
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
        if (isset($map['SnapshotJobIds'])) {
            $model->snapshotJobIds = $map['SnapshotJobIds'];
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
