<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryWaterMarkTemplateListResponseBody\waterMarkTemplateList\waterMarkTemplate;

use AlibabaCloud\Tea\Model;

class ratioRefer extends Model
{
    /**
     * @description The horizontal offset of the watermark relative to the output video image. Default value: **0**. The default value indicates no offset. The value can be an integer or a decimal.
     *
     *   **Integer**: the vertical offset. This indicates the absolute position. Unit: pixel.
     *   **Decimal**: the ratio of the horizontal offset to the width of the output video. The ratio varies based on the size of the video. Four decimal places are supported, such as 0.9999. More decimal places are discarded.
     *
     * @example 0.51
     *
     * @var string
     */
    public $dx;

    /**
     * @description The vertical offset of the watermark relative to the output video image. Default value: **0**. The default value indicates no offset. The value can be an integer or a decimal.
     *
     *   **Integer**: the vertical offset. This indicates the absolute position. Unit: pixel.
     *   **Decimal**: the ratio of the vertical offset to the height of the output video. The ratio varies based on the size of the video. Four decimal places are supported, such as 0.9999. More decimal places are discarded.
     *
     * @example 0.4
     *
     * @var string
     */
    public $dy;

    /**
     * @description The height of the watermark image in the output video. The value can be an integer or a decimal.
     *
     *   **Integer**: the height of the watermark image. This indicates the absolute position. Unit: pixel.
     *   **Decimal**: the ratio of the height of the watermark image to the height of the output video. The ratio varies based on the size of the video. Four decimal places are supported, such as 0.9999. More decimal places are discarded.
     *
     * @example 0.33
     *
     * @var string
     */
    public $height;

    /**
     * @description The width of the watermark image in the output video. The value can be an integer or a decimal.
     *
     *   **Integer**: the width of the watermark image. This indicates the absolute position. Unit: pixel.
     *   **Decimal**: the ratio of the width of the watermark image to the width of the output video. The ratio varies based on the size of the video. Four decimal places are supported, such as 0.9999. More decimal places are discarded.
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
