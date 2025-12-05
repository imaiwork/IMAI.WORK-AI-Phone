<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults;
use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\textResults;
use AlibabaCloud\Tea\Model;

class ImAuditResponseBody extends Model
{
    /**
     * @description Indicates whether the image moderation QPS exceeds the limit. Valid values: true and false. A value of true indicates that the QPS does not exceed the limit. A value of false indicates that the QPS exceeds the limit.
     *
     * @example false
     *
     * @var bool
     */
    public $imageQuotaExceed;

    /**
     * @description The image moderation results. If the HTTP status code 200 is returned, the array in the returned results contains one or more elements. For more information about the parameters, see [Data returned by the ImAudit operation](https://help.aliyun.com/document_detail/268644.html).
     *
     * @var imageResults
     */
    public $imageResults;

    /**
     * @description The ID of the request.
     *
     * @example 5210DBB0-E327-4D45-ADBC-0B83C8793421
     *
     * @var string
     */
    public $requestId;

    /**
     * @description Indicates whether the text moderation QPS exceeds the limit. Valid values: true and false.
     *
     * @example false
     *
     * @var bool
     */
    public $textQuotaExceed;

    /**
     * @description The text moderation results. If the HTTP status code 200 is returned, the array in the returned results contains one or more elements. For more information about the parameters, see [Data returned by the ImAudit operation](https://help.aliyun.com/document_detail/268644.html).
     *
     * @var textResults
     */
    public $textResults;
    protected $_name = [
        'imageQuotaExceed' => 'ImageQuotaExceed',
        'imageResults'     => 'ImageResults',
        'requestId'        => 'RequestId',
        'textQuotaExceed'  => 'TextQuotaExceed',
        'textResults'      => 'TextResults',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->imageQuotaExceed) {
            $res['ImageQuotaExceed'] = $this->imageQuotaExceed;
        }
        if (null !== $this->imageResults) {
            $res['ImageResults'] = null !== $this->imageResults ? $this->imageResults->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }
        if (null !== $this->textQuotaExceed) {
            $res['TextQuotaExceed'] = $this->textQuotaExceed;
        }
        if (null !== $this->textResults) {
            $res['TextResults'] = null !== $this->textResults ? $this->textResults->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return ImAuditResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['ImageQuotaExceed'])) {
            $model->imageQuotaExceed = $map['ImageQuotaExceed'];
        }
        if (isset($map['ImageResults'])) {
            $model->imageResults = imageResults::fromMap($map['ImageResults']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }
        if (isset($map['TextQuotaExceed'])) {
            $model->textQuotaExceed = $map['TextQuotaExceed'];
        }
        if (isset($map['TextResults'])) {
            $model->textResults = textResults::fromMap($map['TextResults']);
        }

        return $model;
    }
}
