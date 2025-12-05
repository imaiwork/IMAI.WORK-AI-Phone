<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\fpShots\fpShot;
use AlibabaCloud\Tea\Model;

class fpShots extends Model
{
    /**
     * @var fpShot[]
     */
    public $fpShot;
    protected $_name = [
        'fpShot' => 'FpShot',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpShot) {
            $res['FpShot'] = [];
            if (null !== $this->fpShot && \is_array($this->fpShot)) {
                $n = 0;
                foreach ($this->fpShot as $item) {
                    $res['FpShot'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpShots
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpShot'])) {
            if (!empty($map['FpShot'])) {
                $model->fpShot = [];
                $n             = 0;
                foreach ($map['FpShot'] as $item) {
                    $model->fpShot[$n++] = null !== $item ? fpShot::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
