<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob;
use AlibabaCloud\Tea\Model;

class fpShotJobList extends Model
{
    /**
     * @var fpShotJob[]
     */
    public $fpShotJob;
    protected $_name = [
        'fpShotJob' => 'FpShotJob',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpShotJob) {
            $res['FpShotJob'] = [];
            if (null !== $this->fpShotJob && \is_array($this->fpShotJob)) {
                $n = 0;
                foreach ($this->fpShotJob as $item) {
                    $res['FpShotJob'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpShotJobList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpShotJob'])) {
            if (!empty($map['FpShotJob'])) {
                $model->fpShotJob = [];
                $n                = 0;
                foreach ($map['FpShotJob'] as $item) {
                    $model->fpShotJob[$n++] = null !== $item ? fpShotJob::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
