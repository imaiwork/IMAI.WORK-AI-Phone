<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody\snapshotJob;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody\snapshotJob\snapshotConfig\outputFile;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody\snapshotJob\snapshotConfig\tileOut;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody\snapshotJob\snapshotConfig\tileOutputFile;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitSnapshotJobResponseBody\snapshotJob\snapshotConfig\timeArray;
use AlibabaCloud\Tea\Model;

class snapshotConfig extends Model
{
    /**
     * @description The type of the snapshot. Default value: **Normal**. Valid values:
     *
     *   **normal**: normal frames.
     *   **intra**: I-frames (keyframes).
     *
     * > If the FrameType parameter is set to intra in the request, only keyframes are captured. If no keyframe is found at the specified point in time, the keyframe closest to the specified point in time is captured. Keyframes are captured faster than normal frames if the same snapshot rules are applied.
     * @example intra
     *
     * @var string
     */
    public $frameType;

    /**
     * @description The height of the output snapshot.
     *
     * @example 8
     *
     * @var string
     */
    public $height;

    /**
     * @description The interval for capturing snapshots.
     *
     *   If this parameter is specified in the request, snapshots are captured at intervals. The value must be greater than 0 in the request.
     *   Unit: seconds.
     *   Default value: **10**.
     *
     * @example 20
     *
     * @var string
     */
    public $interval;

    /**
     * @description The number of snapshots. If the Num parameter is set in the request, snapshots are captured at intervals.
     *
     * @example 10
     *
     * @var string
     */
    public $num;

    /**
     * @description The information about the output file of the snapshot job.
     *
     * @var outputFile
     */
    public $outputFile;

    /**
     * @description The tiling configurations.
     *
     * @var tileOut
     */
    public $tileOut;

    /**
     * @description The information about the output file of the tiling job.
     *
     * @var tileOutputFile
     */
    public $tileOutputFile;

    /**
     * @description The start time for capturing snapshots. Unit: milliseconds.
     *
     * @example 5
     *
     * @var string
     */
    public $time;

    /**
     * @var timeArray
     */
    public $timeArray;

    /**
     * @description The width of the output snapshot.
     *
     * @example 8
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'frameType'      => 'FrameType',
        'height'         => 'Height',
        'interval'       => 'Interval',
        'num'            => 'Num',
        'outputFile'     => 'OutputFile',
        'tileOut'        => 'TileOut',
        'tileOutputFile' => 'TileOutputFile',
        'time'           => 'Time',
        'timeArray'      => 'TimeArray',
        'width'          => 'Width',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->frameType) {
            $res['FrameType'] = $this->frameType;
        }
        if (null !== $this->height) {
            $res['Height'] = $this->height;
        }
        if (null !== $this->interval) {
            $res['Interval'] = $this->interval;
        }
        if (null !== $this->num) {
            $res['Num'] = $this->num;
        }
        if (null !== $this->outputFile) {
            $res['OutputFile'] = null !== $this->outputFile ? $this->outputFile->toMap() : null;
        }
        if (null !== $this->tileOut) {
            $res['TileOut'] = null !== $this->tileOut ? $this->tileOut->toMap() : null;
        }
        if (null !== $this->tileOutputFile) {
            $res['TileOutputFile'] = null !== $this->tileOutputFile ? $this->tileOutputFile->toMap() : null;
        }
        if (null !== $this->time) {
            $res['Time'] = $this->time;
        }
        if (null !== $this->timeArray) {
            $res['TimeArray'] = null !== $this->timeArray ? $this->timeArray->toMap() : null;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return snapshotConfig
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FrameType'])) {
            $model->frameType = $map['FrameType'];
        }
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['Interval'])) {
            $model->interval = $map['Interval'];
        }
        if (isset($map['Num'])) {
            $model->num = $map['Num'];
        }
        if (isset($map['OutputFile'])) {
            $model->outputFile = outputFile::fromMap($map['OutputFile']);
        }
        if (isset($map['TileOut'])) {
            $model->tileOut = tileOut::fromMap($map['TileOut']);
        }
        if (isset($map['TileOutputFile'])) {
            $model->tileOutputFile = tileOutputFile::fromMap($map['TileOutputFile']);
        }
        if (isset($map['Time'])) {
            $model->time = $map['Time'];
        }
        if (isset($map['TimeArray'])) {
            $model->timeArray = timeArray::fromMap($map['TimeArray']);
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }

        return $model;
    }
}
