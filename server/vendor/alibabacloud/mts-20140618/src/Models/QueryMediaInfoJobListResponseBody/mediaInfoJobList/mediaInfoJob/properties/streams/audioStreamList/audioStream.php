<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList\mediaInfoJob\properties\streams\audioStreamList;

use AlibabaCloud\Tea\Model;

class audioStream extends Model
{
    /**
     * @description The bitrate of the media file.
     *
     * @example 1536000
     *
     * @var string
     */
    public $bitrate;

    /**
     * @description The number of sound channels.
     *
     * @example 5.1(side)
     *
     * @var string
     */
    public $channelLayout;

    /**
     * @description The output layout of the sound channels.
     *
     * @example 2
     *
     * @var string
     */
    public $channels;

    /**
     * @description The full name of the encoding format.
     *
     * @example DCA (DTS Coherent Acoustics)
     *
     * @var string
     */
    public $codecLongName;

    /**
     * @description The short name of the encoding format. Valid values:
     *
     *   **acc**
     *   **mp3**
     *   **mp4**
     *   **ogg**
     *   **flac**
     *
     * @example acc
     *
     * @var string
     */
    public $codecName;

    /**
     * @description The tag of the encoding format.
     *
     * @example 0x0000
     *
     * @var string
     */
    public $codecTag;

    /**
     * @description The tag string of the encoding format.
     *
     * @example [0][0][0][0]
     *
     * @var string
     */
    public $codecTagString;

    /**
     * @description The codec time base.
     *
     * @example 1/48000
     *
     * @var string
     */
    public $codecTimeBase;

    /**
     * @description The duration of the media file.
     *
     * @example 123
     *
     * @var string
     */
    public $duration;

    /**
     * @description The sequence number of the audio stream. The value indicates the position of the audio stream in all audio streams.
     *
     * @example 1
     *
     * @var string
     */
    public $index;

    /**
     * @description The language. For more information, see [FFmpeg documentation](https://www.ffmpeg.org/ffmpeg-all.html?spm=a2c4g.11186623.2.66.243851cd2SntfN#Metadata).
     *
     * @example eng
     *
     * @var string
     */
    public $lang;

    /**
     * @description The total number of frames.
     *
     * @example 123
     *
     * @var string
     */
    public $numFrames;

    /**
     * @description The sampling format.
     *
     * @example fltp
     *
     * @var string
     */
    public $sampleFmt;

    /**
     * @description The sampling rate.
     *
     * @example 48000
     *
     * @var string
     */
    public $samplerate;

    /**
     * @description The start time.
     *
     * @example 0.042000
     *
     * @var string
     */
    public $startTime;

    /**
     * @description The time base.
     *
     * @example 1/1000
     *
     * @var string
     */
    public $timebase;
    protected $_name = [
        'bitrate'        => 'Bitrate',
        'channelLayout'  => 'ChannelLayout',
        'channels'       => 'Channels',
        'codecLongName'  => 'CodecLongName',
        'codecName'      => 'CodecName',
        'codecTag'       => 'CodecTag',
        'codecTagString' => 'CodecTagString',
        'codecTimeBase'  => 'CodecTimeBase',
        'duration'       => 'Duration',
        'index'          => 'Index',
        'lang'           => 'Lang',
        'numFrames'      => 'NumFrames',
        'sampleFmt'      => 'SampleFmt',
        'samplerate'     => 'Samplerate',
        'startTime'      => 'StartTime',
        'timebase'       => 'Timebase',
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
        if (null !== $this->channelLayout) {
            $res['ChannelLayout'] = $this->channelLayout;
        }
        if (null !== $this->channels) {
            $res['Channels'] = $this->channels;
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
        if (null !== $this->duration) {
            $res['Duration'] = $this->duration;
        }
        if (null !== $this->index) {
            $res['Index'] = $this->index;
        }
        if (null !== $this->lang) {
            $res['Lang'] = $this->lang;
        }
        if (null !== $this->numFrames) {
            $res['NumFrames'] = $this->numFrames;
        }
        if (null !== $this->sampleFmt) {
            $res['SampleFmt'] = $this->sampleFmt;
        }
        if (null !== $this->samplerate) {
            $res['Samplerate'] = $this->samplerate;
        }
        if (null !== $this->startTime) {
            $res['StartTime'] = $this->startTime;
        }
        if (null !== $this->timebase) {
            $res['Timebase'] = $this->timebase;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return audioStream
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Bitrate'])) {
            $model->bitrate = $map['Bitrate'];
        }
        if (isset($map['ChannelLayout'])) {
            $model->channelLayout = $map['ChannelLayout'];
        }
        if (isset($map['Channels'])) {
            $model->channels = $map['Channels'];
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
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['Index'])) {
            $model->index = $map['Index'];
        }
        if (isset($map['Lang'])) {
            $model->lang = $map['Lang'];
        }
        if (isset($map['NumFrames'])) {
            $model->numFrames = $map['NumFrames'];
        }
        if (isset($map['SampleFmt'])) {
            $model->sampleFmt = $map['SampleFmt'];
        }
        if (isset($map['Samplerate'])) {
            $model->samplerate = $map['Samplerate'];
        }
        if (isset($map['StartTime'])) {
            $model->startTime = $map['StartTime'];
        }
        if (isset($map['Timebase'])) {
            $model->timebase = $map['Timebase'];
        }

        return $model;
    }
}
