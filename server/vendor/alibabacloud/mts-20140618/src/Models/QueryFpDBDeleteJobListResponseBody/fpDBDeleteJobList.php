<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpDBDeleteJobListResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpDBDeleteJobListResponseBody\fpDBDeleteJobList\fpDBDeleteJob;
use AlibabaCloud\Tea\Model;

class fpDBDeleteJobList extends Model
{
    /**
     * @var fpDBDeleteJob[]
     */
    public $fpDBDeleteJob;
    protected $_name = [
        'fpDBDeleteJob' => 'FpDBDeleteJob',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpDBDeleteJob) {
            $res['FpDBDeleteJob'] = [];
            if (null !== $this->fpDBDeleteJob && \is_array($this->fpDBDeleteJob)) {
                $n = 0;
                foreach ($this->fpDBDeleteJob as $item) {
                    $res['FpDBDeleteJob'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpDBDeleteJobList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpDBDeleteJob'])) {
            if (!empty($map['FpDBDeleteJob'])) {
                $model->fpDBDeleteJob = [];
                $n                    = 0;
                foreach ($map['FpDBDeleteJob'] as $item) {
                    $model->fpDBDeleteJob[$n++] = null !== $item ? fpDBDeleteJob::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
