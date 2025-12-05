<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\clip;

use AlibabaCloud\Tea\Model;

class timeSpan extends Model
{
    /**
     * @description The duration of the clip.
     *
     *   Format: `hh:mm:ss[.SSS]` or `sssss[.SSS]`.
     *   Valid values: `[00:00:00.000,23:59:59.999]` or `[0.000,86399.999]`.
     *
     * @example 01:00:59.999
     *
     * @var string
     */
    public $duration;

    /**
     * @description The start time.
     *
     *   Format: `hh:mm:ss[.SSS]` or `sssss[.SSS]`.
     *   Valid values: `[00:00:00.000,23:59:59.999]` or `[0.000,86399.999]`.
     *
     * @example 01:59:59.999
     *
     * @var string
     */
    public $seek;
    protected $_name = [
        'duration' => 'Duration',
        'seek'     => 'Seek',
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
        if (null !== $this->seek) {
            $res['Seek'] = $this->seek;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return timeSpan
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['Seek'])) {
            $model->seek = $map['Seek'];
        }

        return $model;
    }
}
