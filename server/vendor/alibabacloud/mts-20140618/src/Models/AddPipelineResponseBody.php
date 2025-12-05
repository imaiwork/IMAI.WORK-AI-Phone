<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\AddPipelineResponseBody\pipeline;
use AlibabaCloud\Tea\Model;

class AddPipelineResponseBody extends Model
{
    /**
     * @description The MPS queue.
     *
     * @var pipeline
     */
    public $pipeline;

    /**
     * @description The ID of the request.
     *
     * @example CFEA608A-5A1C-4C83-A54B-6197BC250D23
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'pipeline'  => 'Pipeline',
        'requestId' => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->pipeline) {
            $res['Pipeline'] = null !== $this->pipeline ? $this->pipeline->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return AddPipelineResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Pipeline'])) {
            $model->pipeline = pipeline::fromMap($map['Pipeline']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
