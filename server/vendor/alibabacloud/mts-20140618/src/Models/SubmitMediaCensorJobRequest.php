<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class SubmitMediaCensorJobRequest extends Model
{
    /**
     * @description The live comments.
     *
     * @example hello world
     *
     * @var string
     */
    public $barrages;

    /**
     * @description The OSS URL of the image file that is used as the thumbnail. To view the OSS URL of the image file, you can log on to the **MPS console** and choose **Media Management** > **Media List** in the left-side navigation pane. You can specify up to five thumbnails in a JSON array.
     *
     *   Bucket: the name of the OSS bucket that stores the input file.
     *
     *   Location: the OSS region. The OSS region must be the same as the region in which your MPS service is activated.
     *
     *   Object: the OSS object to be moderated.
     *
     **
     *
     **Note**The name of the object cannot start with a forward slash (/). Otherwise, the operation fails to be called.
     *
     * @example [{"Bucket":"example-bucket-****","Location":"oss-cn-shanghai","Object":"example-****.jpeg"}]
     *
     * @var string
     */
    public $coverImages;

    /**
     * @description The description of the video. The value can be up to 128 bytes in size.
     *
     * @example example description
     *
     * @var string
     */
    public $description;

    /**
     * @description The URL of the video.
     *
     * @var string
     */
    public $externalUrl;

    /**
     * @description The Object Storage Service (OSS) URL of the media file to be moderated. To view the OSS URL of the media file, you can log on to the **MPS console** and choose **Media Management** > **Media List** in the left-side navigation pane. To moderate an image file, use the `CoverImage` parameter to specify the OSS URL of the image file. The value is a JSON object. For more information, see the "Input" section of the [Parameter details](https://help.aliyun.com/document_detail/29253.html) topic.
     *
     *   Bucket: the name of the OSS bucket that stores the input file.
     *
     *   Location: the OSS region. The OSS region must be the same as the region in which your MPS service is activated.
     *
     *   Object: the OSS object to be moderated.
     *
     **
     *
     **Note**The name of the object cannot start with a forward slash (/). Otherwise, the operation fails to be called.
     *
     * @example {"Bucket":"example-bucket-****","Location":"oss-cn-shanghai","Object":"example-****.flv"}
     *
     * @var string
     */
    public $input;

    /**
     * @var string
     */
    public $ownerAccount;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @description The ID of the MPS queue. This ID can be used to associate the job with a notification method. To view the ID of the MPS queue, you can log on to the **MPS console** and choose **Global Settings** > **Pipelines** in the left-side navigation pane. An empty string ("") indicates that the default MPS queue is used to run the job. By default, an MPS queue can process a maximum of 10 concurrent content moderation jobs. To increase the limit, [submit a ticket](https://workorder-intl.console.aliyun.com/?spm=5176.12246746.top-nav.ditem-sub.35da7bbcitpQnr#/ticket/createIndex).
     *
     * > MPS queues are automatically created by the system. For more information about how to query and update MPS queues, see the [UpdatePipeline](https://help.aliyun.com/document_detail/188374.html) topic.
     * @example b22c173cced04565b1f38f1ecc39****
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
     * @description The title of the video. The value can be up to 64 bytes in size.
     *
     * @example Hello World
     *
     * @var string
     */
    public $title;

    /**
     * @description The custom data. The value can be up to 128 bytes in size.
     *
     * @example UserDatatestid-001-****
     *
     * @var string
     */
    public $userData;

    /**
     * @description The video moderation configurations and the OSS URLs of the output snapshots. To view the OSS URL of the media file, you can log on to the **MPS console** and choose **Media Management** > **Media List** in the left-side navigation pane.
     *
     * The value is a JSON object.
     *
     *   OutputFile:
     *
     *   Bucket: the name of the OSS bucket that stores the output file.
     *   Location: the OSS region. The OSS region must be the same as the region in which your MPS service is activated.
     *   Object: the OSS object to be generated. In the value, {Count} indicates the sequence number of the frame snapshot.
     *
     *   StoreVideoTimeline: specifies whether to generate the `{jobId}.video_timeline` file. The file is stored in OSS. A value of true indicates that the file is generated. A value of false indicates that the file is not generated. If you do not specify this parameter, the file is not generated by default. For more information about the format of the file, see the "VideoTimelines" parameter in the [QueryMediaCensorJobDetail](https://help.aliyun.com/document_detail/91779.html) topic.
     *
     *   SaveType: the output mode. A value of abnormal indicates that snapshots are generated only for illegal frames. A value of all indicates that snapshots are generated for all frames.
     *
     *   Biztype: the moderation template. If you do not specify this parameter or set the value to common, the default template is used. You can submit a ticket to create a custom moderation template. Then, set this parameter to your user ID to use the custom moderation template.
     *
     *   Scenes: the moderation scenarios. You can specify the moderation scenarios that you want to use. If you do not specify this parameter, the terrorism and porn moderation scenarios are used by default. Valid values:
     *
     *   porn: pornographic content detection
     *   terrorism: terrorist content detection
     *   ad: ad violation detection
     *   live: undesirable scene detection
     *   logo: special logo detection
     *   audio: audio anti-spam
     *
     * This parameter is required.
     * @example {"Scenes" : ["porn"], "OutputFile":{"Bucket": "example-001","Location": "oss-cn-hangzhou","Object": "test/example-{Count}.jpg"},"SaveType" : "abnormal","BizType":"common"}
     *
     * @var string
     */
    public $videoCensorConfig;
    protected $_name = [
        'barrages'             => 'Barrages',
        'coverImages'          => 'CoverImages',
        'description'          => 'Description',
        'externalUrl'          => 'ExternalUrl',
        'input'                => 'Input',
        'ownerAccount'         => 'OwnerAccount',
        'ownerId'              => 'OwnerId',
        'pipelineId'           => 'PipelineId',
        'resourceOwnerAccount' => 'ResourceOwnerAccount',
        'resourceOwnerId'      => 'ResourceOwnerId',
        'title'                => 'Title',
        'userData'             => 'UserData',
        'videoCensorConfig'    => 'VideoCensorConfig',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->barrages) {
            $res['Barrages'] = $this->barrages;
        }
        if (null !== $this->coverImages) {
            $res['CoverImages'] = $this->coverImages;
        }
        if (null !== $this->description) {
            $res['Description'] = $this->description;
        }
        if (null !== $this->externalUrl) {
            $res['ExternalUrl'] = $this->externalUrl;
        }
        if (null !== $this->input) {
            $res['Input'] = $this->input;
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
        if (null !== $this->title) {
            $res['Title'] = $this->title;
        }
        if (null !== $this->userData) {
            $res['UserData'] = $this->userData;
        }
        if (null !== $this->videoCensorConfig) {
            $res['VideoCensorConfig'] = $this->videoCensorConfig;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SubmitMediaCensorJobRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Barrages'])) {
            $model->barrages = $map['Barrages'];
        }
        if (isset($map['CoverImages'])) {
            $model->coverImages = $map['CoverImages'];
        }
        if (isset($map['Description'])) {
            $model->description = $map['Description'];
        }
        if (isset($map['ExternalUrl'])) {
            $model->externalUrl = $map['ExternalUrl'];
        }
        if (isset($map['Input'])) {
            $model->input = $map['Input'];
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
        if (isset($map['Title'])) {
            $model->title = $map['Title'];
        }
        if (isset($map['UserData'])) {
            $model->userData = $map['UserData'];
        }
        if (isset($map['VideoCensorConfig'])) {
            $model->videoCensorConfig = $map['VideoCensorConfig'];
        }

        return $model;
    }
}
