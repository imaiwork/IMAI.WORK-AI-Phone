<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryWaterMarkTemplateListResponseBody\nonExistWids;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryWaterMarkTemplateListResponseBody\waterMarkTemplateList;
use AlibabaCloud\Tea\Model;

class QueryWaterMarkTemplateListResponseBody extends Model
{
    /**
     * @description The IDs of the templates that do not exist.
     *
     * @var nonExistWids
     */
    public $nonExistWids;

    /**
     * @description The ID of the request.
     *
     * @example 17079AF5-6276-51A9-B755-D26594C93F3C
     *
     * @var string
     */
    public $requestId;

    /**
     * @description The details of the watermark templates.
     *
     * @var waterMarkTemplateList
     */
    public $waterMarkTemplateList;
    protected $_name = [
        'nonExistWids'          => 'NonExistWids',
        'requestId'             => 'RequestId',
        'waterMarkTemplateList' => 'WaterMarkTemplateList',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->nonExistWids) {
            $res['NonExistWids'] = null !== $this->nonExistWids ? $this->nonExistWids->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }
        if (null !== $this->waterMarkTemplateList) {
            $res['WaterMarkTemplateList'] = null !== $this->waterMarkTemplateList ? $this->waterMarkTemplateList->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryWaterMarkTemplateListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['NonExistWids'])) {
            $model->nonExistWids = nonExistWids::fromMap($map['NonExistWids']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['WaterMarkTemplateList'])) {
            $model->waterMarkTemplateList = waterMarkTemplateList::fromMap($map['WaterMarkTemplateList']);
        }

        return $model;
    }
}
