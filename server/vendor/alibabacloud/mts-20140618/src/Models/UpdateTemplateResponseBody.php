<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template;
use AlibabaCloud\Tea\Model;

class UpdateTemplateResponseBody extends Model
{
    /**
     * @description The type of the transcoding template.
     *
     * @example 5E4FB22E-B9EA-4E24-8FFC-B407EA71QW21
     *
     * @var string
     */
    public $requestId;

    /**
     * @description The type of the transcoding template.
     *
     * @var template
     */
    public $template;
    protected $_name = [
        'requestId' => 'RequestId',
        'template'  => 'Template',
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
        if (null !== $this->template) {
            $res['Template'] = null !== $this->template ? $this->template->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return UpdateTemplateResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['Template'])) {
            $model->template = template::fromMap($map['Template']);
        }

        return $model;
    }
}
