<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponseBody\template;

use AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponseBody\template\video\bitrateBnd;
use AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponseBody\template\video\narrowBand;
use AlibabaCloud\Tea\Model;

class video extends Model
{
    /**
     * @description The bitrate of the output video. Unit: Kbit/s.
     *
     * @example 500
     *
     * @var string
     */
    public $bitrate;

    /**
     * @description The bitrate range of the video.
     *
     * @var bitrateBnd
     */
    public $bitrateBnd;

    /**
     * @description The size of the buffer.
     *
     *   Default value: **6000**.
     *   Unit: KB.
     *
     * @example 6000
     *
     * @var string
     */
    public $bufsize;

    /**
     * @description The video codec. Valid values: H.264, H.265, GIF, and WebP. Default value: **H.264**.
     *
     * @example H.264
     *
     * @var string
     */
    public $codec;

    /**
     * @description The constant rate factor. Default value if the video codec is set to H.264: **23**. Default value if the video codec is set to H.265: **26**.
     *
     * > If this parameter is specified, the setting of the Bitrate parameter becomes invalid.
     * @example 15
     *
     * @var string
     */
    public $crf;

    /**
     * @description The method of video cropping. Valid values:
     *
     *   **border**: automatically detects and removes borders.
     *   **Value in the format of width:height:left:top**: crops the video image based on the custom settings. Example: 1280:800:0:140.
     *
     * @example border
     *
     * @var string
     */
    public $crop;

    /**
     * @description The level of quality control on the video.
     *
     * @example 10
     *
     * @var string
     */
    public $degrain;

    /**
     * @description The frame rate. Default value: the frame rate of the input file. The value is 60 if the frame rate of the input file exceeds 60. Unit: frames per second.
     *
     * @example 25
     *
     * @var string
     */
    public $fps;

    /**
     * @description The GOP size. The GOP size can be the maximum interval of keyframes or the maximum number of frames in a frame group. If the maximum interval is specified, the value contains the unit (s). If the maximum number of frames is specified, the value does not contain a unit. Default value: **10s**.
     *
     * @example 10s
     *
     * @var string
     */
    public $gop;

    /**
     * @description Indicates whether the HDR2SDR conversion feature is enabled. If this feature is enabled, high dynamic range (HDR) videos are transcoded to standard dynamic range (SDR) videos.
     *
     * @example true
     *
     * @var string
     */
    public $hdr2sdr;

    /**
     * @description The height of the video.
     *
     *   Unit: pixel.
     *   Default value: the height of the input video.
     *
     * @example 800
     *
     * @var string
     */
    public $height;

    /**
     * @description Indicates whether the auto-rotate screen feature is enabled. Default value: **false**. Valid values:
     *
     *   **true**: The auto-rotate screen feature is enabled.
     *   **false**: The auto-rotate screen feature is disabled.
     *
     * > If this feature is enabled, the width of the output video corresponds to the long side of the input video, which is the height of the input video in portrait mode. The height of the output video corresponds to the short side of the input video, which is the width of the input video in portrait mode.
     * @example false
     *
     * @var string
     */
    public $longShortMode;

    /**
     * @description The maximum frame rate.
     *
     * @example 60
     *
     * @var string
     */
    public $maxFps;

    /**
     * @description The maximum bitrate of the video. Unit: Kbit/s.
     *
     * @example 500
     *
     * @var string
     */
    public $maxrate;

    /**
     * @description The Narrowband HD settings.
     *
     * @var narrowBand
     */
    public $narrowBand;

    /**
     * @description The black borders to be added to the video. The value is in the width:height:left:top format.
     *
     * @example 1280:800:0:140
     *
     * @var string
     */
    public $pad;

    /**
     * @description The pixel format. Standard pixel formats such as yuv420p and yuvj420p are supported. The default pixel format can be **yuv420p** or the pixel format of the input video.
     *
     * @example yuv420p
     *
     * @var string
     */
    public $pixFmt;

    /**
     * @description The preset video algorithm. Default value: **medium**. Valid values:
     *
     *   **veryfast**
     *   **fast**
     *   **medium**
     *   **slow**
     *   **slower**
     *
     * > This parameter is valid only if the Codec parameter is set to H.264.
     * @example fast
     *
     * @var string
     */
    public $preset;

