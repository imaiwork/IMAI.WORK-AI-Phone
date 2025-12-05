<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SearchWaterMarkTemplateResponseBody\waterMarkTemplateList;

use AlibabaCloud\SDK\Mts\V20140618\Models\SearchWaterMarkTemplateResponseBody\waterMarkTemplateList\waterMarkTemplate\ratioRefer;
use AlibabaCloud\SDK\Mts\V20140618\Models\SearchWaterMarkTemplateResponseBody\waterMarkTemplateList\waterMarkTemplate\timeline;
use AlibabaCloud\Tea\Model;

class waterMarkTemplate extends Model
{
    /**
     * @description The name of the watermark template.
     *
     * @example 100
     *
     * @var string
     */
    public $dx;

    /**
     * @description The values of the Height, Width, Dx, and Dy parameters relative to the reference edges. If the values of the Height, Width, Dx, and Dy parameters are decimals between 0 and 1, the values are calculated by referring to the following edges in sequence:
     *
     *   **Width**: the width edge.
     *   **Height**: the height edge.
     *   **Long**: the long edge.
     *   **Short**: the short edge.
     *
     * @example 100
     *
     * @var string
     */
    public $dy;

    /**
     * @description The ID of the watermark template.
     *
     * @example 8
     *
     * @var string
     */
    public $height;

    /**
     * @description The vertical offset. Unit: pixel.
     *
     * @example 88c6ca184c0e4578645b665e2a12****
     *
     * @var string
     */
    public $id;

    /**
     * @description The width of the watermark image in the output video. The value can be an integer or a decimal.
     *
     *   **Integer**: the width of the watermark image. This indicates the absolute position. Unit: pixel.
     *   **Decimal**: the ratio of the width of the watermark image to the width of the output video. The ratio varies based on the size of the video. Four decimal places are supported, such as 0.9999. More decimal places are discarded.
     *
     * @example example-watermark
     *
     * @var string
     */
    public $name;

    /**
     * @description The status of the watermark template. Valid values: Valid values:
     *
     *   **Normal**: The watermark template is normal.
     *   **Deleted**: The watermark template is deleted.
     *
     * @var ratioRefer
     */
    public $ratioRefer;

    /**
     * @description The beginning of the time range during which the watermark is displayed.
     *
     *   Unit: seconds.
     *   Default value: **0**.
     *
     * @example TopRight
     *
     * @var string
     */
    public $referPos;

    /**
     * @description The display duration of the watermark. Default value: **ToEND**. The default value indicates that the watermark is displayed until the video ends.
     *
     * @example Normal
     *
     * @var string
     */
    public $state;

    /**
     * @description The timeline of the watermark.
     *
     * @var timeline
     */
    public $timeline;

    /**
     * @description The position of the watermark. Valid values:
     *
     *   TopRight: the upper-right corner.
     *   TopLeft: the upper-left corner.
     *   BottomRight: the lower-right corner.
     *   BottomLeft: the lower-left corner.
     *
     * @example Image
     *
     * @var string
     */
    public $type;

    /**
     * @description The vertical offset. Unit: pixel.
     *
     * @example 8
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'dx'         => 'Dx',
        'dy'         => 'Dy',
        'height'     => 'Height',
        'id'         => 'Id',
        'name'       => 'Name',
        'ratioRefer' => 'RatioRefer',
        'referPos'   => 'ReferPos',
        'state'      => 'State',
        'timeline'   => 'Timeline',
        'type'       => 'Type',
        'width'      => 'Width',
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
        if (null !== $this->id) {
            $res['Id'] = $this->id;
        }
        if (null !== $this->name) {
            $res['Name'] = $this->name;
        }
        if (null !== $this->ratioRefer) {
            $res['RatioRefer'] = null !== $this->ratioRefer ? $this->ratioRefer->toMap() : null;
        }
        if (null !== $this->referPos) {
            $res['ReferPos'] = $this->referPos;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }
        if (null !== $this->timeline) {
            $res['Timeline'] = null !== $this->timeline ? $this->timeline->toMap() : null;
        }
        if (null !== $this->type) {
            $res['Type'] = $this->type;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return waterMarkTemplate
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
        if (isset($map['Id'])) {
            $model->id = $map['Id'];
        }
        if (isset($map['Name'])) {
            $model->name = $map['Name'];
        }
        if (isset($map['RatioRefer'])) {
            $model->ratioRefer = ratioRefer::fromMap($map['RatioRefer']);
        }
        if (isset($map['ReferPos'])) {
            $model->referPos = $map['ReferPos'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }
        if (isset($map['Timeline'])) {
            $model->timeline = timeline::fromMap($map['Timeline']);
        }
        if (isset($map['Type'])) {
            $model->type = $map['Type'];
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }

        return $model;
    }
}
