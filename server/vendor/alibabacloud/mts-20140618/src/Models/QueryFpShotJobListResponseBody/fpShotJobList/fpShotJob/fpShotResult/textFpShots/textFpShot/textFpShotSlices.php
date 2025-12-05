<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\textFpShots\textFpShot;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\textFpShots\textFpShot\textFpShotSlices\textFpShotSlice;
use AlibabaCloud\Tea\Model;

class textFpShotSlices extends Model
{
    /**
     * @var textFpShotSlice[]
     */
    public $textFpShotSlice;
    protected $_name = [
        'textFpShotSlice' => 'TextFpShotSlice',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->textFpShotSlice) {
            $res['TextFpShotSlice'] = [];
            if (null !== $this->textFpShotSlice && \is_array($this->textFpShotSlice)) {
                $n = 0;
                foreach ($this->textFpShotSlice as $item) {
                    $res['TextFpShotSlice'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return textFpShotSlices
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['TextFpShotSlice'])) {
            if (!empty($map['TextFpShotSlice'])) {
                $model->textFpShotSlice = [];
                $n                      = 0;
                foreach ($map['TextFpShotSlice'] as $item) {
                    $model->textFpShotSlice[$n++] = null !== $item ? textFpShotSlice::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
