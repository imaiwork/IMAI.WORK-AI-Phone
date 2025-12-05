<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\UpdateWaterMarkTemplateResponseBody\waterMarkTemplate;

use AlibabaCloud\Tea\Model;

class timeline extends Model
{
    /**
     * @description The display duration of the watermark. Default value: **ToEND**. The default value indicates that the watermark is displayed until the video ends.
     *
     * @example 10
     *
     * @var string
     */
    public $duration;

    /**
     * @description The beginning of the time range during which the watermark is displayed.
     *
     *   Unit: seconds.
     *   Default value: **0**.
     *
     * @example 0
     *
     * @var string
     */
    public $start;
    protected $_name = [
        'duration' => 'Duration',
        'start'    => 'Start',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->duration) {
            $res['Duration'] = $this->duration;
        }
        if (null !== $this->start) {
            $res['Start'] = $this->start;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return timeline
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['Start'])) {
            $model->start = $map['Start'];
        }

        return $model;
    }
}
