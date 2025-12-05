<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\mergeList;

use AlibabaCloud\Tea\Model;

class merge extends Model
{
    /**
     * @description The duration of the clip.
     *
     *   Format: `hh:mm:ss[.SSS]` or `sssss[.SSS]`.
     *   Examples: 01:59:59.999 and 32000.23.
     *
     * @example 01:59:59.999
     *
     * @var string
     */
    public $duration;

    /**
     * @description The OSS URL of the clip.
     *
     *   Example: `http://example-bucket-****.oss-cn-hangzhou.aliyuncs.com/example-object.flv`.
     *   The object must be URL-encoded by using the UTF-8 standard.
     *
     * @example http://example-bucket.oss-cn-hangzhou.aliyuncs.com/example-object.flv
     *
     * @var string
     */
    public $mergeURL;

    /**
     * @description The Alibaba Cloud Resource Name (ARN) of the Resource Access Management (RAM) role used for delegated authorization.
     *
     * @example acs:ram::<your uid>:role/<your role name>
     *
     * @var string
     */
    public $roleArn;

    /**
     * @description The start point in time of the clip.
     *
     *   Format: `hh:mm:ss[.SSS]` or `sssss[.SSS]`.
     *   Examples: 01:59:59.999 and 32000.23.
     *
     * @var string
     */
    public $start;
    protected $_name = [
        'duration' => 'Duration',
        'mergeURL' => 'MergeURL',
        'roleArn'  => 'RoleArn',
        'start'    => 'Start',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->duration) {
            $res['Duration'] = $this->duration;
        }
        if (null !== $this->mergeURL) {
            $res['MergeURL'] = $this->mergeURL;
        }
        if (null !== $this->roleArn) {
            $res['RoleArn'] = $this->roleArn;
        }
        if (null !== $this->start) {
            $res['Start'] = $this->start;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return merge
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['MergeURL'])) {
            $model->mergeURL = $map['MergeURL'];
        }
        if (isset($map['RoleArn'])) {
            $model->roleArn = $map['RoleArn'];
        }
        if (isset($map['Start'])) {
            $model->start = $map['Start'];
        }

        return $model;
    }
}
