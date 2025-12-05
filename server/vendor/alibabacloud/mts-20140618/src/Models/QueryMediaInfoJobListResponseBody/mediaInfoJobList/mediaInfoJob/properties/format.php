<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList\mediaInfoJob\properties;

use AlibabaCloud\Tea\Model;

class format extends Model
{
    /**
     * @description The total bitrate.
     *
     * @example 1630.045
     *
     * @var string
     */
    public $bitrate;

    /**
     * @description The total duration.
     *
     * @example 17.226000
     *
     * @var string
     */
    public $duration;

    /**
     * @description The full name of the container format.
     *
     * @example QuickTime/MOV
     *
     * @var string
     */
    public $formatLongName;

    /**
     * @description The short name of the container format.
     *
     * @example mov
     *
     * @var string
     */
    public $formatName;

    /**
     * @description The total number of program streams.
     *
     * @example 2
     *
     * @var string
     */
    public $numPrograms;

    /**
     * @description The total number of media streams.
     *
     * @example 1
     *
     * @var string
     */
    public $numStreams;

    /**
     * @description The size of the image file.
     *
     * @example 3509895
     *
     * @var string
     */
    public $size;

    /**
     * @description The start time.
     *
     * @example 0.042000
     *
     * @var string
     */
    public $startTime;
    protected $_name = [
        'bitrate'        => 'Bitrate',
        'duration'       => 'Duration',
        'formatLongName' => 'FormatLongName',
        'formatName'     => 'FormatName',
        'numPrograms'    => 'NumPrograms',
        'numStreams'     => 'NumStreams',
        'size'           => 'Size',
        'startTime'      => 'StartTime',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->bitrate) {
            $res['Bitrate'] = $this->bitrate;
        }
        if (null !== $this->duration) {
            $res['Duration'] = $this->duration;
        }
        if (null !== $this->formatLongName) {
            $res['FormatLongName'] = $this->formatLongName;
        }
        if (null !== $this->formatName) {
            $res['FormatName'] = $this->formatName;
        }
        if (null !== $this->numPrograms) {
            $res['NumPrograms'] = $this->numPrograms;
        }
        if (null !== $this->numStreams) {
            $res['NumStreams'] = $this->numStreams;
        }
        if (null !== $this->size) {
            $res['Size'] = $this->size;
        }
        if (null !== $this->startTime) {
            $res['StartTime'] = $this->startTime;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return format
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Bitrate'])) {
            $model->bitrate = $map['Bitrate'];
        }
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['FormatLongName'])) {
            $model->formatLongName = $map['FormatLongName'];
        }
        if (isset($map['FormatName'])) {
            $model->formatName = $map['FormatName'];
        }
        if (isset($map['NumPrograms'])) {
            $model->numPrograms = $map['NumPrograms'];
        }
        if (isset($map['NumStreams'])) {
            $model->numStreams = $map['NumStreams'];
        }
        if (isset($map['Size'])) {
            $model->size = $map['Size'];
        }
        if (isset($map['StartTime'])) {
            $model->startTime = $map['StartTime'];
        }

        return $model;
    }
}
