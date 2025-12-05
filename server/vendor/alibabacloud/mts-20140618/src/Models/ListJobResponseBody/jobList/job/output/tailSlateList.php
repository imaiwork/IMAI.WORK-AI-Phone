<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\tailSlateList\tailSlate;
use AlibabaCloud\Tea\Model;

class tailSlateList extends Model
{
    /**
     * @var tailSlate[]
     */
    public $tailSlate;
    protected $_name = [
        'tailSlate' => 'TailSlate',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->tailSlate) {
            $res['TailSlate'] = [];
            if (null !== $this->tailSlate && \is_array($this->tailSlate)) {
                $n = 0;
                foreach ($this->tailSlate as $item) {
                    $res['TailSlate'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return tailSlateList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['TailSlate'])) {
            if (!empty($map['TailSlate'])) {
                $model->tailSlate = [];
                $n                = 0;
                foreach ($map['TailSlate'] as $item) {
                    $model->tailSlate[$n++] = null !== $item ? tailSlate::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
