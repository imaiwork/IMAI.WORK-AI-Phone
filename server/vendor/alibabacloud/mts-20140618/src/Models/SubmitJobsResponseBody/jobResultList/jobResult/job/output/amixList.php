<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\amixList\amix;
use AlibabaCloud\Tea\Model;

class amixList extends Model
{
    /**
     * @var amix[]
     */
    public $amix;
    protected $_name = [
        'amix' => 'Amix',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->amix) {
            $res['Amix'] = [];
            if (null !== $this->amix && \is_array($this->amix)) {
                $n = 0;
                foreach ($this->amix as $item) {
                    $res['Amix'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return amixList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Amix'])) {
            if (!empty($map['Amix'])) {
                $model->amix = [];
                $n           = 0;
                foreach ($map['Amix'] as $item) {
                    $model->amix[$n++] = null !== $item ? amix::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
