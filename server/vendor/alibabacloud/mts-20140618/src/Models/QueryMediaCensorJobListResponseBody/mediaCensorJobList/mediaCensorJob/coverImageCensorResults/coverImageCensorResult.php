<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobListResponseBody\mediaCensorJobList\mediaCensorJob\coverImageCensorResults;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobListResponseBody\mediaCensorJobList\mediaCensorJob\coverImageCensorResults\coverImageCensorResult\results;
use AlibabaCloud\Tea\Model;

class coverImageCensorResult extends Model
{
    /**
     * @description The OSS bucket that stores the video thumbnail.
     *
     * @example example-Bucket-****
     *
     * @var string
     */
    public $bucket;

    /**
     * @description The OSS region in which the video thumbnail resides.
     *
     * @example oss-cn-shanghai
     *
     * @var string
     */
    public $location;

    /**
     * @description The Object Storage Service (OSS) object that is used as the video thumbnail.
     *
     * @example test/ai/censor/v2/vme-****.jpg
     *
     * @var string
     */
    public $object;

    /**
     * @description The moderation results of the content moderation jobs.
     *
     * @var results
     */
    public $results;
    protected $_name = [
        'bucket'   => 'Bucket',
        'location' => 'Location',
        'object'   => 'Object',
        'results'  => 'Results',
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
        if (null !== $this->location) {
            $res['Location'] = $this->location;
        }
        if (null !== $this->object) {
            $res['Object'] = $this->object;
        }
        if (null !== $this->results) {
            $res['Results'] = null !== $this->results ? $this->results->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return coverImageCensorResult
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Bucket'])) {
            $model->bucket = $map['Bucket'];
        }
        if (isset($map['Location'])) {
            $model->location = $map['Location'];
        }
        if (isset($map['Object'])) {
            $model->object = $map['Object'];
        }
        if (isset($map['Results'])) {
            $model->results = results::fromMap($map['Results']);
        }

        return $model;
    }
}
