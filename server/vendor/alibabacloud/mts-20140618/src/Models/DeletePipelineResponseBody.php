<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class DeletePipelineResponseBody extends Model
{
    /**
     * @description The ID of the MPS queue that is deleted.
     *
     * @example d1ce4d3efcb549419193f50f1fcd****
     *
     * @var string
     */
    public $pipelineId;

    /**
     * @description The ID of the request.
     *
     * @example 338CA33A-AE83-5DF4-B6F2-C6D3ED8143F5
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'pipelineId' => 'PipelineId',
        'requestId'  => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->pipelineId) {
            $res['PipelineId'] = $this->pipelineId;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return DeletePipelineResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['PipelineId'])) {
            $model->pipelineId = $map['PipelineId'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
