<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody;

use AlibabaCloud\Tea\Model;

class nonExistRunIds extends Model
{
    /**
     * @var string[]
     */
    public $runId;
    protected $_name = [
        'runId' => 'RunId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->runId) {
            $res['RunId'] = $this->runId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return nonExistRunIds
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['RunId'])) {
            if (!empty($map['RunId'])) {
                $model->runId = $map['RunId'];
            }
        }

        return $model;
    }
}
