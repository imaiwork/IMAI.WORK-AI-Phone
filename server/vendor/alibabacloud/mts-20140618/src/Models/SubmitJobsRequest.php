<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class SubmitJobsRequest extends Model
{
    /**
     * @description The information about the input file. For more information, see the "Input" section of the [Parameter details](https://help.aliyun.com/document_detail/29253.html) topic.
     *
     * >
     *
     *   The path of an Object Storage Service (OSS) object must be URL-encoded in UTF-8 before you use the path in MPS.
     *
     *   The OSS bucket must reside in the same region as your MPS service.
     *
     * This parameter is required.
     * @example a/b/c/test-cn.srt
     *
     * @var string
     */
    public $input;

    /**
     * @description The name of the OSS bucket that stores the output file.
     *
     *   For more information about the term bucket, see [Terms](https://help.aliyun.com/document_detail/31827.html).
     *
     * This parameter is required.
     * @example exampleBucket
     *
     * @var string
     */
    public $outputBucket;

    /**
     * @description The region in which the OSS bucket that stores the output file resides.
     *
     *   The OSS bucket must reside in the same region as MPS.
     *   For more information about the term bucket, see [Terms](https://help.aliyun.com/document_detail/31827.html).
     *
     * @example oss-cn-hangzhou
     *
     * @var string
     */
    public $outputLocation;

    /**
     * @description The job output configurations. For more information, see the "Output" section of the [Parameter details](https://help.aliyun.com/document_detail/29253.html) topic.
     *
     *   Specify the value in a JSON array of Output objects. You can specify up to 30 Output objects.
     *
     * This parameter is required.
     * @example [{"OutputObject":"exampleOutput.mp4","TemplateId":"6181666213ab41b9bc21da8ff5ff****","WaterMarks":[{"InputFile":{"Bucket":"exampleBucket","Location":"oss-cn-hangzhou","Object":"image_01.png"},"WaterMarkTemplateId":"9b772ce2740d4d55876d8b542d47****"}],"UserData":"testid-001"}]
     *
     * @var string
     */
    public $outputs;

    /**
     * @var string
     */
    public $ownerAccount;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @description The ID of the MPS queue. For more information, see [Terms](https://help.aliyun.com/document_detail/31827.html).
     *
     *   To obtain the ID of an MPS queue, you can log on to the [MPS console](https://mps.console.aliyun.com/overview) and choose **Global Settings** > **MPS Queue and Callback** in the left-side navigation pane.
     *   If you want to receive asynchronous message notifications, associate an MNS queue or topic with the MPS queue. For more information, see [Receive notifications](https://www.alibabacloud.com/help/zh/apsaravideo-for-media-processing/latest/receive-message-notifications).
     *
     * This parameter is required.
     * @example dd3dae411e704030b921e52698e5****
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
    protected $_name = [
        'input'                => 'Input',
        'outputBucket'         => 'OutputBucket',
        'outputLocation'       => 'OutputLocation',
        'outputs'              => 'Outputs',
        'ownerAccount'         => 'OwnerAccount',
        'ownerId'              => 'OwnerId',
        'pipelineId'           => 'PipelineId',
        'resourceOwnerAccount' => 'ResourceOwnerAccount',
        'resourceOwnerId'      => 'ResourceOwnerId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->input) {
            $res['Input'] = $this->input;
        }
        if (null !== $this->outputBucket) {
            $res['OutputBucket'] = $this->outputBucket;
        }
        if (null !== $this->outputLocation) {
            $res['OutputLocation'] = $this->outputLocation;
        }
        if (null !== $this->outputs) {
            $res['Outputs'] = $this->outputs;
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

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SubmitJobsRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Input'])) {
            $model->input = $map['Input'];
        }
        if (isset($map['OutputBucket'])) {
            $model->outputBucket = $map['OutputBucket'];
        }
        if (isset($map['OutputLocation'])) {
            $model->outputLocation = $map['OutputLocation'];
        }
        if (isset($map['Outputs'])) {
            $model->outputs = $map['Outputs'];
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

        return $model;
    }
}
