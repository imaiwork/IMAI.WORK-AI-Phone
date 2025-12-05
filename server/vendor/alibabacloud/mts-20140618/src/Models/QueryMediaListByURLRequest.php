<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class QueryMediaListByURLRequest extends Model
{
    /**
     * @description The OSS URLs of the media files. To obtain the OSS URL of a media file, you can perform the following operations in the ApsaraVideo Media Processing (MPS) console: In the left-side navigation pane, choose **Media Management** > **Media List**. Find the media file whose OSS URL you want to view and click **Manage** in the **Actions** column. The OSS URL of the media file is displayed on the **Obtain Encoding URL** tab. Separate multiple URLs with commas (,). You can query up to 10 media files at a time.
     *
     *   The URL complies with RFC 3986 and is encoded in UTF-8, with reserved characters being percent-encoded. The value can be up to 3,200 bytes in size. For more information, see [URL encoding](https://help.aliyun.com/document_detail/423796.html).
     *   Only OSS HTTP URLs are supported. Alibaba Cloud CDN URLs and HTTPS URLs are not supported.
     *
     * This parameter is required.
     * @example http://example-bucket-****.oss-cn-shanghai.aliyuncs.com/example.mp4
     *
     * @var string
     */
    public $fileURLs;

    /**
     * @description Specifies whether to include media information in the returned result.
     *
     *   Valid values: true and false.
     *
     *   Default value: **false**.
     *
     * > To obtain detailed information about the media files, set this parameter to true.
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
        'fileURLs'             => 'FileURLs',
        'includeMediaInfo'     => 'IncludeMediaInfo',
        'includePlayList'      => 'IncludePlayList',
        'includeSnapshotList'  => 'IncludeSnapshotList',
        'includeSummaryList'   => 'IncludeSummaryList',
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
        if (null !== $this->fileURLs) {
            $res['FileURLs'] = $this->fileURLs;
        }
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
     * @return QueryMediaListByURLRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FileURLs'])) {
            $model->fileURLs = $map['FileURLs'];
        }
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
