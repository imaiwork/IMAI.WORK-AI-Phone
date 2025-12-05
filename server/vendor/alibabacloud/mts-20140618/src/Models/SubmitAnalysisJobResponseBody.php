<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitAnalysisJobResponseBody\analysisJob;
use AlibabaCloud\Tea\Model;

class SubmitAnalysisJobResponseBody extends Model
{
    /**
     * @description The information about the preset template analysis job that was submitted.
     *
     * @var analysisJob
     */
    public $analysisJob;

    /**
     * @description The ID of the request.
     *
     * @example B52658D4-07AB-43CD-82B0-210958A65E23
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'analysisJob' => 'AnalysisJob',
        'requestId'   => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->analysisJob) {
            $res['AnalysisJob'] = null !== $this->analysisJob ? $this->analysisJob->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SubmitAnalysisJobResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AnalysisJob'])) {
            $model->analysisJob = analysisJob::fromMap($map['AnalysisJob']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
