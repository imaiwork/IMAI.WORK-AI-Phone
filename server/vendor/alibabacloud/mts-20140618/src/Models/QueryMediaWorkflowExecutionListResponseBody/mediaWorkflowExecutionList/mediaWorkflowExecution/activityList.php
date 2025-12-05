<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody\mediaWorkflowExecutionList\mediaWorkflowExecution;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody\mediaWorkflowExecutionList\mediaWorkflowExecution\activityList\activity;
use AlibabaCloud\Tea\Model;

class activityList extends Model
{
    /**
     * @var activity[]
     */
    public $activity;
    protected $_name = [
        'activity' => 'Activity',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->activity) {
            $res['Activity'] = [];
            if (null !== $this->activity && \is_array($this->activity)) {
                $n = 0;
                foreach ($this->activity as $item) {
                    $res['Activity'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return activityList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Activity'])) {
            if (!empty($map['Activity'])) {
                $model->activity = [];
                $n               = 0;
                foreach ($map['Activity'] as $item) {
                    $model->activity[$n++] = null !== $item ? activity::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
