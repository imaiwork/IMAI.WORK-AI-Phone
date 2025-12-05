<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowListResponseBody;

use AlibabaCloud\Tea\Model;

class nonExistMediaWorkflowIds extends Model
{
    /**
     * @var string[]
     */
    public $mediaWorkflowId;
    protected $_name = [
        'mediaWorkflowId' => 'MediaWorkflowId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaWorkflowId) {
            $res['MediaWorkflowId'] = $this->mediaWorkflowId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return nonExistMediaWorkflowIds
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaWorkflowId'])) {
            if (!empty($map['MediaWorkflowId'])) {
                $model->mediaWorkflowId = $map['MediaWorkflowId'];
            }
        }

        return $model;
    }
}
