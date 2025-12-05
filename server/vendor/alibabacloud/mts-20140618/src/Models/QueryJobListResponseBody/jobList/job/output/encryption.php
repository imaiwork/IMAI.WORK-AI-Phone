<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output;

use AlibabaCloud\Tea\Model;

class encryption extends Model
{
    /**
     * @description The encryption ID.
     *
     * @example 31fa3c9ca8134f9cec2b4b0b0f78****
     *
     * @var string
     */
    public $id;

    /**
     * @description The key that is used to encrypt the video.
     *
     * @example encryptionkey128
     *
     * @var string
     */
    public $key;

    /**
     * @description The key encryption method. Valid values: Base64 and KMS.
     *
     * >  For example, if the key is `encryptionkey128`, the key can be encrypted as `Base64("encryptionkey128")` or `KMS(Base64("encryptionkey128")` depending on the encryption method used.
     * @example Base64
     *
     * @var string
     */
    public $keyType;

    /**
     * @description The URL that is used to request the key. The URL is Base64-encoded.
     *
     * @example https://1161758785*****.cn-shanghai.fc.aliyuncs.com/2016-08-15/proxy/HLS-decyptServer/decyptServer/
     *
     * @var string
     */
    public $keyUri;

    /**
     * @description The number of unencrypted frames at the beginning of the video. Leaving these frames unencrypted enables video playback to quickly start.
     *
     * @example 3
     *
     * @var string
     */
    public $skipCnt;

    /**
     * @description The encryption type. Only **hls-aes-128** may be returned.
     *
     * @example hls-aes-128
     *
     * @var string
     */
    public $type;
    protected $_name = [
        'id'      => 'Id',
        'key'     => 'Key',
        'keyType' => 'KeyType',
        'keyUri'  => 'KeyUri',
        'skipCnt' => 'SkipCnt',
        'type'    => 'Type',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->id) {
            $res['Id'] = $this->id;
        }
        if (null !== $this->key) {
            $res['Key'] = $this->key;
        }
        if (null !== $this->keyType) {
            $res['KeyType'] = $this->keyType;
        }
        if (null !== $this->keyUri) {
            $res['KeyUri'] = $this->keyUri;
        }
        if (null !== $this->skipCnt) {
            $res['SkipCnt'] = $this->skipCnt;
        }
        if (null !== $this->type) {
            $res['Type'] = $this->type;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return encryption
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Id'])) {
            $model->id = $map['Id'];
        }
        if (isset($map['Key'])) {
            $model->key = $map['Key'];
        }
        if (isset($map['KeyType'])) {
            $model->keyType = $map['KeyType'];
        }
        if (isset($map['KeyUri'])) {
            $model->keyUri = $map['KeyUri'];
        }
        if (isset($map['SkipCnt'])) {
            $model->skipCnt = $map['SkipCnt'];
        }
        if (isset($map['Type'])) {
            $model->type = $map['Type'];
        }

        return $model;
    }
}
