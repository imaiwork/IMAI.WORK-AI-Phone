<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\digiWaterMark;

use AlibabaCloud\Tea\Model;

class inputFile extends Model
{
    /**
     * @description The name of the OSS bucket in which the input file is stored.
     *
     * @example example-bucket-****
     *
     * @var string
     */
    public $bucket;

    /**
     * @description The ID of the OSS region in which the input file is stored.
     *
     * @example oss-cn-hangzhou
     *
     * @var string
     */
    public $location;

    /**
     * @description The name of the OSS object that is used as the input file.
     *
     * @example example-intput.flv
     *
     * @var string
     */
    public $object;
    protected $_name = [
        'bucket'   => 'Bucket',
        'location' => 'Location',
        'object'   => 'Object',
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

        return $res;
    }

    /**
     * @param array $map
     *
     * @return inputFile
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

        return $model;
    }
}
