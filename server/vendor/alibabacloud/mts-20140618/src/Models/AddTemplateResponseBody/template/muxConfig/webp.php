<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponseBody\template\muxConfig;

use AlibabaCloud\Tea\Model;

class webp extends Model
{
    /**
     * @description The loop count.
     *
     * @example 0
     *
     * @var string
     */
    public $loop;
    protected $_name = [
        'loop' => 'Loop',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->loop) {
            $res['Loop'] = $this->loop;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return webp
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Loop'])) {
            $model->loop = $map['Loop'];
        }

        return $model;
    }
}
