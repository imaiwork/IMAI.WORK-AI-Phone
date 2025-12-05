<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\fpShots\fpShot\fpShotSlices;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\fpShots\fpShot\fpShotSlices\fpShotSlice\duplication;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\fpShots\fpShot\fpShotSlices\fpShotSlice\input;
use AlibabaCloud\Tea\Model;

class fpShotSlice extends Model
{
    /**
     * @description The start point in time and duration of the similar video clip in the video file that has similar fingerprints to the input video in the video fingerprint library.
     *
     * @var duplication
     */
    public $duplication;

    /**
     * @description The start time and duration of the similar video clip in the input video.
     *
     * @var input
     */
    public $input;

    /**
     * @description The similarity of the input video clip against the video file that has similar fingerprints to the input video in the video fingerprint library.
     *
     * @example 0
     *
     * @var string
     */
    public $similarity;
    protected $_name = [
        'duplication' => 'Duplication',
        'input'       => 'Input',
        'similarity'  => 'Similarity',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->duplication) {
            $res['Duplication'] = null !== $this->duplication ? $this->duplication->toMap() : null;
        }
        if (null !== $this->input) {
            $res['Input'] = null !== $this->input ? $this->input->toMap() : null;
        }
        if (null !== $this->similarity) {
            $res['Similarity'] = $this->similarity;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpShotSlice
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Duplication'])) {
            $model->duplication = duplication::fromMap($map['Duplication']);
        }
        if (isset($map['Input'])) {
            $model->input = input::fromMap($map['Input']);
        }
        if (isset($map['Similarity'])) {
            $model->similarity = $map['Similarity'];
        }

        return $model;
    }
}
