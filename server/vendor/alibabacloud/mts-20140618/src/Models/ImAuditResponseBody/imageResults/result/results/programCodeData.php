<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result\results;

use AlibabaCloud\Tea\Model;

class programCodeData extends Model
{
    /**
     * @description The height of the mini program code area. Unit: pixel.
     *
     * @example 413.0
     *
     * @var float
     */
    public $h;

    /**
     * @description The width of the mini program code area. Unit: pixel.
     *
     * @example 402.0
     *
     * @var float
     */
    public $w;

    /**
     * @description The distance between the upper-left corner of the mini program code area and the y-axis, with the upper-left corner of the image being the coordinate origin. Unit: pixel.
     *
     * @example 11.0
     *
     * @var float
     */
    public $x;

    /**
     * @description The distance between the upper-left corner of the mini program code area and the x-axis, with the upper-left corner of the image being the coordinate origin. Unit: pixel.
     *
     * @example 0.0
     *
     * @var float
     */
    public $y;
    protected $_name = [
        'h' => 'h',
        'w' => 'w',
        'x' => 'x',
        'y' => 'y',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->h) {
            $res['h'] = $this->h;
        }
        if (null !== $this->w) {
            $res['w'] = $this->w;
        }
        if (null !== $this->x) {
            $res['x'] = $this->x;
        }
        if (null !== $this->y) {
            $res['y'] = $this->y;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return programCodeData
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['h'])) {
            $model->h = $map['h'];
        }
        if (isset($map['w'])) {
            $model->w = $map['w'];
        }
        if (isset($map['x'])) {
            $model->x = $map['x'];
        }
        if (isset($map['y'])) {
            $model->y = $map['y'];
        }

        return $model;
    }
}
