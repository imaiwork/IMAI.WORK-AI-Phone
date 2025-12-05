<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySmarttagTemplateListResponseBody\templates;
use AlibabaCloud\Tea\Model;

class QuerySmarttagTemplateListResponseBody extends Model
{
    /**
     * @description The ID of the request.
     *
     * @example 5210DBB0-E327-4D45-ADBC-0B83C8796E26
     *
     * @var string
     */
    public $requestId;

    /**
     * @description The templates.
     *
     * @var templates
     */
    public $templates;
    protected $_name = [
        'requestId' => 'RequestId',
        'templates' => 'Templates',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }
        if (null !== $this->templates) {
            $res['Templates'] = null !== $this->templates ? $this->templates->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QuerySmarttagTemplateListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['Templates'])) {
            $model->templates = templates::fromMap($map['Templates']);
        }

        return $model;
    }
}
