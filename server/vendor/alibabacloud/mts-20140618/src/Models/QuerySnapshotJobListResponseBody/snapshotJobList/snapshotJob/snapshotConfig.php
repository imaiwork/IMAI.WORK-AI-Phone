<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob;

use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob\snapshotConfig\outputFile;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob\snapshotConfig\tileOut;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob\snapshotConfig\tileOutputFile;
use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob\snapshotConfig\timeArray;
use AlibabaCloud\Tea\Model;

class snapshotConfig extends Model
{
    /**
     * @description The ID of the region in which the output OSS bucket is located.
     *
     * @example intra
     *
     * @var string
     */
    public $frameType;

    /**
     * @description The number of snapshots to take. If the Num parameter is set in the request, snapshots are taken at intervals.
     *
     * @example 8
     *
     * @var string
     */
    public $height;

    /**
     * @description The OSS object that is generated as the output file of the snapshot job.
     *
     * @example 10
     *
     * @var string
     */
    public $interval;

    /**
     * @description The status of the snapshot job.
     *
     * - **Submitted**: The job was submitted.
     * - **Snapshoting**: The job is being processed.
     * - **Success**: The job was successfully processed.
     * - **Fail**: The job failed.
     * @example 10
     *
     * @var string
     */
    public $num;

    /**
     * @description The OSS output file of the tiling job.
     *
     * @var outputFile
     */
    public $outputFile;

    /**
     * @description The margin width of the tiled image.
     *
     *   Default value: **0**.
     *   Unit: pixel.
     *
     * @var tileOut
     */
    public $tileOut;

    /**
     * @description The error message returned when the job fails. This parameter is not returned if the job is successfully processed.
     *
     * @var tileOutputFile
     */
    public $tileOutputFile;

    /**
     * @description The width of a single image. The default value is the width of the output snapshot.
     *
     * @example 4
     *
     * @var string
     */
    public $time;

    /**
     * @var timeArray
     */
    public $timeArray;

    /**
     * @description The OSS bucket that stores the output file.
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
