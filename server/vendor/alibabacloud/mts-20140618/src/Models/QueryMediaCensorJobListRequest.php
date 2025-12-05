<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class QueryMediaCensorJobListRequest extends Model
{
    /**
     * @description The end of the time range within which the jobs to be queried were created.
     *
     *   Specify the time in the ISO 8601 standard in the YYYY-MM-DDThh:mm:ssZ format.
     *   The time must be in UTC.
     *
     * @example 2022-02-14T02:16:07Z
     *
     * @var string
     */
    public $endOfJobCreatedTimeRange;

    /**
     * @description The ID of the content moderation job. You can call the [SubmitMediaCensorJob](https://help.aliyun.com/document_detail/91779.html) operation to query the ID of the content moderation job. Separate multiple IDs with commas (,).
     *
     * @example fa9c34be3bcf42919ac4d1775239****,78dc866518b843259669df58ed30****
     *
     * @var string
     */
    public $jobIds;

    /**
     * @description The maximum number of entries to return on each page.
     *
     *   Default value: **30**.
     *   Valid values: **1 to 300**.
     *
     * @example 20
     *
     * @var int
     */
    public $maximumPageSize;

    /**
     * @description The pagination token that is used in the next request to retrieve a new page of results. You do not need to specify this parameter for the first request. The returned value of NextPageToken is a pagination token, which can be used in the next request to retrieve a new page of results.
     *
     * @example 79aff3eee82242e092899db5f669****
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
     * @description The ID of the ApsaraVideo Media Processing (MPS) queue that is used to run the job. To obtain the ID of the MPS queue, perform the following steps: Log on to the [**MPS console**](https://mps.console.aliyun.com/overview). In the left-side navigation pane, choose **Global Settings** > **Pipelines**.
     *
     * @example c5b30b7c0d0e4a0abde1d5f9e751****
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
     *   Specify the time in the ISO 8601 standard in the YYYY-MM-DDThh:mm:ssZ format.
     *   The time must be in UTC.
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
     * @return QueryMediaCensorJobListRequest
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
