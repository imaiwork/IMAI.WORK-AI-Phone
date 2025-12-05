<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template;

use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template\muxConfig\gif;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template\muxConfig\segment;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template\muxConfig\webp;
use AlibabaCloud\Tea\Model;

class muxConfig extends Model
{
    /**
     * @description The duration for which the final frame is paused. Unit: milliseconds.
     *
     * @var gif
     */
    public $gif;

    /**
     * @description The length of the segment. Unit: seconds.
     *
     * @var segment
     */
    public $segment;

    /**
     * @description The loop count.
     *
     * @var webp
     */
    public $webp;
    protected $_name = [
        'gif'     => 'Gif',
        'segment' => 'Segment',
        'webp'    => 'Webp',
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
        if (null !== $this->webp) {
            $res['Webp'] = null !== $this->webp ? $this->webp->toMap() : null;
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
        if (isset($map['Webp'])) {
            $model->webp = webp::fromMap($map['Webp']);
        }

        return $model;
    }
}
