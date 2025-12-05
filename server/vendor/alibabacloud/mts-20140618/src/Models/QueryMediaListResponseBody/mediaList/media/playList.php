<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListResponseBody\mediaList\media;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListResponseBody\mediaList\media\playList\play;
use AlibabaCloud\Tea\Model;

class playList extends Model
{
    /**
     * @var play[]
     */
    public $play;
    protected $_name = [
        'play' => 'Play',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->play) {
            $res['Play'] = [];
            if (null !== $this->play && \is_array($this->play)) {
                $n = 0;
                foreach ($this->play as $item) {
                    $res['Play'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return playList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Play'])) {
            if (!empty($map['Play'])) {
                $model->play = [];
                $n           = 0;
                foreach ($map['Play'] as $item) {
                    $model->play[$n++] = null !== $item ? play::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
