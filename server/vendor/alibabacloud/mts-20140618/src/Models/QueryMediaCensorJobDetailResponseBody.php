<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail;
use AlibabaCloud\Tea\Model;

class QueryMediaCensorJobDetailResponseBody extends Model
{
    /**
     * @description The results of the content moderation job.
     *
     * @var mediaCensorJobDetail
     */
    public $mediaCensorJobDetail;

    /**
     * @description The ID of the request.
     *
     * @example B42299E6-F71F-465F-8FE9-4FC2E3D3C2CA
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'mediaCensorJobDetail' => 'MediaCensorJobDetail',
        'requestId'            => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaCensorJobDetail) {
            $res['MediaCensorJobDetail'] = null !== $this->mediaCensorJobDetail ? $this->mediaCensorJobDetail->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryMediaCensorJobDetailResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaCensorJobDetail'])) {
            $model->mediaCensorJobDetail = mediaCensorJobDetail::fromMap($map['MediaCensorJobDetail']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
