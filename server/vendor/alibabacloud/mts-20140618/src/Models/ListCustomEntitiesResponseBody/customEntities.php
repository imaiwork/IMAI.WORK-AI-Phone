<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomEntitiesResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomEntitiesResponseBody\customEntities\customEntity;
use AlibabaCloud\Tea\Model;

class customEntities extends Model
{
    /**
     * @var customEntity[]
     */
    public $customEntity;
    protected $_name = [
        'customEntity' => 'CustomEntity',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->customEntity) {
            $res['CustomEntity'] = [];
            if (null !== $this->customEntity && \is_array($this->customEntity)) {
                $n = 0;
                foreach ($this->customEntity as $item) {
                    $res['CustomEntity'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return customEntities
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CustomEntity'])) {
            if (!empty($map['CustomEntity'])) {
                $model->customEntity = [];
                $n                   = 0;
                foreach ($map['CustomEntity'] as $item) {
                    $model->customEntity[$n++] = null !== $item ? customEntity::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
