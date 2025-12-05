<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob;
use AlibabaCloud\Tea\Model;

class analysisJobList extends Model
{
    /**
     * @var analysisJob[]
     */
    public $analysisJob;
    protected $_name = [
        'analysisJob' => 'AnalysisJob',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->analysisJob) {
            $res['AnalysisJob'] = [];
            if (null !== $this->analysisJob && \is_array($this->analysisJob)) {
                $n = 0;
                foreach ($this->analysisJob as $item) {
                    $res['AnalysisJob'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return analysisJobList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AnalysisJob'])) {
            if (!empty($map['AnalysisJob'])) {
                $model->analysisJob = [];
                $n                  = 0;
                foreach ($map['AnalysisJob'] as $item) {
                    $model->analysisJob[$n++] = null !== $item ? analysisJob::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
