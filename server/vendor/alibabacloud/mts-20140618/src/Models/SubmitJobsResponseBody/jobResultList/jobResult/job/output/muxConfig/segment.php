<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\muxConfig;

use AlibabaCloud\Tea\Model;

class segment extends Model
{
    /**
     * @description The length of the segment. The value is an integer. Unit: seconds.
     *
     *   Valid values: **[1,10]**.
     *   Default value: **10**.
     *
     * @example 20
     *
     * @var string
     */
    public $duration;
    protected $_name = [
        'duration' => 'Duration',
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

        return $res;
    }

    /**
     * @param array $map
     *
     * @return segment
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }

        return $model;
    }
}
