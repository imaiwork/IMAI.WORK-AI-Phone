<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryPipelineListResponseBody\nonExistPids;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryPipelineListResponseBody\pipelineList;
use AlibabaCloud\Tea\Model;

class QueryPipelineListResponseBody extends Model
{
    /**
     * @description The IDs of MPS queues that do not exist.
     *
     * @var nonExistPids
     */
    public $nonExistPids;

    /**
     * @description The MPS queues.
     *
     * @var pipelineList
     */
    public $pipelineList;

    /**
     * @description The ID of the request.
     *
     * @example 1C538EAA-ACAF-5AD8-B091-A72C63007149
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'nonExistPids' => 'NonExistPids',
        'pipelineList' => 'PipelineList',
        'requestId'    => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->nonExistPids) {
            $res['NonExistPids'] = null !== $this->nonExistPids ? $this->nonExistPids->toMap() : null;
        }
        if (null !== $this->pipelineList) {
            $res['PipelineList'] = null !== $this->pipelineList ? $this->pipelineList->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryPipelineListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['NonExistPids'])) {
            $model->nonExistPids = nonExistPids::fromMap($map['NonExistPids']);
        }
        if (isset($map['PipelineList'])) {
            $model->pipelineList = pipelineList::fromMap($map['PipelineList']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
