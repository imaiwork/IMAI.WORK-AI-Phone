<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateWaterMarkTemplateResponseBody\waterMarkTemplate;
use AlibabaCloud\Tea\Model;

class UpdateWaterMarkTemplateResponseBody extends Model
{
    /**
     * @description The ID of the request.
     *
     * @example E558894E-40D9-57C6-B5CC-0F5CDF23614E
     *
     * @var string
     */
    public $requestId;

    /**
     * @description The details of the watermark template.
     *
     * @var waterMarkTemplate
     */
    public $waterMarkTemplate;
    protected $_name = [
        'requestId'         => 'RequestId',
        'waterMarkTemplate' => 'WaterMarkTemplate',
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
        if (null !== $this->waterMarkTemplate) {
            $res['WaterMarkTemplate'] = null !== $this->waterMarkTemplate ? $this->waterMarkTemplate->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return UpdateWaterMarkTemplateResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['WaterMarkTemplate'])) {
            $model->waterMarkTemplate = waterMarkTemplate::fromMap($map['WaterMarkTemplate']);
        }

        return $model;
    }
}
