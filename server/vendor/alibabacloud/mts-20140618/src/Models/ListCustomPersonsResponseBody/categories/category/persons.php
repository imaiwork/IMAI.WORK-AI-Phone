<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomPersonsResponseBody\categories\category;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomPersonsResponseBody\categories\category\persons\person;
use AlibabaCloud\Tea\Model;

class persons extends Model
{
    /**
     * @var person[]
     */
    public $person;
    protected $_name = [
        'person' => 'Person',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->person) {
            $res['Person'] = [];
            if (null !== $this->person && \is_array($this->person)) {
                $n = 0;
                foreach ($this->person as $item) {
                    $res['Person'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return persons
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Person'])) {
            if (!empty($map['Person'])) {
                $model->person = [];
                $n             = 0;
                foreach ($map['Person'] as $item) {
                    $model->person[$n++] = null !== $item ? person::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
