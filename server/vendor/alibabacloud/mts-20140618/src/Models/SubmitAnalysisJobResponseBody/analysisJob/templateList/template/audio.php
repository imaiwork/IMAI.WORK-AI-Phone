<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitAnalysisJobResponseBody\analysisJob\templateList\template;

use AlibabaCloud\Tea\Model;

class audio extends Model
{
    /**
     * @description The audio bitrate of the output file.
     *
     *   Unit: Kbit/s.
     *   Default value: **128**.
     *
     * @example 8
     *
     * @var string
     */
    public $bitrate;

    /**
     * @description The number of sound channels. Default value: **2**.
     *
     * @example 1
     *
     * @var string
     */
    public $channels;

    /**
     * @description The audio codec format. Default value: **acc**.
     *
     * @example mp3
     *
     * @var string
     */
    public $codec;

    /**
     * @description The codec profile of the audio. Valid values if the **Codec** parameter is set to **aac**: aac_low, aac_he, aac_he_v2, aac_ld, and aac_eld.
     *
     * @example aac_low
     *
     * @var string
     */
    public $profile;

    /**
     * @description The level of quality control on the audio.
     *
     * @example 10
     *
     * @var string
     */
    public $qscale;

    /**
     * @description The sampling rate.
     *
     *   Unit: Hz.
     *   Default value: **44100**.
     *
     * @example 32000
     *
     * @var string
     */
    public $samplerate;
    protected $_name = [
        'bitrate'    => 'Bitrate',
        'channels'   => 'Channels',
        'codec'      => 'Codec',
        'profile'    => 'Profile',
        'qscale'     => 'Qscale',
        'samplerate' => 'Samplerate',
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

        return $model;
    }
}
