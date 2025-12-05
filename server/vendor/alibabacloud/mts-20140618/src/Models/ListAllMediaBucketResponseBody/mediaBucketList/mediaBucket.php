<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListAllMediaBucketResponseBody\mediaBucketList;

use AlibabaCloud\Tea\Model;

class mediaBucket extends Model
{
    /**
     * @description The name of the media bucket.
     *
     * @example example-bucket-****
     *
     * @var string
     */
    public $bucket;

    /**
     * @description The settings of Object Storage Service (OSS) hotlink protection. For more information, see [Hotlink protection](https://help.aliyun.com/document_detail/31869.html).
     *
     * @example http://www.example.com
     *
     * @var string
     */
    public $referer;

    /**
     * @description The type of the media bucket. Valid values:
     *
     *   Input: input media bucket
     *   Output: output media bucket
     *
     * @example Input
     *
     * @var string
     */
    public $type;
    protected $_name = [
        'bucket'  => 'Bucket',
        'referer' => 'Referer',
        'type'    => 'Type',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->bucket) {
            $res['Bucket'] = $this->bucket;
        }
        if (null !== $this->referer) {
            $res['Referer'] = $this->referer;
        }
        if (null !== $this->type) {
            $res['Type'] = $this->type;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return mediaBucket
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Bucket'])) {
            $model->bucket = $map['Bucket'];
        }
        if (isset($map['Referer'])) {
            $model->referer = $map['Referer'];
        }
        if (isset($map['Type'])) {
            $model->type = $map['Type'];
        }

        return $model;
    }
}
