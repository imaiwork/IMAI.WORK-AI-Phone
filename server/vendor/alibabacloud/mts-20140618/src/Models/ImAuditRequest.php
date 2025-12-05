<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class ImAuditRequest extends Model
{
    /**
     * @description The business type. By default, the public business type is used.
     *
     * @example 139440480445****
     *
     * @var string
     */
    public $bizType;

    /**
     * @description The custom text entries. You can specify up to 5 text entries. The value must be a JSON array. You must specify at least one of the Images and Contents parameters.
     *
     * @example ["Hello","Who are you","Where am I"]
     *
     * @var string
     */
    public $contents;

    /**
     * @description The image URLs. You can specify up to 5 image URLs. The value must be a JSON array. To view the URLs of the images, you can log on to the **ApsaraVideo Media Processing (MPS) console** and choose **Media Management** > **Media List** in the left-side navigation pane. You must set at least one of the Images and Contents parameters. The image to be moderated must meet the following limits. Otherwise, the moderation task may fail.
     *
     *   The image size cannot exceed 20 MB, the height or width of the image cannot exceed 30,000 pixels, and the image cannot exceed 0.25 billion pixels.
     *   We recommend that you upload images of at least 256 × 256 pixels to ensure required moderation result.
     *
     * @var string
     */
    public $images;

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
     * @description The moderation scenarios. Separate multiple scenarios with commas (,). For example, if you specify {"porn","terrorism"} for this parameter, both pornographic content detection and terrorist content detection are performed on the images and text. Valid values:
     *
     *   porn: pornography
     *   terrorism: terrorist content
     *   ad: ad violation
     *   qrcode: QR code
     *   live: undesirable scene
     *   logo: special logo
     *   antispam: text anti-spam (valid only for text)
     *
     * This parameter is required.
     * @example ["porn","terrorism","ad"]
     *
     * @var string
     */
    public $scenes;
    protected $_name = [
        'bizType'              => 'BizType',
        'contents'             => 'Contents',
        'images'               => 'Images',
        'ownerId'              => 'OwnerId',
        'resourceOwnerAccount' => 'ResourceOwnerAccount',
        'resourceOwnerId'      => 'ResourceOwnerId',
        'scenes'               => 'Scenes',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->bizType) {
            $res['BizType'] = $this->bizType;
        }
        if (null !== $this->contents) {
            $res['Contents'] = $this->contents;
        }
        if (null !== $this->images) {
            $res['Images'] = $this->images;
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
        if (null !== $this->scenes) {
            $res['Scenes'] = $this->scenes;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return ImAuditRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['BizType'])) {
            $model->bizType = $map['BizType'];
        }
        if (isset($map['Contents'])) {
            $model->contents = $map['Contents'];
        }
        if (isset($map['Images'])) {
            $model->images = $map['Images'];
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
        if (isset($map['Scenes'])) {
            $model->scenes = $map['Scenes'];
        }

        return $model;
    }
}
