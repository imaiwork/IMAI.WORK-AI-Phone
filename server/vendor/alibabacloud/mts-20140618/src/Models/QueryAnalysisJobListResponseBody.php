<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\nonExistAnalysisJobIds;
use AlibabaCloud\Tea\Model;

class QueryAnalysisJobListResponseBody extends Model
{
    /**
     * @description The IDs of template analysis jobs.
     *
     * @var analysisJobList
     */
    public $analysisJobList;

    /**
     * @description The message sent by Message Service (MNS) to notify the user of the job result.
     *
     * @var nonExistAnalysisJobIds
     */
    public $nonExistAnalysisJobIds;

    /**
     * @description The status of the job. Valid values:
     *
     *   **Submitted**: The job has been submitted.
     *   **Analyzing**: The job is being run.
     *   **Success**: The job is successful.
     *   **Fail**: The job fails.
     *
     * @example 5CA6E020-4102-4FFF-AA56-5ED7ECD811A1
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'analysisJobList'        => 'AnalysisJobList',
        'nonExistAnalysisJobIds' => 'NonExistAnalysisJobIds',
        'requestId'              => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->analysisJobList) {
            $res['AnalysisJobList'] = null !== $this->analysisJobList ? $this->analysisJobList->toMap() : null;
        }
        if (null !== $this->nonExistAnalysisJobIds) {
            $res['NonExistAnalysisJobIds'] = null !== $this->nonExistAnalysisJobIds ? $this->nonExistAnalysisJobIds->toMap() : null;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryAnalysisJobListResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AnalysisJobList'])) {
            $model->analysisJobList = analysisJobList::fromMap($map['AnalysisJobList']);
        }
        if (isset($map['NonExistAnalysisJobIds'])) {
            $model->nonExistAnalysisJobIds = nonExistAnalysisJobIds::fromMap($map['NonExistAnalysisJobIds']);
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
