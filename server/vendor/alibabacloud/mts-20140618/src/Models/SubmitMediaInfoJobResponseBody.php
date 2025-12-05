<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitMediaInfoJobResponseBody\mediaInfoJob;
use AlibabaCloud\Tea\Model;

class SubmitMediaInfoJobResponseBody extends Model
{
    /**
     * @description The details of the media information analysis job.
     *
     * @var mediaInfoJob
     */
    public $mediaInfoJob;

    /**
     * @description The ID of the request.
     *
     * @example 62D9BE16-B7D5-550C-A482-7A0F60E09877
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'mediaInfoJob' => 'MediaInfoJob',
        'requestId'    => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaInfoJob) {
            $res['MediaInfoJob'] = null !== $this->mediaInfoJob ? $this->mediaInfoJob->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SubmitMediaInfoJobResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaInfoJob'])) {
            $model->mediaInfoJob = mediaInfoJob::fromMap($map['MediaInfoJob']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
