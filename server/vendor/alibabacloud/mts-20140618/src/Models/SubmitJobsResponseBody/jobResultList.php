<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult;
use AlibabaCloud\Tea\Model;

class jobResultList extends Model
{
    /**
     * @var jobResult[]
     */
    public $jobResult;
    protected $_name = [
        'jobResult' => 'JobResult',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->jobResult) {
            $res['JobResult'] = [];
            if (null !== $this->jobResult && \is_array($this->jobResult)) {
                $n = 0;
                foreach ($this->jobResult as $item) {
                    $res['JobResult'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return jobResultList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['JobResult'])) {
            if (!empty($map['JobResult'])) {
                $model->jobResult = [];
                $n                = 0;
                foreach ($map['JobResult'] as $item) {
                    $model->jobResult[$n++] = null !== $item ? jobResult::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
