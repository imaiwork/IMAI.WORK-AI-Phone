<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody\snapshotJob;

use AlibabaCloud\Tea\Model;

class input extends Model
{
    /**
     * @description The OSS bucket that stores the object.
     *
     * @example example
     *
     * @var string
     */
    public $bucket;

    /**
     * @description The region in which the OSS bucket resides.
     *
     * @example example-location\\"
     *
     * @var string
     */
    public $location;

    /**
     * @description The OSS object that is used as the input file.
     *
     * @example example.flv
     *
     * @var string
     */
    public $object;

    /**
     * @description The ARN of the specified RAM role. Format: acs:ram::$accountID:role/$roleName.
     *
     * @example acs:ram::1:role/testrole
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
     * @return input
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
