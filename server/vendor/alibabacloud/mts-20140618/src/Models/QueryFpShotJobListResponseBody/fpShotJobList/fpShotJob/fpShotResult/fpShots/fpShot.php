<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\fpShots;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\fpShots\fpShot\fpShotSlices;
use AlibabaCloud\Tea\Model;

class fpShot extends Model
{
    /**
     * @description The video files that have similar fingerprints to the input video in the video fingerprint library.
     *
     * @var fpShotSlices
     */
    public $fpShotSlices;

    /**
     * @description The unique primary key of the input video.
     *
     * @example 498ac941373341599c4777c8d884****
     *
     * @var string
     */
    public $primaryKey;

    /**
     * @description The overall similarity of the input video against video files that have similar fingerprints to the input video in the video fingerprint library.
     *
     * >  The overall similarity is the average value of the similarities of the input video clips with the clips of the video that has a similar fingerprint. If multiple video files that have similar fingerprints to the input video exist in the video fingerprint library, the similarities of the input video against multiple similar video clips are returned.
     * @example 0.8914769887924194
     *
     * @var string
     */
    public $similarity;
    protected $_name = [
        'fpShotSlices' => 'FpShotSlices',
        'primaryKey'   => 'PrimaryKey',
        'similarity'   => 'Similarity',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpShotSlices) {
            $res['FpShotSlices'] = null !== $this->fpShotSlices ? $this->fpShotSlices->toMap() : null;
        }
        if (null !== $this->primaryKey) {
            $res['PrimaryKey'] = $this->primaryKey;
        }
        if (null !== $this->similarity) {
            $res['Similarity'] = $this->similarity;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpShot
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpShotSlices'])) {
            $model->fpShotSlices = fpShotSlices::fromMap($map['FpShotSlices']);
        }
        if (isset($map['PrimaryKey'])) {
            $model->primaryKey = $map['PrimaryKey'];
        }
        if (isset($map['Similarity'])) {
            $model->similarity = $map['Similarity'];
        }

        return $model;
    }
}
