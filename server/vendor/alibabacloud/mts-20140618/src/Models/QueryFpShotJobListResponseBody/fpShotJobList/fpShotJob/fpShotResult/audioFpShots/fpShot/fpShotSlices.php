<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\audioFpShots\fpShot;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\audioFpShots\fpShot\fpShotSlices\fpShotSlice;
use AlibabaCloud\Tea\Model;

class fpShotSlices extends Model
{
    /**
     * @var fpShotSlice[]
     */
    public $fpShotSlice;
    protected $_name = [
        'fpShotSlice' => 'FpShotSlice',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpShotSlice) {
            $res['FpShotSlice'] = [];
            if (null !== $this->fpShotSlice && \is_array($this->fpShotSlice)) {
                $n = 0;
                foreach ($this->fpShotSlice as $item) {
                    $res['FpShotSlice'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpShotSlices
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpShotSlice'])) {
            if (!empty($map['FpShotSlice'])) {
                $model->fpShotSlice = [];
                $n                  = 0;
                foreach ($map['FpShotSlice'] as $item) {
                    $model->fpShotSlice[$n++] = null !== $item ? fpShotSlice::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
