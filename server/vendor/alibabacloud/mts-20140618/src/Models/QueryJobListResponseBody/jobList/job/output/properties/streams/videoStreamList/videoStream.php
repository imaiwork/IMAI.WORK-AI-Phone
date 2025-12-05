<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\properties\streams\videoStreamList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\properties\streams\videoStreamList\videoStream\networkCost;
use AlibabaCloud\Tea\Model;

class videoStream extends Model
{
    /**
     * @description The average frame rate of the video stream.
     *
     * @example 30.0
     *
     * @var string
     */
    public $avgFPS;

    /**
     * @description The video bitrate.
     *
     * @example 421.117
     *
     * @var string
     */
    public $bitrate;

    /**
     * @description The full name of the codec.
     *
     * @example H.264 / AVC / MPEG-4 AVC / MPEG-4 part 10
     *
     * @var string
     */
    public $codecLongName;

    /**
     * @description The short name of the codec.
     *
     * @example h264
     *
     * @var string
     */
    public $codecName;

    /**
     * @description The tag of the codec.
     *
     * @example 0x31637661
     *
     * @var string
     */
    public $codecTag;

    /**
     * @description The tag string of the codec.
     *
     * @example avc1
     *
     * @var string
     */
    public $codecTagString;

    /**
     * @description The codec time base.
     *
     * @example 1/60
     *
     * @var string
     */
    public $codecTimeBase;

    /**
     * @description The display aspect ratio (DAR) of the video stream.
     *
     * @example 9:16
     *
     * @var string
     */
    public $dar;

    /**
     * @description The duration of the video stream.
     *
     * @example 17.233333
     *
     * @var string
     */
    public $duration;

    /**
     * @description The frame rate of the video stream.
     *
     * @example 30.0
     *
     * @var string
     */
    public $fps;

    /**
     * @description Indicates whether the video stream contains bidirectional frames (B-frames).
     *
     * @example 2
     *
     * @var string
     */
    public $hasBFrames;

    /**
     * @description The height of the video stream in pixels.
     *
     * @example 1280
     *
     * @var string
     */
    public $height;

    /**
     * @description The sequence number of the video stream. The value indicates the position of the video stream in all video streams.
     *
     * @example 0
     *
     * @var string
     */
    public $index;

    /**
     * @description The language of the video stream. For more information, see [FFmpeg documentation](https://www.ffmpeg.org/ffmpeg-all.html#Metadata) and [ISO 639](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes).
     *
     * @example eng
     *
     * @var string
     */
    public $lang;

    /**
     * @description The codec level.
     *
     * @example 31
     *
     * @var string
     */
    public $level;

    /**
     * @description The network bandwidth that was consumed.
     *
     * @var networkCost
     */
    public $networkCost;

    /**
     * @description The total number of frames.
     *
     * @example 30
     *
     * @var string
     */
    public $numFrames;

    /**
     * @description The pixel format of the video stream.
     *
     * @example yuv420p
     *
     * @var string
     */
    public $pixFmt;

    /**
     * @description The codec profile.
     *
     * @example high
     *
     * @var string
     */
    public $profile;

    /**
     * @description The sample aspect ratio (SAR) of the video stream.
     *
     * @example 1:1
     *
     * @var string
     */
    public $sar;

    /**
     * @description The start time.
     *
     * @example 0.000000
     *
     * @var string
     */
    public $startTime;

    /**
     * @description The time base of the video stream.
     *
     * @example 1/15360
     *
     * @var string
     */
    public $timebase;

    /**
     * @description The width of the video stream in pixels.
     *
     * @example 720
     *
     * @var string
     */
    public $width;

    /**
     * @description The number of binary bits used by each sample or pixel.
     *
     * @example 8
     *
     * @var string
     */
    public $bitsPerRawSample;

    /**
     * @description The primary colors.
     *
     * @example bt709
     *
     * @var string
     */
    public $colorPrimaries;

