<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\audioFpShots\fpShot\fpShotSlices\fpShotSlice;

use AlibabaCloud\Tea\Model;

class input extends Model
{
    /**
     * @description The duration of the similar audio clip in the input audio.
     *
     * @example 5
     *
     * @var string
     */
    public $duration;

    /**
     * @description The start point in time of the similar audio clip in the input audio.
     *
     * @example 0
     *
     * @var string
     */
    public $start;
    protected $_name = [
        'duration' => 'Duration',
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
        if (null !== $this->start) {
            $res['Start'] = $this->start;
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
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['Start'])) {
            $model->start = $map['Start'];
        }

        return $model;
    }
}
