<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class DeleteWaterMarkTemplateResponseBody extends Model
{
    /**
     * @description The ID of the request.
     *
     * @example 030E2671-806A-52AF-A93C-DA8E308603A6
     *
     * @var string
     */
    public $requestId;

    /**
     * @description The ID of the deleted watermark template.
     *
     * @example 3780bd69b2b74540bc7b1096f564****
     *
     * @var string
     */
    public $waterMarkTemplateId;
    protected $_name = [
        'requestId'           => 'RequestId',
        'waterMarkTemplateId' => 'WaterMarkTemplateId',
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
        if (null !== $this->waterMarkTemplateId) {
            $res['WaterMarkTemplateId'] = $this->waterMarkTemplateId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return DeleteWaterMarkTemplateResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['WaterMarkTemplateId'])) {
            $model->waterMarkTemplateId = $map['WaterMarkTemplateId'];
        }

        return $model;
    }
}
