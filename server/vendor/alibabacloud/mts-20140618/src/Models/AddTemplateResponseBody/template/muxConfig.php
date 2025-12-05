<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponseBody\template;

use AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponseBody\template\muxConfig\gif;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponseBody\template\muxConfig\segment;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponseBody\template\muxConfig\webp;
use AlibabaCloud\Tea\Model;

class muxConfig extends Model
{
    /**
     * @description The transmuxing settings for GIF.
     *
     * @var gif
     */
    public $gif;

    /**
     * @description The segment settings.
     *
     * @var segment
     */
    public $segment;

    /**
     * @description The transmuxing settings for WebP.
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
