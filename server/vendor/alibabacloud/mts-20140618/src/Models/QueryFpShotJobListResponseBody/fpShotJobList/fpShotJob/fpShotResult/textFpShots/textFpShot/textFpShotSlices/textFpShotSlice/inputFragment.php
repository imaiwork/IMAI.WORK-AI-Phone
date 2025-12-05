<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\textFpShots\textFpShot\textFpShotSlices\textFpShotSlice;

use AlibabaCloud\Tea\Model;

class inputFragment extends Model
{
    /**
     * @description The duration of the similar text snippet in the input text.
     *
     * @example 3
     *
     * @var string
     */
    public $duration;

    /**
     * @description The start time of the similar text snippet in the input text.
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
     * @return inputFragment
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
