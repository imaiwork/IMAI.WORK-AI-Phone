<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\outSubtitleList\outSubtitle;
use AlibabaCloud\Tea\Model;

class outSubtitleList extends Model
{
    /**
     * @var outSubtitle[]
     */
    public $outSubtitle;
    protected $_name = [
        'outSubtitle' => 'OutSubtitle',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->outSubtitle) {
            $res['OutSubtitle'] = [];
            if (null !== $this->outSubtitle && \is_array($this->outSubtitle)) {
                $n = 0;
                foreach ($this->outSubtitle as $item) {
                    $res['OutSubtitle'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return outSubtitleList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['OutSubtitle'])) {
            if (!empty($map['OutSubtitle'])) {
                $model->outSubtitle = [];
                $n                  = 0;
                foreach ($map['OutSubtitle'] as $item) {
                    $model->outSubtitle[$n++] = null !== $item ? outSubtitle::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
