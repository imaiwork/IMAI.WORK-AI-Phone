<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\mergeList\merge;
use AlibabaCloud\Tea\Model;

class mergeList extends Model
{
    /**
     * @var merge[]
     */
    public $merge;
    protected $_name = [
        'merge' => 'Merge',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->merge) {
            $res['Merge'] = [];
            if (null !== $this->merge && \is_array($this->merge)) {
                $n = 0;
                foreach ($this->merge as $item) {
                    $res['Merge'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return mergeList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Merge'])) {
            if (!empty($map['Merge'])) {
                $model->merge = [];
                $n            = 0;
                foreach ($map['Merge'] as $item) {
                    $model->merge[$n++] = null !== $item ? merge::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
