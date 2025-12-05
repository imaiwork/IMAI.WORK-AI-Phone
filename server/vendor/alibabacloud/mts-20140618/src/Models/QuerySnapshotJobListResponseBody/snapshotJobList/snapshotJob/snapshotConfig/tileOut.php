<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob\snapshotConfig;

use AlibabaCloud\Tea\Model;

class tileOut extends Model
{
    /**
     * @description The interval for taking snapshots.
     *
     *   If this Interval parameter is specified in the request, snapshots are taken at intervals. The value must be greater than 0.
     *   Unit: seconds.
     *   Default value: **10**.
     *
     * @example 8
     *
     * @var string
     */
    public $cellHeight;

    /**
     * @description The number of rows that the tiled image can contain. Default value: **10**.
     *
     * @example 3
     *
     * @var string
     */
    public $cellSelStep;

    /**
     * @description The type of the snapshot. Valid values:
     *
     *   **normal**: normal frames.
     *   **intra**: I-frames.
     *   Default value: **intra**.
     *
     * @example 8
     *
     * @var string
     */
    public $cellWidth;

    /**
     * @description Indicates whether the single images are retained. Default value: **true**.
     *
     * @example black
     *
     * @var string
     */
    public $color;

    /**
     * @description The height of the output snapshot.
     *
     * @example 10
     *
     * @var string
     */
    public $columns;

    /**
     * @description The Object Storage Service (OSS) output file of the snapshot job.
     *
     * @example false
     *
     * @var string
     */
    public $isKeepCellPic;

    /**
     * @description The Alibaba Cloud Resource Name (ARN) of the specified RAM role. Format: acs:ram::$accountID:role/$roleName.
     *
     * @example 10
     *
     * @var string
     */
    public $lines;

    /**
     * @description The width of the output snapshot.
     *
     * @example 0
     *
     * @var string
     */
    public $margin;

    /**
     * @description The number of columns that the tiled image can contain. Default value: **10**.
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
