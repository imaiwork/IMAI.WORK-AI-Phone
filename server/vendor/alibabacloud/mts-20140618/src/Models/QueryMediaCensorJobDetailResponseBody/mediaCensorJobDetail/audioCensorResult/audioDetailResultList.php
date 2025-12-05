<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\audioCensorResult;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\audioCensorResult\audioDetailResultList\audioDetailResult;
use AlibabaCloud\Tea\Model;

class audioDetailResultList extends Model
{
    /**
     * @var audioDetailResult[]
     */
    public $audioDetailResult;
    protected $_name = [
        'audioDetailResult' => 'AudioDetailResult',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->audioDetailResult) {
            $res['AudioDetailResult'] = [];
            if (null !== $this->audioDetailResult && \is_array($this->audioDetailResult)) {
                $n = 0;
                foreach ($this->audioDetailResult as $item) {
                    $res['AudioDetailResult'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return audioDetailResultList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AudioDetailResult'])) {
            if (!empty($map['AudioDetailResult'])) {
                $model->audioDetailResult = [];
                $n                        = 0;
                foreach ($map['AudioDetailResult'] as $item) {
                    $model->audioDetailResult[$n++] = null !== $item ? audioDetailResult::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
