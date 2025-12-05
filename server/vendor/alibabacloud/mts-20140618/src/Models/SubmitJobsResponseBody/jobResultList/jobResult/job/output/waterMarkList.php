<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\waterMarkList\waterMark;
use AlibabaCloud\Tea\Model;

class waterMarkList extends Model
{
    /**
     * @var waterMark[]
     */
    public $waterMark;
    protected $_name = [
        'waterMark' => 'WaterMark',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->waterMark) {
            $res['WaterMark'] = [];
            if (null !== $this->waterMark && \is_array($this->waterMark)) {
                $n = 0;
                foreach ($this->waterMark as $item) {
                    $res['WaterMark'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return waterMarkList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['WaterMark'])) {
            if (!empty($map['WaterMark'])) {
                $model->waterMark = [];
                $n                = 0;
                foreach ($map['WaterMark'] as $item) {
                    $model->waterMark[$n++] = null !== $item ? waterMark::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
