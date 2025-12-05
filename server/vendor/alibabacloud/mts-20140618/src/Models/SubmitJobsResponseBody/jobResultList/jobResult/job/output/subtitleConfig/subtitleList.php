<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\subtitleConfig;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\subtitleConfig\subtitleList\subtitle;
use AlibabaCloud\Tea\Model;

class subtitleList extends Model
{
    /**
     * @var subtitle[]
     */
    public $subtitle;
    protected $_name = [
        'subtitle' => 'Subtitle',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->subtitle) {
            $res['Subtitle'] = [];
            if (null !== $this->subtitle && \is_array($this->subtitle)) {
                $n = 0;
                foreach ($this->subtitle as $item) {
                    $res['Subtitle'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return subtitleList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Subtitle'])) {
            if (!empty($map['Subtitle'])) {
                $model->subtitle = [];
                $n               = 0;
                foreach ($map['Subtitle'] as $item) {
                    $model->subtitle[$n++] = null !== $item ? subtitle::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
