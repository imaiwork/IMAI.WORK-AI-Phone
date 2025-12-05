<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody\snapshotJob\snapshotConfig;

use AlibabaCloud\Tea\Model;

class tileOut extends Model
{
    /**
     * @description The height of a single image. The default value is the height of the output snapshot.
     *
     * @example 100
     *
     * @var string
     */
    public $cellHeight;

    /**
     * @description The step for selecting a single image.
     *
     * @example 3
     *
     * @var string
     */
    public $cellSelStep;

    /**
     * @description The width of a single image. The default value is the width of the output snapshot.
     *
     * @example 100
     *
     * @var string
     */
    public $cellWidth;

    /**
     * @description The background color.
     *
     *   Default value: **black**.
     *   You can set the Color parameter to a **color keyword** or **random** in the request.
     *
     * > If you want to set the background color to black, you can specify the color keyword in one of the following three formats: Black, black, and #000000.
     * @example black
     *
     * @var string
     */
    public $color;

    /**
     * @description The number of columns that the tiled image contains. Default value: **10**.
     *
     * @example 10
     *
     * @var string
     */
    public $columns;

    /**
     * @description Indicates whether the single images are retained. Valid values:
     *
     *   **true**: The single images are retained.
     *   **false**: The single images are not retained.
     *   Default value: **true**.
     *
     * @example false
     *
     * @var string
     */
    public $isKeepCellPic;

    /**
     * @description The number of rows that the tiled image contains. Default value: **10**.
     *
     * @example 10
     *
     * @var string
     */
    public $lines;

    /**
     * @description The margin width of the tiled image.
     *
     *   Default value: **0**.
     *   Unit: pixel.
     *
     * @example 5
     *
     * @var string
     */
    public $margin;

    /**
     * @description The distance between two consecutive single images in the tiled image.
     *
     *   Default value: **0**.
     *   Unit: pixel.
     *
     * @example 0
     *
     * @var string
     */
    public $padding;
    protected $_name = [
        'cellHeight'    => 'CellHeight',
        'cellSelStep'   => 'CellSelStep',
        'cellWidth'     => 'CellWidth',
        'color'         => 'Color',
        'columns'       => 'Columns',
        'isKeepCellPic' => 'IsKeepCellPic',
        'lines'         => 'Lines',
        'margin'        => 'Margin',
        'padding'       => 'Padding',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->cellHeight) {
            $res['CellHeight'] = $this->cellHeight;
        }
        if (null !== $this->cellSelStep) {
            $res['CellSelStep'] = $this->cellSelStep;
        }
        if (null !== $this->cellWidth) {
            $res['CellWidth'] = $this->cellWidth;
        }
        if (null !== $this->color) {
            $res['Color'] = $this->color;
        }
        if (null !== $this->columns) {
            $res['Columns'] = $this->columns;
        }
        if (null !== $this->isKeepCellPic) {
            $res['IsKeepCellPic'] = $this->isKeepCellPic;
        }
        if (null !== $this->lines) {
            $res['Lines'] = $this->lines;
        }
        if (null !== $this->margin) {
            $res['Margin'] = $this->margin;
        }
        if (null !== $this->padding) {
            $res['Padding'] = $this->padding;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return tileOut
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CellHeight'])) {
            $model->cellHeight = $map['CellHeight'];
        }
        if (isset($map['CellSelStep'])) {
            $model->cellSelStep = $map['CellSelStep'];
        }
        if (isset($map['CellWidth'])) {
            $model->cellWidth = $map['CellWidth'];
        }
        if (isset($map['Color'])) {
            $model->color = $map['Color'];
        }
        if (isset($map['Columns'])) {
            $model->columns = $map['Columns'];
        }
        if (isset($map['IsKeepCellPic'])) {
            $model->isKeepCellPic = $map['IsKeepCellPic'];
        }
        if (isset($map['Lines'])) {
            $model->lines = $map['Lines'];
        }
        if (isset($map['Margin'])) {
            $model->margin = $map['Margin'];
        }
        if (isset($map['Padding'])) {
            $model->padding = $map['Padding'];
        }

        return $model;
    }
}
