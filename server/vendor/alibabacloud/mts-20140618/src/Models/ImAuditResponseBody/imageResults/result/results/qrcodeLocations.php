<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result\results;

use AlibabaCloud\Tea\Model;

class qrcodeLocations extends Model
{
    /**
     * @description The height of the QR code area. Unit: pixel.
     *
     * @example 413.0
     *
     * @var float
     */
    public $h;

    /**
     * @description The URL that the detected QR code points to.
     *
     * @example http://xxx
     *
     * @var string
     */
    public $qrcode;

    /**
     * @description The width of the QR code area. Unit: pixel.
     *
     * @example 402.0
     *
     * @var float
     */
    public $w;

    /**
     * @description The distance between the upper-left corner of the QR code area and the y-axis, with the upper-left corner of the image being the coordinate origin. Unit: pixel.
     *
     * @example 11
     *
     * @var float
     */
    public $x;

    /**
     * @description The distance between the upper-left corner of the QR code area and the x-axis, with the upper-left corner of the image being the coordinate origin. Unit: pixel.
     *
     * @example 0
     *
     * @var float
     */
    public $y;
    protected $_name = [
        'h'      => 'h',
        'qrcode' => 'qrcode',
        'w'      => 'w',
        'x'      => 'x',
        'y'      => 'y',
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
        if (null !== $this->qrcode) {
            $res['qrcode'] = $this->qrcode;
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
     * @return qrcodeLocations
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['h'])) {
            $model->h = $map['h'];
        }
        if (isset($map['qrcode'])) {
            $model->qrcode = $map['qrcode'];
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
