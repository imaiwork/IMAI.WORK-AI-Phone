<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\outSubtitleList\outSubtitle;

use AlibabaCloud\Tea\Model;

class outSubtitleFile extends Model
{
    /**
     * @description The name of the OSS bucket in which the output caption is stored.
     *
     * @example exampleBucket
     *
     * @var string
     */
    public $bucket;

    /**
     * @description The OSS region in which the output caption resides.
     *
     * @example oss-cn-hangzhou
     *
     * @var string
     */
    public $location;

    /**
     * @description The name of the OSS object that is used as the output caption.
     *
     * @example example.flv
     *
     * @var string
     */
    public $object;

    /**
     * @description The ARN of the RAM role used for delegated authorization.
     *
     * @example acs:ram::<your uid>:role/<your role name>
     *
     * @var string
     */
    public $roleArn;
    protected $_name = [
        'bucket'   => 'Bucket',
        'location' => 'Location',
        'object'   => 'Object',
        'roleArn'  => 'RoleArn',
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
        if (null !== $this->roleArn) {
            $res['RoleArn'] = $this->roleArn;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return outSubtitleFile
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
        if (isset($map['RoleArn'])) {
            $model->roleArn = $map['RoleArn'];
        }

        return $model;
    }
}