    /**
     * @description The codec profile.
     *
     *   **baseline**: suitable for mobile devices
     *   **main**: suitable for standard-definition devices
     *   **high**: suitable for high-definition devices
     *   Default value: **high**.
     *
     * > This parameter is valid only if the Codec parameter is set to H.264.
     * @example high
     *
     * @var string
     */
    public $profile;

    /**
     * @description The level of the independent denoising algorithm.
     *
     * @example 1
     *
     * @var string
     */
    public $qscale;

    /**
     * @description Indicates whether the video stream is deleted.
     *
     *   **true**: The video stream is deleted.
     *   **false**: The video stream is retained.
     *   Default value: **false**.
     *
     * @example false
     *
     * @var string
     */
    public $remove;

    /**
     * @description The policy of resolution adjustment.
     *
     * @example 0
     *
     * @var string
     */
    public $resoPriority;

    /**
     * @description The scan mode. Valid values:
     *
     *   **interlaced**
     *   **progressive**
     *
     * @example interlaced
     *
     * @var string
     */
    public $scanMode;

    /**
     * @description The width of the video.
     *
     *   Default value: the width of the input video.****
     *   Unit: pixel.
     *
     * @example 256
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'bitrate'       => 'Bitrate',
        'bitrateBnd'    => 'BitrateBnd',
        'bufsize'       => 'Bufsize',
        'codec'         => 'Codec',
        'crf'           => 'Crf',
        'crop'          => 'Crop',
        'degrain'       => 'Degrain',
        'fps'           => 'Fps',
        'gop'           => 'Gop',
        'hdr2sdr'       => 'Hdr2sdr',
        'height'        => 'Height',
        'longShortMode' => 'LongShortMode',
        'maxFps'        => 'MaxFps',
        'maxrate'       => 'Maxrate',
        'narrowBand'    => 'NarrowBand',
        'pad'           => 'Pad',
        'pixFmt'        => 'PixFmt',
        'preset'        => 'Preset',
        'profile'       => 'Profile',
        'qscale'        => 'Qscale',
        'remove'        => 'Remove',
        'resoPriority'  => 'ResoPriority',
        'scanMode'      => 'ScanMode',
        'width'         => 'Width',
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
        if (null !== $this->crop) {
            $res['Crop'] = $this->crop;
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
        if (null !== $this->hdr2sdr) {
            $res['Hdr2sdr'] = $this->hdr2sdr;
        }
        if (null !== $this->height) {
            $res['Height'] = $this->height;
        }
        if (null !== $this->longShortMode) {
            $res['LongShortMode'] = $this->longShortMode;
        }
        if (null !== $this->maxFps) {
            $res['MaxFps'] = $this->maxFps;
        }
        if (null !== $this->maxrate) {
            $res['Maxrate'] = $this->maxrate;
        }
        if (null !== $this->narrowBand) {
            $res['NarrowBand'] = null !== $this->narrowBand ? $this->narrowBand->toMap() : null;
        }
        if (null !== $this->pad) {
            $res['Pad'] = $this->pad;
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
        if (null !== $this->remove) {
            $res['Remove'] = $this->remove;
        }
        if (null !== $this->resoPriority) {
            $res['ResoPriority'] = $this->resoPriority;
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
        if (isset($map['Crop'])) {
            $model->crop = $map['Crop'];
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
        if (isset($map['Hdr2sdr'])) {
            $model->hdr2sdr = $map['Hdr2sdr'];
        }
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['LongShortMode'])) {
            $model->longShortMode = $map['LongShortMode'];
        }
        if (isset($map['MaxFps'])) {
            $model->maxFps = $map['MaxFps'];
        }
        if (isset($map['Maxrate'])) {
            $model->maxrate = $map['Maxrate'];
        }
        if (isset($map['NarrowBand'])) {
            $model->narrowBand = narrowBand::fromMap($map['NarrowBand']);
        }
        if (isset($map['Pad'])) {
            $model->pad = $map['Pad'];
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
        if (isset($map['Remove'])) {
            $model->remove = $map['Remove'];
        }
        if (isset($map['ResoPriority'])) {
            $model->resoPriority = $map['ResoPriority'];
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
