<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SearchWaterMarkTemplateResponseBody\waterMarkTemplateList\waterMarkTemplate;

use AlibabaCloud\Tea\Model;

class ratioRefer extends Model
{
    /**
     * @description The horizontal offset. Unit: pixel.
     *
     * @example 0.51
     *
     * @var string
     */
    public $dx;

    /**
     * @description The timeline of the watermark.
     *
     * @example 0.2
     *
     * @var string
     */
    public $dy;

    /**
     * @description The height of the watermark image. Unit: pixel.
     *
     * @example 0.33
     *
     * @var string
     */
    public $height;

    /**
     * @description The width of the watermark image. Unit: pixel.
     *
     * @example 0.36
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'dx'     => 'Dx',
        'dy'     => 'Dy',
        'height' => 'Height',
        'width'  => 'Width',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->dx) {
            $res['Dx'] = $this->dx;
        }
        if (null !== $this->dy) {
            $res['Dy'] = $this->dy;
        }
        if (null !== $this->height) {
            $res['Height'] = $this->height;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return ratioRefer
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Dx'])) {
            $model->dx = $map['Dx'];
        }
        if (isset($map['Dy'])) {
            $model->dy = $map['Dy'];
        }
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }

        return $model;
    }
}
