<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SearchWaterMarkTemplateResponseBody\waterMarkTemplateList\waterMarkTemplate;

use AlibabaCloud\Tea\Model;

class timeline extends Model
{
    /**
     * @description The horizontal offset of the watermark relative to the output video image. Default value: **0**. The default value indicates no offset.
     *
     * The value can be an integer or a decimal.
     *
     *   **Integer**: the vertical offset. This indicates the absolute position. Unit: pixel.
     *   **Decimal**: the ratio of the horizontal offset to the width of the output video. The ratio varies based on the size of the video. Four decimal places are supported, such as 0.9999. More decimal places are discarded.
     *
     * @example ToEND
     *
     * @var string
     */
    public $duration;

    /**
     * @description The total number of returned entries.
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
