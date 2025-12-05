<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class QueryMediaListRequest extends Model
{
    /**
     * @description Specifies whether to include media information in the returned result.
     *
     *   Valid values: true and false.
     *   Default value: **false**.
     *
     * @example true
     *
     * @var bool
     */
    public $includeMediaInfo;

    /**
     * @description Specifies whether to include playback information in the returned result.
     *
     *   Valid values: true and false.
     *   Default value: **false**.
     *
     * @example true
     *
     * @var bool
     */
    public $includePlayList;

    /**
     * @description Specifies whether to include snapshot information in the returned result.
     *
     *   Valid values: true and false.
     *   Default value: **false**.
     *
     * @example true
     *
     * @var bool
     */
    public $includeSnapshotList;

    /**
     * @description Specifies whether to include summaries in the returned result.
     *
     *   Valid values: true and false.
     *   Default value: **false**.
     *
     * @example true
     *
     * @var bool
     */
    public $includeSummaryList;

    /**
     * @description The IDs of the media files. To obtain the ID of a media file, you can perform the following operations in the ApsaraVideo Media Processing (MPS) console: In the left-side navigation pane, choose **Media Management** > **Media List**. Find the required video and click Manage. The ID of the video is displayed on the Basics tab. Separate multiple IDs with commas (,). You can query up to 10 media files at a time.
     *
     * This parameter is required.
     * @example 3e1cd21131a94525be55acf65888****,e26cfa29e784402388463f61dbec****
     *
     * @var string
     */
    public $mediaIds;

    /**
     * @var string
     */
    public $ownerAccount;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @var string
     */
    public $resourceOwnerAccount;

    /**
     * @var int
     */
    public $resourceOwnerId;
    protected $_name = [
        'includeMediaInfo'     => 'IncludeMediaInfo',
        'includePlayList'      => 'IncludePlayList',
        'includeSnapshotList'  => 'IncludeSnapshotList',
        'includeSummaryList'   => 'IncludeSummaryList',
        'mediaIds'             => 'MediaIds',
        'ownerAccount'         => 'OwnerAccount',
        'ownerId'              => 'OwnerId',
        'resourceOwnerAccount' => 'ResourceOwnerAccount',
        'resourceOwnerId'      => 'ResourceOwnerId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->includeMediaInfo) {
            $res['IncludeMediaInfo'] = $this->includeMediaInfo;
        }
        if (null !== $this->includePlayList) {
            $res['IncludePlayList'] = $this->includePlayList;
        }
        if (null !== $this->includeSnapshotList) {
            $res['IncludeSnapshotList'] = $this->includeSnapshotList;
        }
        if (null !== $this->includeSummaryList) {
            $res['IncludeSummaryList'] = $this->includeSummaryList;
        }
        if (null !== $this->mediaIds) {
            $res['MediaIds'] = $this->mediaIds;
        }
        if (null !== $this->ownerAccount) {
            $res['OwnerAccount'] = $this->ownerAccount;
        }
        if (null !== $this->ownerId) {
            $res['OwnerId'] = $this->ownerId;
        }
        if (null !== $this->resourceOwnerAccount) {
            $res['ResourceOwnerAccount'] = $this->resourceOwnerAccount;
        }
        if (null !== $this->resourceOwnerId) {
            $res['ResourceOwnerId'] = $this->resourceOwnerId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryMediaListRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['IncludeMediaInfo'])) {
            $model->includeMediaInfo = $map['IncludeMediaInfo'];
        }
        if (isset($map['IncludePlayList'])) {
            $model->includePlayList = $map['IncludePlayList'];
        }
        if (isset($map['IncludeSnapshotList'])) {
            $model->includeSnapshotList = $map['IncludeSnapshotList'];
        }
        if (isset($map['IncludeSummaryList'])) {
            $model->includeSummaryList = $map['IncludeSummaryList'];
        }
        if (isset($map['MediaIds'])) {
            $model->mediaIds = $map['MediaIds'];
        }
        if (isset($map['OwnerAccount'])) {
            $model->ownerAccount = $map['OwnerAccount'];
        }
        if (isset($map['OwnerId'])) {
            $model->ownerId = $map['OwnerId'];
        }
        if (isset($map['ResourceOwnerAccount'])) {
            $model->resourceOwnerAccount = $map['ResourceOwnerAccount'];
        }
        if (isset($map['ResourceOwnerId'])) {
            $model->resourceOwnerId = $map['ResourceOwnerId'];
        }

        return $model;
    }
}
