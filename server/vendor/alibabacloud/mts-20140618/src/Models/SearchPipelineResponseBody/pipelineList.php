<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SearchPipelineResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\SearchPipelineResponseBody\pipelineList\pipeline;
use AlibabaCloud\Tea\Model;

class pipelineList extends Model
{
    /**
     * @var pipeline[]
     */
    public $pipeline;
    protected $_name = [
        'pipeline' => 'Pipeline',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->pipeline) {
            $res['Pipeline'] = [];
            if (null !== $this->pipeline && \is_array($this->pipeline)) {
                $n = 0;
                foreach ($this->pipeline as $item) {
                    $res['Pipeline'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return pipelineList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Pipeline'])) {
            if (!empty($map['Pipeline'])) {
                $model->pipeline = [];
                $n               = 0;
                foreach ($map['Pipeline'] as $item) {
                    $model->pipeline[$n++] = null !== $item ? pipeline::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
