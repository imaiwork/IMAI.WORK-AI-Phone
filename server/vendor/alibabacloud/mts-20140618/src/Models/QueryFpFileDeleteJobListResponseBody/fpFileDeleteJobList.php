<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpFileDeleteJobListResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpFileDeleteJobListResponseBody\fpFileDeleteJobList\fpFileDeleteJob;
use AlibabaCloud\Tea\Model;

class fpFileDeleteJobList extends Model
{
    /**
     * @var fpFileDeleteJob[]
     */
    public $fpFileDeleteJob;
    protected $_name = [
        'fpFileDeleteJob' => 'FpFileDeleteJob',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpFileDeleteJob) {
            $res['FpFileDeleteJob'] = [];
            if (null !== $this->fpFileDeleteJob && \is_array($this->fpFileDeleteJob)) {
                $n = 0;
                foreach ($this->fpFileDeleteJob as $item) {
                    $res['FpFileDeleteJob'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpFileDeleteJobList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpFileDeleteJob'])) {
            if (!empty($map['FpFileDeleteJob'])) {
                $model->fpFileDeleteJob = [];
                $n                      = 0;
                foreach ($map['FpFileDeleteJob'] as $item) {
                    $model->fpFileDeleteJob[$n++] = null !== $item ? fpFileDeleteJob::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