    /**
     * @description The color transfer configuration.
     *
     * @example bt709
     *
     * @var string
     */
    public $colorTransfer;
    protected $_name = [
        'avgFPS'           => 'AvgFPS',
        'bitrate'          => 'Bitrate',
        'codecLongName'    => 'CodecLongName',
        'codecName'        => 'CodecName',
        'codecTag'         => 'CodecTag',
        'codecTagString'   => 'CodecTagString',
        'codecTimeBase'    => 'CodecTimeBase',
        'dar'              => 'Dar',
        'duration'         => 'Duration',
        'fps'              => 'Fps',
        'hasBFrames'       => 'HasBFrames',
        'height'           => 'Height',
        'index'            => 'Index',
        'lang'             => 'Lang',
        'level'            => 'Level',
        'networkCost'      => 'NetworkCost',
        'numFrames'        => 'NumFrames',
        'pixFmt'           => 'PixFmt',
        'profile'          => 'Profile',
        'sar'              => 'Sar',
        'startTime'        => 'StartTime',
        'timebase'         => 'Timebase',
        'width'            => 'Width',
        'bitsPerRawSample' => 'bitsPerRawSample',
        'colorPrimaries'   => 'colorPrimaries',
        'colorTransfer'    => 'colorTransfer',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->avgFPS) {
            $res['AvgFPS'] = $this->avgFPS;
        }
        if (null !== $this->bitrate) {
            $res['Bitrate'] = $this->bitrate;
        }
        if (null !== $this->codecLongName) {
            $res['CodecLongName'] = $this->codecLongName;
        }
        if (null !== $this->codecName) {
            $res['CodecName'] = $this->codecName;
        }
        if (null !== $this->codecTag) {
            $res['CodecTag'] = $this->codecTag;
        }
        if (null !== $this->codecTagString) {
            $res['CodecTagString'] = $this->codecTagString;
        }
        if (null !== $this->codecTimeBase) {
            $res['CodecTimeBase'] = $this->codecTimeBase;
        }
        if (null !== $this->dar) {
            $res['Dar'] = $this->dar;
        }
        if (null !== $this->duration) {
            $res['Duration'] = $this->duration;
        }
        if (null !== $this->fps) {
            $res['Fps'] = $this->fps;
        }
        if (null !== $this->hasBFrames) {
            $res['HasBFrames'] = $this->hasBFrames;
        }
        if (null !== $this->height) {
            $res['Height'] = $this->height;
        }
        if (null !== $this->index) {
            $res['Index'] = $this->index;
        }
        if (null !== $this->lang) {
            $res['Lang'] = $this->lang;
        }
        if (null !== $this->level) {
            $res['Level'] = $this->level;
        }
        if (null !== $this->networkCost) {
            $res['NetworkCost'] = null !== $this->networkCost ? $this->networkCost->toMap() : null;
        }
        if (null !== $this->numFrames) {
            $res['NumFrames'] = $this->numFrames;
        }
        if (null !== $this->pixFmt) {
            $res['PixFmt'] = $this->pixFmt;
        }
        if (null !== $this->profile) {
            $res['Profile'] = $this->profile;
        }
        if (null !== $this->sar) {
            $res['Sar'] = $this->sar;
        }
        if (null !== $this->startTime) {
            $res['StartTime'] = $this->startTime;
        }
        if (null !== $this->timebase) {
            $res['Timebase'] = $this->timebase;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }
        if (null !== $this->bitsPerRawSample) {
            $res['bitsPerRawSample'] = $this->bitsPerRawSample;
        }
        if (null !== $this->colorPrimaries) {
            $res['colorPrimaries'] = $this->colorPrimaries;
        }
        if (null !== $this->colorTransfer) {
            $res['colorTransfer'] = $this->colorTransfer;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return videoStream
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AvgFPS'])) {
            $model->avgFPS = $map['AvgFPS'];
        }
        if (isset($map['Bitrate'])) {
            $model->bitrate = $map['Bitrate'];
        }
        if (isset($map['CodecLongName'])) {
            $model->codecLongName = $map['CodecLongName'];
        }
        if (isset($map['CodecName'])) {
            $model->codecName = $map['CodecName'];
        }
        if (isset($map['CodecTag'])) {
            $model->codecTag = $map['CodecTag'];
        }
        if (isset($map['CodecTagString'])) {
            $model->codecTagString = $map['CodecTagString'];
        }
        if (isset($map['CodecTimeBase'])) {
            $model->codecTimeBase = $map['CodecTimeBase'];
        }
        if (isset($map['Dar'])) {
            $model->dar = $map['Dar'];
        }
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['Fps'])) {
            $model->fps = $map['Fps'];
        }
        if (isset($map['HasBFrames'])) {
            $model->hasBFrames = $map['HasBFrames'];
        }
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['Index'])) {
            $model->index = $map['Index'];
        }
        if (isset($map['Lang'])) {
            $model->lang = $map['Lang'];
        }
        if (isset($map['Level'])) {
            $model->level = $map['Level'];
        }
        if (isset($map['NetworkCost'])) {
            $model->networkCost = networkCost::fromMap($map['NetworkCost']);
        }
        if (isset($map['NumFrames'])) {
            $model->numFrames = $map['NumFrames'];
        }
        if (isset($map['PixFmt'])) {
            $model->pixFmt = $map['PixFmt'];
        }
        if (isset($map['Profile'])) {
            $model->profile = $map['Profile'];
        }
        if (isset($map['Sar'])) {
            $model->sar = $map['Sar'];
        }
        if (isset($map['StartTime'])) {
            $model->startTime = $map['StartTime'];
        }
        if (isset($map['Timebase'])) {
            $model->timebase = $map['Timebase'];
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }
        if (isset($map['bitsPerRawSample'])) {
            $model->bitsPerRawSample = $map['bitsPerRawSample'];
        }
        if (isset($map['colorPrimaries'])) {
            $model->colorPrimaries = $map['colorPrimaries'];
        }
        if (isset($map['colorTransfer'])) {
            $model->colorTransfer = $map['colorTransfer'];
        }

        return $model;
    }
}
