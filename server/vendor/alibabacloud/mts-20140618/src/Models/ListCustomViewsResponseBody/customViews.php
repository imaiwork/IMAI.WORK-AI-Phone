<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomViewsResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListCustomViewsResponseBody\customViews\customView;
use AlibabaCloud\Tea\Model;

class customViews extends Model
{
    /**
     * @var customView[]
     */
    public $customView;
    protected $_name = [
        'customView' => 'CustomView',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->customView) {
            $res['CustomView'] = [];
            if (null !== $this->customView && \is_array($this->customView)) {
                $n = 0;
                foreach ($this->customView as $item) {
                    $res['CustomView'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return customViews
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CustomView'])) {
            if (!empty($map['CustomView'])) {
                $model->customView = [];
                $n                 = 0;
                foreach ($map['CustomView'] as $item) {
                    $model->customView[$n++] = null !== $item ? customView::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
