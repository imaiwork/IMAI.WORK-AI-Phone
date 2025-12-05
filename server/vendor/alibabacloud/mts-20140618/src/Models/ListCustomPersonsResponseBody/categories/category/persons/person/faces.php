<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomPersonsResponseBody\categories\category\persons\person;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomPersonsResponseBody\categories\category\persons\person\faces\face;
use AlibabaCloud\Tea\Model;

class faces extends Model
{
    /**
     * @var face[]
     */
    public $face;
    protected $_name = [
        'face' => 'Face',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->face) {
            $res['Face'] = [];
            if (null !== $this->face && \is_array($this->face)) {
                $n = 0;
                foreach ($this->face as $item) {
                    $res['Face'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return faces
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Face'])) {
            if (!empty($map['Face'])) {
                $model->face = [];
                $n           = 0;
                foreach ($map['Face'] as $item) {
                    $model->face[$n++] = null !== $item ? face::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
