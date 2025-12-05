<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowListResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowListResponseBody\mediaWorkflowList\mediaWorkflow;
use AlibabaCloud\Tea\Model;

class mediaWorkflowList extends Model
{
    /**
     * @var mediaWorkflow[]
     */
    public $mediaWorkflow;
    protected $_name = [
        'mediaWorkflow' => 'MediaWorkflow',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaWorkflow) {
            $res['MediaWorkflow'] = [];
            if (null !== $this->mediaWorkflow && \is_array($this->mediaWorkflow)) {
                $n = 0;
                foreach ($this->mediaWorkflow as $item) {
                    $res['MediaWorkflow'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return mediaWorkflowList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaWorkflow'])) {
            if (!empty($map['MediaWorkflow'])) {
                $model->mediaWorkflow = [];
                $n                    = 0;
                foreach ($map['MediaWorkflow'] as $item) {
                    $model->mediaWorkflow[$n++] = null !== $item ? mediaWorkflow::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
