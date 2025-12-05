<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class AddMediaRequest extends Model
{
    /**
     * @description The ID of the category to which the media file belongs. The value cannot be negative.
     *
     * @example 123
     *
     * @var int
     */
    public $cateId;

    /**
     * @description The URL of the thumbnail. To obtain the URL, you can log on to the **MPS console** and choose **Workflows** > **Media Buckets**. Alternatively, you can log on to the **OSS console** and click **My OSS Paths**.
     *
     *   The value can be up to 3,200 bytes in length.
     *   The URL complies with RFC 2396 and is encoded in UTF-8, with reserved characters being percent-encoded. For more information, see [URL encoding](https://help.aliyun.com/document_detail/423796.html).
     *
     * @example http://bucket.oss-cn-hangzhou.aliyuncs.com/example/1.png
     *
     * @var string
     */
    public $coverURL;

    /**
     * @description The description of the media file.
     *
     *   The description can be up to 1,024 bytes in length.
     *   The value must be encoded in UTF-8.
     *
     * @example A test video
     *
     * @var string
     */
    public $description;

    /**
     * @description The path of the input file. You can query the path of the input file in the MPS or OSS console. For more information, see the **Triggering and matching rules for a workflow** section of this topic.
     *
     *   The value can be up to 3,200 bytes in length.
     *   The URL complies with RFC 2396 and is encoded in UTF-8, with reserved characters being percent-encoded. For more information, see [URL encoding](https://help.aliyun.com/document_detail/423796.html).
     *
     * This parameter is required.
     * @example http://bucket.oss-cn-hangzhou.aliyuncs.com/A/B/C/test.mp4
     *
     * @var string
     */
    public $fileURL;

    /**
     * @description Specifies whether to check if the media workflow supports the specified input path. We recommend that you set this parameter to true to prevent errors that may result from invalid paths. Valid values:
     *
     *   **true**: checks whether the workflow supports the specified input path.
     *   **false**: does not check whether the workflow supports the specified input path.
     *
     * @example false
     *
     * @var bool
     */
    public $inputUnbind;

    /**
     * @description The ID of the media workflow that you want to run for the media file. To query the ID of a media workflow, you can log on to the MPS console or call the [AddMediaWorkflow](https://help.aliyun.com/document_detail/44437.html) operation.
     *
     * @example 07da6c65da7f458997336e0de192****
     *
     * @var string
     */
    public $mediaWorkflowId;

    /**
     * @description The custom data of the media workflow.
     *
     *   The value can be up to 1,024 bytes in length.
     *   The value must be encoded in UTF-8.
     *
     * @example test
     *
     * @var string
     */
    public $mediaWorkflowUserData;

    /**
     * @description The subtitle settings that are used to overwrite the original settings.
     *
     *   Example 1: Use `{"WebVTTSubtitleOverrides",[{"RefActivityName":"subtitleNode","WebVTTSubtitleURL":"http://test.oss-cn-hangzhou.aliyuncs.com/example1.vtt"}]}` to overwrite the original subtitle settings during HTTP Live Streaming (HLS) packaging.
     *   Example 2: Use `{"subtitleTransNodeName":{"InputConfig":{"Format":"stl","InputFile":{"URL":"http://subtitleBucket.oss-cn-hangzhou.aliyuncs.com/package/example/CENG.stl"}}}}` to overwrite the original subtitle settings during Dynamic Adaptive Streaming over HTTP (DASH) packaging.
     *
     * @example {“subtitleTransNodeName”:{“InputConfig”:{“Format”:”stl”,”InputFile”:{“URL”:”http://exampleBucket.oss-cn-hangzhou.aliyuncs.com/package/example/CENG.stl"}}}}
     *
     * @var string
     */
    public $overrideParams;

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

    /**
     * @description The tags that you want to add to the media file.
     *
     * > In MPS, each tag that is specified for a media file is independent. You can search for all the media files that have the same tags in the Media Library.
     *
     *   You can specify up to 16 tags for a media file. Separate multiple tags with commas (,).
     *   Each tag can be up to 32 bytes in size
     *   The value must be encoded in UTF-8.
     *
     * @example tag1,tag2
     *
     * @var string
     */
    public $tags;

    /**
     * @description The title of the media file.
     *
     *   The title can be up to 128 bytes in length.
     *   The value must be encoded in UTF-8.
     *
     * @example mytest
     *
     * @var string
     */
    public $title;
    protected $_name = [
        'cateId'                => 'CateId',
        'coverURL'              => 'CoverURL',
        'description'           => 'Description',
        'fileURL'               => 'FileURL',
        'inputUnbind'           => 'InputUnbind',
        'mediaWorkflowId'       => 'MediaWorkflowId',
        'mediaWorkflowUserData' => 'MediaWorkflowUserData',
        'overrideParams'        => 'OverrideParams',
        'ownerAccount'          => 'OwnerAccount',
        'ownerId'               => 'OwnerId',
        'resourceOwnerAccount'  => 'ResourceOwnerAccount',
        'resourceOwnerId'       => 'ResourceOwnerId',
        'tags'                  => 'Tags',
        'title'                 => 'Title',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->cateId) {
            $res['CateId'] = $this->cateId;
        }
        if (null !== $this->coverURL) {
            $res['CoverURL'] = $this->coverURL;
        }
        if (null !== $this->description) {
            $res['Description'] = $this->description;
        }
        if (null !== $this->fileURL) {
            $res['FileURL'] = $this->fileURL;
        }
        if (null !== $this->inputUnbind) {
            $res['InputUnbind'] = $this->inputUnbind;
        }
        if (null !== $this->mediaWorkflowId) {
            $res['MediaWorkflowId'] = $this->mediaWorkflowId;
        }
        if (null !== $this->mediaWorkflowUserData) {
            $res['MediaWorkflowUserData'] = $this->mediaWorkflowUserData;
        }
        if (null !== $this->overrideParams) {
            $res['OverrideParams'] = $this->overrideParams;
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
        if (null !== $this->tags) {
            $res['Tags'] = $this->tags;
        }
        if (null !== $this->title) {
            $res['Title'] = $this->title;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return AddMediaRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CateId'])) {
            $model->cateId = $map['CateId'];
        }
        if (isset($map['CoverURL'])) {
            $model->coverURL = $map['CoverURL'];
        }
        if (isset($map['Description'])) {
            $model->description = $map['Description'];
        }
        if (isset($map['FileURL'])) {
            $model->fileURL = $map['FileURL'];
        }
        if (isset($map['InputUnbind'])) {
            $model->inputUnbind = $map['InputUnbind'];
        }
        if (isset($map['MediaWorkflowId'])) {
            $model->mediaWorkflowId = $map['MediaWorkflowId'];
        }
        if (isset($map['MediaWorkflowUserData'])) {
            $model->mediaWorkflowUserData = $map['MediaWorkflowUserData'];
        }
        if (isset($map['OverrideParams'])) {
            $model->overrideParams = $map['OverrideParams'];
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
        if (isset($map['Tags'])) {
            $model->tags = $map['Tags'];
        }
        if (isset($map['Title'])) {
            $model->title = $map['Title'];
        }

        return $model;
    }
}
