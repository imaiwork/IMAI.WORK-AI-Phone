<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\subtitleConfig;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\subtitleConfig\extSubtitleList\extSubtitle;
use AlibabaCloud\Tea\Model;

class extSubtitleList extends Model
{
    /**
     * @var extSubtitle[]
     */
    public $extSubtitle;
    protected $_name = [
        'extSubtitle' => 'ExtSubtitle',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->extSubtitle) {
            $res['ExtSubtitle'] = [];
            if (null !== $this->extSubtitle && \is_array($this->extSubtitle)) {
                $n = 0;
                foreach ($this->extSubtitle as $item) {
                    $res['ExtSubtitle'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return extSubtitleList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['ExtSubtitle'])) {
            if (!empty($map['ExtSubtitle'])) {
                $model->extSubtitle = [];
                $n                  = 0;
                foreach ($map['ExtSubtitle'] as $item) {
                    $model->extSubtitle[$n++] = null !== $item ? extSubtitle::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
