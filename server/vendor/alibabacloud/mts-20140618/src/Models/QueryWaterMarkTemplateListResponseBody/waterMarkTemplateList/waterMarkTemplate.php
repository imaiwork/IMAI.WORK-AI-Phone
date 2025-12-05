<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryWaterMarkTemplateListResponseBody\waterMarkTemplateList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryWaterMarkTemplateListResponseBody\waterMarkTemplateList\waterMarkTemplate\ratioRefer;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryWaterMarkTemplateListResponseBody\waterMarkTemplateList\waterMarkTemplate\timeline;
use AlibabaCloud\Tea\Model;

class waterMarkTemplate extends Model
{
    /**
     * @description The horizontal offset. Unit: pixel.
     *
     * @example 100
     *
     * @var string
     */
    public $dx;

    /**
     * @description The vertical offset. Unit: pixel.
     *
     * @example 100
     *
     * @var string
     */
    public $dy;

    /**
     * @description The height of the watermark image. Unit: pixel.
     *
     * @example 8
     *
     * @var string
     */
    public $height;

    /**
     * @description The ID of the watermark template.
     *
     * @example 3780bd69b2b74540bc7b1096f564****
     *
     * @var string
     */
    public $id;

    /**
     * @description The name of the watermark template.
     *
     * @example example-watermark
     *
     * @var string
     */
    public $name;

    /**
     * @description The values of the Height, Width, Dx, and Dy parameters relative to the reference edges. If the values of the Height, Width, Dx, and Dy parameters are decimals between 0 and 1, the values are calculated by referring to the following edges in sequence:
     *
     *   **Width**: the width edge.
     *   **Height**: the height edge.
     *   **Long**: the long edge.
     *   **Short**: the short edge.
     *
     * @var ratioRefer
     */
    public $ratioRefer;

    /**
     * @description The position of the watermark. Valid values:
     *
     *   **TopRight**: the upper-right corner.
     *   **TopLeft**: the upper-left corner.
     *   **BottomRight**: the lower-right corner.
     *   **BottomLeft**: the lower-left corner.
     *
     * @example TopRight
     *
     * @var string
     */
    public $referPos;

    /**
     * @description The status of the watermark template. Valid values: Valid values:
     *
     *   **Normal**: The watermark template is normal.
     *   **Deleted**: The watermark template is deleted.
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
     * @description The type of the watermark. Valid values:
     *
     *   Image: an image watermark.
     *   Text: a text watermark.
     *
     * > Only watermarks of the **Image** type are supported.
     * @example Image
     *
     * @var string
     */
    public $type;

    /**
     * @description The width of the watermark image. Unit: pixel.
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
