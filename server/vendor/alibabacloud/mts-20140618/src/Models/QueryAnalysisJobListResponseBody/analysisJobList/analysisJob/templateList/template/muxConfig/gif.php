<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\templateList\template\muxConfig;

use AlibabaCloud\Tea\Model;

class gif extends Model
{
    /**
     * @description The interval between two consecutive loops for the GIF format. Unit: 0.01 second. For example, a value of 500 indicates 5 seconds.
     *
     * @example 0
     *
     * @var string
     */
    public $finalDelay;

    /**
     * @description The number of loops for the GIF or WebP format. Default value: 0.
     *
     * @example 0
     *
     * @var string
     */
    public $loop;
    protected $_name = [
        'finalDelay' => 'FinalDelay',
        'loop'       => 'Loop',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->finalDelay) {
            $res['FinalDelay'] = $this->finalDelay;
        }
        if (null !== $this->loop) {
            $res['Loop'] = $this->loop;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return gif
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FinalDelay'])) {
            $model->finalDelay = $map['FinalDelay'];
        }
        if (isset($map['Loop'])) {
            $model->loop = $map['Loop'];
        }

        return $model;
    }
}
