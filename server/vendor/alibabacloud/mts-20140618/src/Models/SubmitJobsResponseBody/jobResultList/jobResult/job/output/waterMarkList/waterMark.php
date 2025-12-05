<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\waterMarkList;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\waterMarkList\waterMark\inputFile;
use AlibabaCloud\Tea\Model;

class waterMark extends Model
{
    /**
     * @description The horizontal offset of the watermark image relative to the output video. If this parameter is specified in the request, the corresponding parameter in the watermark template is overwritten. Default value: 0. The value can be an integer or a decimal number.
     *
     *   An integer indicates the pixel value of the horizontal offset.
     *
     *   Valid values: **[8,4096]**.
     *   Unit: pixel.
     *
     *   A decimal number indicates the ratio of the horizontal offset to the width in the output video resolution.
     *
     *   Valid values: (0,1).
     *   The decimal number can be accurate to four decimal places, such as 0.9999. Excess digits are automatically deleted.
     *
     * @example 1
     *
     * @var string
     */
    public $dx;

    /**
     * @description The vertical offset of the watermark image relative to the output video. If this parameter is specified in the request, the corresponding parameter in the watermark template is overwritten. The value can be an integer or a decimal number.
     *
     *   An integer indicates the pixel value of the vertical offset.
     *
     *   Valid values: **[8,4096]**.
     *   Unit: pixel.
     *
     *   A decimal number indicates the ratio of the vertical offset to the height in the output video resolution.
     *
     *   Valid values: **(0,1)**.
     *   The decimal number can be accurate to four decimal places, such as 0.9999. Excess digits are automatically deleted.
     *
     * @example 1
     *
     * @var string
     */
    public $dy;

    /**
     * @description The height of the watermark. If this parameter is specified in the request, the corresponding parameter in the watermark template is overwritten. The value can be an integer or a decimal number.
     *
     *   An integer indicates the pixel value of the watermark height.
     *
     *   Valid values: **[8,4096]**.
     *   Unit: pixel.
     *
     *   A decimal number indicates the ratio of the watermark height to the height in the output video resolution.
     *
     *   Valid values: **(0,1)**.
     *   The decimal number can be accurate to four decimal places, such as 0.9999. Excess digits are automatically deleted.
     *
     * @example 1280
     *
     * @var string
     */
    public $height;

    /**
     * @description The watermark input file. PNG images and MOV files are supported.
     *
     * @var inputFile
     */
    public $inputFile;

    /**
     * @description The position of the watermark. If this parameter is specified in the request, the corresponding parameter in the watermark template is overwritten. Valid values:
     *
     *   **TopRight**
     *   **TopLeft**
     *   **BottomRight**
     *   **BottomLeft**
     *
     * @example TopRight
     *
     * @var string
     */
    public $referPos;

    /**
     * @description The type of the watermark. If this parameter is specified in the request, the corresponding parameter in the watermark template is overwritten. For more information, see [Parameter details](https://help.aliyun.com/document_detail/29253.html). Valid values:
     *
     *   **Image**
     *   **Text**
     *
     * @example Image
     *
     * @var string
     */
    public $type;

    /**
     * @description The ID of the watermark template.
     *
     * @example 88c6ca184c0e47098a5b665e2a12****
     *
     * @var string
     */
    public $waterMarkTemplateId;

    /**
     * @description The width of the watermark image. If this parameter is specified in the request, the corresponding parameter in the watermark template is overwritten. The value can be an integer or a decimal number.
     *
     *   An integer indicates the pixel value of the watermark width.
     *
     *   Valid values: **[8,4096]**.
     *   Unit: pixel.
     *
     *   A decimal number indicates the ratio of the watermark width to the width in the output video resolution.
     *
     *   Valid values: **(0,1)**.
     *   The decimal number can be accurate to four decimal places, such as 0.9999. Excess digits are automatically deleted.
     *
     * @example 1080
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'dx'                  => 'Dx',
        'dy'                  => 'Dy',
        'height'              => 'Height',
        'inputFile'           => 'InputFile',
        'referPos'            => 'ReferPos',
        'type'                => 'Type',
        'waterMarkTemplateId' => 'WaterMarkTemplateId',
        'width'               => 'Width',
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
        if (null !== $this->inputFile) {
            $res['InputFile'] = null !== $this->inputFile ? $this->inputFile->toMap() : null;
        }
        if (null !== $this->referPos) {
            $res['ReferPos'] = $this->referPos;
        }
        if (null !== $this->type) {
            $res['Type'] = $this->type;
        }
        if (null !== $this->waterMarkTemplateId) {
            $res['WaterMarkTemplateId'] = $this->waterMarkTemplateId;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return waterMark
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
        if (isset($map['InputFile'])) {
            $model->inputFile = inputFile::fromMap($map['InputFile']);
        }
        if (isset($map['ReferPos'])) {
            $model->referPos = $map['ReferPos'];
        }
        if (isset($map['Type'])) {
            $model->type = $map['Type'];
        }
        if (isset($map['WaterMarkTemplateId'])) {
            $model->waterMarkTemplateId = $map['WaterMarkTemplateId'];
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }

        return $model;
    }
}
