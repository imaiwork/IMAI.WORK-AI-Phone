<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\properties\streams;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\properties\streams\subtitleStreamList\subtitleStream;
use AlibabaCloud\Tea\Model;

class subtitleStreamList extends Model
{
    /**
     * @var subtitleStream[]
     */
    public $subtitleStream;
    protected $_name = [
        'subtitleStream' => 'SubtitleStream',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->subtitleStream) {
            $res['SubtitleStream'] = [];
            if (null !== $this->subtitleStream && \is_array($this->subtitleStream)) {
                $n = 0;
                foreach ($this->subtitleStream as $item) {
                    $res['SubtitleStream'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return subtitleStreamList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['SubtitleStream'])) {
            if (!empty($map['SubtitleStream'])) {
                $model->subtitleStream = [];
                $n                     = 0;
                foreach ($map['SubtitleStream'] as $item) {
                    $model->subtitleStream[$n++] = null !== $item ? subtitleStream::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
