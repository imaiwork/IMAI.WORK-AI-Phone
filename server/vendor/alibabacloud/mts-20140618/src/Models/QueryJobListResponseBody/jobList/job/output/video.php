<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\video\bitrateBnd;
use AlibabaCloud\Tea\Model;

class video extends Model
{
    /**
     * @description The average bitrate of the video. Unit: Kbit/s.
     *
     * @example 500
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
     * @description The buffer size.
     *
     *   Unit: KB.
     *   Default value: **6000**.
     *
     * @example 6000
     *
     * @var string
     */
    public $bufsize;

    /**
     * @description The video codec.
     *
     *   Valid values: H.264 and H.265.
     *   Default value: H.264.
     *
     * @example H.264
     *
     * @var string
     */
    public $codec;

    /**
     * @description The constant rate factor.
     *
     *   Default value when the value of Codec is H.264: **23**, default value when the value of Codec is H.265: **26**.
     *   If the value of this parameter is returned, the value of Bitrate becomes invalid.
     *
     * @example 26
     *
     * @var string
     */
    public $crf;

    /**
     * @description The method of video cropping. Valid values:
     *
     *   **border**: automatically detects and removes borders.
     *   A value in the width:height:left:top format: The video image is cropped based on custom settings.
     *
     * @example 1280:800:0:140
     *
     * @var string
     */
    public $crop;

    /**
     * @description The strength of the independent noise reduction algorithm.
     *
     * @example 5
     *
     * @var string
     */
    public $degrain;

    /**
     * @description The frame rate of the video.
     *
     *   Unit: frames per second.
     *   The value is 60 if the frame rate of the input file exceeds 60.
     *   Default value: the frame rate of the input video.
     *
     * @example 25
     *
     * @var string
     */
    public $fps;

    /**
     * @description The maximum interval between keyframes or the maximum number of frames in a frame group. Unit: seconds.
     *
     *   Default value: **250**.
     *   If the maximum number of frames is returned, the value does not contain a unit.
     *
     * @example 250
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
     * @example 720
     *
     * @var string
     */
    public $height;

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
     * @example 3000
     *
     * @var string
     */
    public $maxrate;

    /**
     * @description The black bars that are added to the video.
     *
     *   Unit: pixel.
     *   Format: width:height:left:top.
     *
     * @example 1280:800:0:140
     *
     * @var string
     */
    public $pad;

    /**
     * @description The pixel format of the video. Valid values: standard pixel formats such as yuv420p and yuvj420p.
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
     * @example medium
     *
     * @var string
     */
    public $preset;

    /**
     * @description The codec profile of the video. Valid values: baseline, main, and high.
     *
     * >  If multiple definitions are involved, we recommend that you use baseline for the lowest definition to ensure normal playback on low-end devices, and use main or high for other definitions.
     *
     *   **baseline**: applicable to mobile devices.
     *   **main**: applicable to standard-definition devices.
     *   **high**: applicable to high-definition devices.
     *   Default value: **high**.
     *
     * @example high
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
     * @description The resource priority.
     *
     * @example 1
     *
     * @var string
     */
    public $resoPriority;

    /**
     * @description The scan mode. Valid values:
     *
     *   If this parameter is left **empty**, the scan mode of the input video is used.
     *   **auto**: automatic deinterlacing.
     *   **progressive**: progressive scan.
     *   **interlaced**: interlaced scan.
     *   **By default**, this parameter is left empty.
     *
     **Best practice**: The interlaced scan mode saves data traffic than the progressive scan mode but provides poor image quality. Therefore, the progressive scan mode is commonly used in mainstream video production.
     *
     *   If **progressive** or **interlaced** is used when the scan mode of the input video is neither of them, the transcoding job fails.
     *   We recommend that you use **the scan mode of the input video** or **automatic deinterlacing** for higher compatibility.
     *
     * @example interlaced
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
     * @example 1280
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'bitrate'      => 'Bitrate',
        'bitrateBnd'   => 'BitrateBnd',
        'bufsize'      => 'Bufsize',
        'codec'        => 'Codec',
        'crf'          => 'Crf',
        'crop'         => 'Crop',
        'degrain'      => 'Degrain',
        'fps'          => 'Fps',
        'gop'          => 'Gop',
        'height'       => 'Height',
        'maxFps'       => 'MaxFps',
        'maxrate'      => 'Maxrate',
        'pad'          => 'Pad',
        'pixFmt'       => 'PixFmt',
        'preset'       => 'Preset',
        'profile'      => 'Profile',
        'qscale'       => 'Qscale',
        'resoPriority' => 'ResoPriority',
        'scanMode'     => 'ScanMode',
        'width'        => 'Width',
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
        if (null !== $this->height) {
            $res['Height'] = $this->height;
        }
        if (null !== $this->maxFps) {
            $res['MaxFps'] = $this->maxFps;
        }
        if (null !== $this->maxrate) {
            $res['Maxrate'] = $this->maxrate;
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
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['MaxFps'])) {
            $model->maxFps = $map['MaxFps'];
        }
        if (isset($map['Maxrate'])) {
            $model->maxrate = $map['Maxrate'];
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
