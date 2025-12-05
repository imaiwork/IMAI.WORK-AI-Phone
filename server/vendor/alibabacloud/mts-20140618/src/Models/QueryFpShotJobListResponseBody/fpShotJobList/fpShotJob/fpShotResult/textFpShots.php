<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\textFpShots\textFpShot;
use AlibabaCloud\Tea\Model;

class textFpShots extends Model
{
    /**
     * @var textFpShot[]
     */
    public $textFpShot;
    protected $_name = [
        'textFpShot' => 'TextFpShot',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->textFpShot) {
            $res['TextFpShot'] = [];
            if (null !== $this->textFpShot && \is_array($this->textFpShot)) {
                $n = 0;
                foreach ($this->textFpShot as $item) {
                    $res['TextFpShot'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return textFpShots
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['TextFpShot'])) {
            if (!empty($map['TextFpShot'])) {
                $model->textFpShot = [];
                $n                 = 0;
                foreach ($map['TextFpShot'] as $item) {
                    $model->textFpShot[$n++] = null !== $item ? textFpShot::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
