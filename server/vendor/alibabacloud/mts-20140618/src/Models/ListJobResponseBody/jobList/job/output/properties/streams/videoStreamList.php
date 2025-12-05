<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\properties\streams;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\properties\streams\videoStreamList\videoStream;
use AlibabaCloud\Tea\Model;

class videoStreamList extends Model
{
    /**
     * @var videoStream[]
     */
    public $videoStream;
    protected $_name = [
        'videoStream' => 'VideoStream',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->videoStream) {
            $res['VideoStream'] = [];
            if (null !== $this->videoStream && \is_array($this->videoStream)) {
                $n = 0;
                foreach ($this->videoStream as $item) {
                    $res['VideoStream'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return videoStreamList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['VideoStream'])) {
            if (!empty($map['VideoStream'])) {
                $model->videoStream = [];
                $n                  = 0;
                foreach ($map['VideoStream'] as $item) {
                    $model->videoStream[$n++] = null !== $item ? videoStream::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
