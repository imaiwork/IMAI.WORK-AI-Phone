<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomGroupsResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomGroupsResponseBody\customGroups\customGroup;
use AlibabaCloud\Tea\Model;

class customGroups extends Model
{
    /**
     * @var customGroup[]
     */
    public $customGroup;
    protected $_name = [
        'customGroup' => 'CustomGroup',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->customGroup) {
            $res['CustomGroup'] = [];
            if (null !== $this->customGroup && \is_array($this->customGroup)) {
                $n = 0;
                foreach ($this->customGroup as $item) {
                    $res['CustomGroup'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return customGroups
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CustomGroup'])) {
            if (!empty($map['CustomGroup'])) {
                $model->customGroup = [];
                $n                  = 0;
                foreach ($map['CustomGroup'] as $item) {
                    $model->customGroup[$n++] = null !== $item ? customGroup::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
