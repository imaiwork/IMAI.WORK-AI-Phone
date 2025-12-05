<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\vensorCensorResult;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\vensorCensorResult\videoTimelines\videoTimeline;
use AlibabaCloud\Tea\Model;

class videoTimelines extends Model
{
    /**
     * @var videoTimeline[]
     */
    public $videoTimeline;
    protected $_name = [
        'videoTimeline' => 'VideoTimeline',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->videoTimeline) {
            $res['VideoTimeline'] = [];
            if (null !== $this->videoTimeline && \is_array($this->videoTimeline)) {
                $n = 0;
                foreach ($this->videoTimeline as $item) {
                    $res['VideoTimeline'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return videoTimelines
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['VideoTimeline'])) {
            if (!empty($map['VideoTimeline'])) {
                $model->videoTimeline = [];
                $n                    = 0;
                foreach ($map['VideoTimeline'] as $item) {
                    $model->videoTimeline[$n++] = null !== $item ? videoTimeline::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
