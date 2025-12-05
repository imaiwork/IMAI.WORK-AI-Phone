<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotDBResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotDBResponseBody\fpShotDBList\fpShotDB;
use AlibabaCloud\Tea\Model;

class fpShotDBList extends Model
{
    /**
     * @var fpShotDB[]
     */
    public $fpShotDB;
    protected $_name = [
        'fpShotDB' => 'FpShotDB',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpShotDB) {
            $res['FpShotDB'] = [];
            if (null !== $this->fpShotDB && \is_array($this->fpShotDB)) {
                $n = 0;
                foreach ($this->fpShotDB as $item) {
                    $res['FpShotDB'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpShotDBList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpShotDB'])) {
            if (!empty($map['FpShotDB'])) {
                $model->fpShotDB = [];
                $n               = 0;
                foreach ($map['FpShotDB'] as $item) {
                    $model->fpShotDB[$n++] = null !== $item ? fpShotDB::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
