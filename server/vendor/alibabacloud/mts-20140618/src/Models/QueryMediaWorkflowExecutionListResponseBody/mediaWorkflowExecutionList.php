<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody\mediaWorkflowExecutionList\mediaWorkflowExecution;
use AlibabaCloud\Tea\Model;

class mediaWorkflowExecutionList extends Model
{
    /**
     * @var mediaWorkflowExecution[]
     */
    public $mediaWorkflowExecution;
    protected $_name = [
        'mediaWorkflowExecution' => 'MediaWorkflowExecution',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaWorkflowExecution) {
            $res['MediaWorkflowExecution'] = [];
            if (null !== $this->mediaWorkflowExecution && \is_array($this->mediaWorkflowExecution)) {
                $n = 0;
                foreach ($this->mediaWorkflowExecution as $item) {
                    $res['MediaWorkflowExecution'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return mediaWorkflowExecutionList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaWorkflowExecution'])) {
            if (!empty($map['MediaWorkflowExecution'])) {
                $model->mediaWorkflowExecution = [];
                $n                             = 0;
                foreach ($map['MediaWorkflowExecution'] as $item) {
                    $model->mediaWorkflowExecution[$n++] = null !== $item ? mediaWorkflowExecution::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
