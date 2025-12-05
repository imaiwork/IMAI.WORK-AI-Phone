<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob\snapshotConfig;

use AlibabaCloud\Tea\Model;

class outputFile extends Model
{
    /**
     * @description The OSS bucket that stores the output file.
     *
     * @example example
     *
     * @var string
     */
    public $bucket;

    /**
     * @description The ID of the region in which the output OSS bucket is located.
     *
     * @example example-location
     *
     * @var string
     */
    public $location;

    /**
     * @description The OSS object that is generated as the output file of the tiling job.
     *
     * @example example.png
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
     * @return outputFile
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
