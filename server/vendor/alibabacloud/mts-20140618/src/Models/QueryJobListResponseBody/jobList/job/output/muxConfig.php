<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\muxConfig\gif;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\muxConfig\segment;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\muxConfig\webp;
use AlibabaCloud\Tea\Model;

class muxConfig extends Model
{
    /**
     * @description The transmuxing configurations for GIF.
     *
     * @var gif
     */
    public $gif;

    /**
     * @description The segment configurations. The value is a JSON object.
     *
     * @var segment
     */
    public $segment;

    /**
     * @description The transmuxing configurations for WebP.
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
