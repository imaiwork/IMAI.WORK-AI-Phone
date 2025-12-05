<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\audio\volume;
use AlibabaCloud\Tea\Model;

class audio extends Model
{
    /**
     * @description The audio bitrate of the output file.
     *
     *   Unit: Kbit/s.
     *   Default value: **128**.
     *
     * @example 128
     *
     * @var string
     */
    public $bitrate;

    /**
     * @description The number of sound channels.
     *
     *   If the value of Codec is mp3, the value of this parameter can only be 1 or 2.
     *   If the value of Codec is aac, the value of this parameter can only be 1, 2, 4, 5, 6, or 8.
     *   Default value: 2.
     *
     * @example 2
     *
     * @var string
     */
    public $channels;

    /**
     * @description The audio codec.
     *
     *   Valid values: aac, mp3, vorbis, and flac.
     *   Default value: **aac**.
     *
     * @example aac
     *
     * @var string
     */
    public $codec;

    /**
     * @description The codec profile of the audio. Valid values if the value of Codec is aac: aaclow, aache, aachev2, aacld, and aaceld.
     *
     * @example aaclow
     *
     * @var string
     */
    public $profile;

    /**
     * @description The level of quality control on the audio.
     *
     * @example 15
     *
     * @var string
     */
    public $qscale;

    /**
     * @description The sampling rate.
     *
     *   Valid values: 22050, 32000, 44100, 48000, and 96000.
     *   Unit: Hz.
     *   Default value: 44100.
     *   If the video container format is FLV and the audio codec is MP3, the value of this parameter cannot be 32000, 48000, or 96000. If the audio codec is MP3, the value of this parameter cannot be 96000.
     *
     * @example 44100
     *
     * @var string
     */
    public $samplerate;

    /**
     * @description The volume configurations.
     *
     * @var volume
     */
    public $volume;
    protected $_name = [
        'bitrate'    => 'Bitrate',
        'channels'   => 'Channels',
        'codec'      => 'Codec',
        'profile'    => 'Profile',
        'qscale'     => 'Qscale',
        'samplerate' => 'Samplerate',
        'volume'     => 'Volume',
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
        if (null !== $this->channels) {
            $res['Channels'] = $this->channels;
        }
        if (null !== $this->codec) {
            $res['Codec'] = $this->codec;
        }
        if (null !== $this->profile) {
            $res['Profile'] = $this->profile;
        }
        if (null !== $this->qscale) {
            $res['Qscale'] = $this->qscale;
        }
        if (null !== $this->samplerate) {
            $res['Samplerate'] = $this->samplerate;
        }
        if (null !== $this->volume) {
            $res['Volume'] = null !== $this->volume ? $this->volume->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return audio
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Bitrate'])) {
            $model->bitrate = $map['Bitrate'];
        }
        if (isset($map['Channels'])) {
            $model->channels = $map['Channels'];
        }
        if (isset($map['Codec'])) {
            $model->codec = $map['Codec'];
        }
        if (isset($map['Profile'])) {
            $model->profile = $map['Profile'];
        }
        if (isset($map['Qscale'])) {
            $model->qscale = $map['Qscale'];
        }
        if (isset($map['Samplerate'])) {
            $model->samplerate = $map['Samplerate'];
        }
        if (isset($map['Volume'])) {
            $model->volume = volume::fromMap($map['Volume']);
        }

        return $model;
    }
}
