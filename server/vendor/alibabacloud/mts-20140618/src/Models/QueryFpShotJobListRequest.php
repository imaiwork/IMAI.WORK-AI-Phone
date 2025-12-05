<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class QueryFpShotJobListRequest extends Model
{
    /**
     * @description The end of the time range within which the jobs to be queried were created.
     *
     *   Specify the time in the ISO 8601 standard in the
     *   YYYY-MM-DDThh:mm:ssZ format. The time must be in UTC.
     *
     * @example 2022-02-14T02:16:07Z
     *
     * @var string
     */
    public $endOfJobCreatedTimeRange;

    /**
     * @description The ID of the media fingerprint analysis job that you want to query. To view the job ID, log on to the [ApsaraVideo Media Processing (MPS) console](https://mps.console.aliyun.com/overview), click **Tasks** in the left-side navigation pane, and then click the **Video DNA** tab on the Tasks page. You can query up to 10 media fingerprint analysis jobs at a time. Separate multiple job IDs with commas (,).
     *
     * @example 88c6ca184c0e47098a5b665e2a12****
     *
     * @var string
     */
    public $jobIds;

    /**
     * @description The maximum number of entries to return on each page.
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
     * @description The token that is used to retrieve the next page of the query results. You do not need to specify this parameter in the first request. The response to the first request contains this parameter, which you add to the next request.
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
     * @description The ID of the MPS queue. To view the ID of the MPS queue, log on to the [MPS console](https://mps.console.aliyun.com/overview) and choose **Global Settings** > **Pipelines** in the left-side navigation pane.
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
     * @description The beginning of the time range within which the jobs to be queried were created.
     *
     *   Specify the time in the ISO 8601 standard in the
     *   YYYY-MM-DDThh:mm:ssZ format. The time must be in UTC.
     *
     * @example 2021-12-22T03:48:05Z
     *
     * @var string
     */
    public $startOfJobCreatedTimeRange;

    /**
     * @description The status of the jobs to be queried. Valid values:
     *
     *   **All**: all jobs.
     *   **Queuing**: the jobs that are being queued.
     *   **Analysing**: the jobs that are in progress.
     *   **Fail**: failed jobs.
     *   **Success**: successful jobs.
     *
     * @example All
     *
     * @var string
     */
    public $state;
    protected $_name = [
        'endOfJobCreatedTimeRange'   => 'EndOfJobCreatedTimeRange',
        'jobIds'                     => 'JobIds',
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
        if (null !== $this->jobIds) {
            $res['JobIds'] = $this->jobIds;
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
     * @return QueryFpShotJobListRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['EndOfJobCreatedTimeRange'])) {
            $model->endOfJobCreatedTimeRange = $map['EndOfJobCreatedTimeRange'];
        }
        if (isset($map['JobIds'])) {
            $model->jobIds = $map['JobIds'];
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
