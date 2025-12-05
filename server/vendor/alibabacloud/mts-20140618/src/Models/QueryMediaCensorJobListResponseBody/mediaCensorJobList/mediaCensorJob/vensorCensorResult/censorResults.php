<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobListResponseBody\mediaCensorJobList\mediaCensorJob\vensorCensorResult;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobListResponseBody\mediaCensorJobList\mediaCensorJob\vensorCensorResult\censorResults\censorResult;
use AlibabaCloud\Tea\Model;

class censorResults extends Model
{
    /**
     * @var censorResult[]
     */
    public $censorResult;
    protected $_name = [
        'censorResult' => 'CensorResult',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->censorResult) {
            $res['CensorResult'] = [];
            if (null !== $this->censorResult && \is_array($this->censorResult)) {
                $n = 0;
                foreach ($this->censorResult as $item) {
                    $res['CensorResult'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return censorResults
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CensorResult'])) {
            if (!empty($map['CensorResult'])) {
                $model->censorResult = [];
                $n                   = 0;
                foreach ($map['CensorResult'] as $item) {
                    $model->censorResult[$n++] = null !== $item ? censorResult::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
