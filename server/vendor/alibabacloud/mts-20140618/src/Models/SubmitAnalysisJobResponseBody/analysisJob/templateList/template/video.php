<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitAnalysisJobResponseBody\analysisJob\templateList\template;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitAnalysisJobResponseBody\analysisJob\templateList\template\video\bitrateBnd;
use AlibabaCloud\Tea\Model;

class video extends Model
{
    /**
     * @description The average bitrate of the video. Unit: Kbit/s.
     *
     * @example 10
     *
     * @var string
     */
    public $bitrate;

    /**
     * @description The average bitrate range of the video.
     *
     * @var bitrateBnd
     */
    public $bitrateBnd;

    /**
     * @description The size of the buffer.
     *
     *   Unit: KB.
     *   Default value: **6000**.
     *
     * @example 5000
     *
     * @var string
     */
    public $bufsize;

    /**
     * @description The video codec. Default value: **H.264**.
     *
     * @example H.264
     *
     * @var string
     */
    public $codec;

    /**
     * @description The constant rate factor.
     *
     *   Default value if the Codec parameter is set to H.264: **23**. Default value if the Codec parameter is set to H.265: **26**.
     *   If this parameter is returned, the setting of the Bitrate parameter is invalid.
     *
     * @example 27
     *
     * @var string
     */
    public $crf;

    /**
     * @description The strength of the independent noise reduction algorithm.
     *
     * @example 5
     *
     * @var string
     */
    public $degrain;

    /**
     * @description The frame rate.
     *
     *   The value is 60 if the frame rate of the input video exceeds 60.
     *   Default value: the frame rate of the input video.
     *
     * @example 60
     *
     * @var string
     */
    public $fps;

    /**
     * @description The maximum number of frames between two keyframes. Default value: **250**.
     *
     * @example 1
     *
     * @var string
     */
    public $gop;

    /**
     * @description The height of the video.
     *
     *   Unit: pixel.
     *   Default value: the height of the input video.
     *
     * @example 1880
     *
     * @var string
     */
    public $height;

    /**
     * @description The maximum bitrate of the video. Unit: Kbit/s.
     *
     * @example 10
     *
     * @var string
     */
    public $maxrate;

    /**
     * @description The pixel format for video color encoding. Valid values: standard pixel formats such as yuv420p and yuvj420p.
     *
     * @example yuvj420p
     *
     * @var string
     */
    public $pixFmt;

    /**
     * @description The preset video algorithm. Valid values: veryfast, fast, medium, slow, and slower. Default value: **medium**.
     *
     * @example medium
     *
     * @var string
     */
    public $preset;

    /**
     * @description The codec profile. Valid values:
     *
     *   **baseline**: applicable to mobile devices.
     *   **main**: applicable to standard-definition devices.
     *   **high**: applicable to high-definition devices.
     *   Default value: **high**.
     *
     * @example baseline
     *
     * @var string
     */
    public $profile;

    /**
     * @description The level of quality control on the video.
     *
     * @example 15
     *
     * @var string
     */
    public $qscale;

    /**
     * @description The scan mode. Valid values:
     *
     *   **interlaced**
     *   **progressive**
     *
     * @example progressive
     *
     * @var string
     */
    public $scanMode;

    /**
     * @description The width of the video.
     *
     *   Unit: pixel.
     *   Default value: the width of the input video.
     *
     * @example 1990
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'bitrate'    => 'Bitrate',
        'bitrateBnd' => 'BitrateBnd',
        'bufsize'    => 'Bufsize',
        'codec'      => 'Codec',
        'crf'        => 'Crf',
        'degrain'    => 'Degrain',
        'fps'        => 'Fps',
        'gop'        => 'Gop',
        'height'     => 'Height',
        'maxrate'    => 'Maxrate',
        'pixFmt'     => 'PixFmt',
        'preset'     => 'Preset',
        'profile'    => 'Profile',
        'qscale'     => 'Qscale',
        'scanMode'   => 'ScanMode',
        'width'      => 'Width',
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
        if (null !== $this->bitrateBnd) {
            $res['BitrateBnd'] = null !== $this->bitrateBnd ? $this->bitrateBnd->toMap() : null;
        }
        if (null !== $this->bufsize) {
            $res['Bufsize'] = $this->bufsize;
        }
        if (null !== $this->codec) {
            $res['Codec'] = $this->codec;
        }
        if (null !== $this->crf) {
            $res['Crf'] = $this->crf;
        }
        if (null !== $this->degrain) {
            $res['Degrain'] = $this->degrain;
        }
        if (null !== $this->fps) {
            $res['Fps'] = $this->fps;
        }
        if (null !== $this->gop) {
            $res['Gop'] = $this->gop;
        }
        if (null !== $this->height) {
            $res['Height'] = $this->height;
        }
        if (null !== $this->maxrate) {
            $res['Maxrate'] = $this->maxrate;
        }
        if (null !== $this->pixFmt) {
            $res['PixFmt'] = $this->pixFmt;
        }
        if (null !== $this->preset) {
            $res['Preset'] = $this->preset;
        }
        if (null !== $this->profile) {
            $res['Profile'] = $this->profile;
        }
        if (null !== $this->qscale) {
            $res['Qscale'] = $this->qscale;
        }
        if (null !== $this->scanMode) {
            $res['ScanMode'] = $this->scanMode;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return video
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Bitrate'])) {
            $model->bitrate = $map['Bitrate'];
        }
        if (isset($map['BitrateBnd'])) {
            $model->bitrateBnd = bitrateBnd::fromMap($map['BitrateBnd']);
        }
        if (isset($map['Bufsize'])) {
            $model->bufsize = $map['Bufsize'];
        }
        if (isset($map['Codec'])) {
            $model->codec = $map['Codec'];
        }
        if (isset($map['Crf'])) {
            $model->crf = $map['Crf'];
        }
        if (isset($map['Degrain'])) {
            $model->degrain = $map['Degrain'];
        }
        if (isset($map['Fps'])) {
            $model->fps = $map['Fps'];
        }
        if (isset($map['Gop'])) {
            $model->gop = $map['Gop'];
        }
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['Maxrate'])) {
            $model->maxrate = $map['Maxrate'];
        }
        if (isset($map['PixFmt'])) {
            $model->pixFmt = $map['PixFmt'];
        }
        if (isset($map['Preset'])) {
            $model->preset = $map['Preset'];
        }
        if (isset($map['Profile'])) {
            $model->profile = $map['Profile'];
        }
        if (isset($map['Qscale'])) {
            $model->qscale = $map['Qscale'];
        }
        if (isset($map['ScanMode'])) {
            $model->scanMode = $map['ScanMode'];
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }

        return $model;
    }
}
