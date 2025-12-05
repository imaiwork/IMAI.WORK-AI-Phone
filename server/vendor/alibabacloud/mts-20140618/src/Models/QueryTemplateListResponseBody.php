<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\nonExistTids;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\templateList;
use AlibabaCloud\Tea\Model;

class QueryTemplateListResponseBody extends Model
{
    /**
     * @description The IDs of the templates that do not exist. This parameter is not returned if all specified transcoding templates are found.
     *
     * @var nonExistTids
     */
    public $nonExistTids;

    /**
     * @description The ID of the request.
     *
     * @example BC860F04-778A-472F-AB39-E1BF329C1EA8
     *
     * @var string
     */
    public $requestId;

    /**
     * @description The transcoding templates.
     *
     * @var templateList
     */
    public $templateList;
    protected $_name = [
        'nonExistTids' => 'NonExistTids',
        'requestId'    => 'RequestId',
        'templateList' => 'TemplateList',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->nonExistTids) {
            $res['NonExistTids'] = null !== $this->nonExistTids ? $this->nonExistTids->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }
        if (null !== $this->templateList) {
            $res['TemplateList'] = null !== $this->templateList ? $this->templateList->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryTemplateListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['NonExistTids'])) {
            $model->nonExistTids = nonExistTids::fromMap($map['NonExistTids']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['TemplateList'])) {
            $model->templateList = templateList::fromMap($map['TemplateList']);
        }

        return $model;
    }
}
