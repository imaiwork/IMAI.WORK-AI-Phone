<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\openingList\opening;
use AlibabaCloud\Tea\Model;

class openingList extends Model
{
    /**
     * @var opening[]
     */
    public $opening;
    protected $_name = [
        'opening' => 'Opening',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->opening) {
            $res['Opening'] = [];
            if (null !== $this->opening && \is_array($this->opening)) {
                $n = 0;
                foreach ($this->opening as $item) {
                    $res['Opening'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return openingList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Opening'])) {
            if (!empty($map['Opening'])) {
                $model->opening = [];
                $n              = 0;
                foreach ($map['Opening'] as $item) {
                    $model->opening[$n++] = null !== $item ? opening::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
