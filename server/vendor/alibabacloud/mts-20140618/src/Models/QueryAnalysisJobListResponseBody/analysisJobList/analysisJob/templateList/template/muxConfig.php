<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\templateList\template;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\templateList\template\muxConfig\gif;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\templateList\template\muxConfig\segment;
use AlibabaCloud\Tea\Model;

class muxConfig extends Model
{
    /**
     * @description The transmuxing configurations for the GIF format.
     *
     * @var gif
     */
    public $gif;

    /**
     * @description The segment configurations.
     *
     * @var segment
     */
    public $segment;
    protected $_name = [
        'gif'     => 'Gif',
        'segment' => 'Segment',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->gif) {
            $res['Gif'] = null !== $this->gif ? $this->gif->toMap() : null;
        }
        if (null !== $this->segment) {
            $res['Segment'] = null !== $this->segment ? $this->segment->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return muxConfig
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Gif'])) {
            $model->gif = gif::fromMap($map['Gif']);
        }
        if (isset($map['Segment'])) {
            $model->segment = segment::fromMap($map['Segment']);
        }

        return $model;
    }
}
