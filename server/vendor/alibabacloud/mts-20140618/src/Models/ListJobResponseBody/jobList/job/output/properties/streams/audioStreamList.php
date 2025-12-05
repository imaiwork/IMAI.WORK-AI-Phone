<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\properties\streams;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\properties\streams\audioStreamList\audioStream;
use AlibabaCloud\Tea\Model;

class audioStreamList extends Model
{
    /**
     * @var audioStream[]
     */
    public $audioStream;
    protected $_name = [
        'audioStream' => 'AudioStream',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->audioStream) {
            $res['AudioStream'] = [];
            if (null !== $this->audioStream && \is_array($this->audioStream)) {
                $n = 0;
                foreach ($this->audioStream as $item) {
                    $res['AudioStream'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return audioStreamList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AudioStream'])) {
            if (!empty($map['AudioStream'])) {
                $model->audioStream = [];
                $n                  = 0;
                foreach ($map['AudioStream'] as $item) {
                    $model->audioStream[$n++] = null !== $item ? audioStream::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
