<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\video\bitrateBnd;
use AlibabaCloud\Tea\Model;

class video extends Model
{
    /**
     * @description The bitrate of the output video. Unit: Kbit/s.
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
     * @example 1000
     *
     * @var string
     */
    public $bufsize;

    /**
     * @description The video codec.
     *
     *   Valid values: **H.264**, **H.265**, **GIF**, and **WEBP**.
     *   Default value: **H.264**.
     *
     * @example H.264
     *
     * @var string
     */
    public $codec;

    /**
     * @description The constant rate factor.
     *
     *   If **Crf** is returned, the value of **Bitrate** is invalid.
     *   Default value if the value of Codec is H.264: **23**. Default value if the value of Codec is H.265: **26**.
     *
     * @example 22
     *
     * @var string
     */
    public $crf;

    /**
     * @description The method of video cropping. Valid values:
     *
     *   **border**: Black borders are automatically detected and removed.
     *   A value in the format of width:height:left:top: The video is cropped based on the custom settings.
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
     * @description The frame rate.
     *
     *   Unit: frames per second.
     *   Valid values: 0 to 60. The value is 60 if the frame rate of the input file exceeds 60.
     *   Default value: the frame rate of the input file.
     *
     * @example 60
     *
     * @var string
     */
    public $fps;

    /**
     * @description The maximum interval between keyframes or the maximum number of frames in a frame group. Unit: seconds.
     *
     *   Default value: 10.
     *   If the maximum number of frames is returned, the value does not have a unit.
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
     * @example 1280
     *
     * @var string
     */
    public $height;

    /**
     * @description The maximum frame rate.
     *
     * @example 15
     *
     * @var string
     */
    public $maxFps;

    /**
     * @description The maximum bitrate of the video. Unit: Kbit/s.
     *
     * @example 10
     *
     * @var string
     */
    public $maxrate;

    /**
     * @description The black borders that are added to the video.
     *
     *   The value is in the width:height:left:top format.
     *   Unit: pixel.
     *
     * @example 1280:800:0:140
     *
     * @var string
     */
    public $pad;

    /**
     * @description The pixel format of the video.
     *
     *   The default pixel format can be **yuv420p** or the pixel format of the input file.
     *
     *   Valid values: standard pixel formats such as **yuv420p** and **yuvj420p**.
     *
     **
     *
     **Note** If a non-standard pixel format such as yuvj420p(pc, bt470bg/bt470bg/smpte170m) is used, compatibility with the pixel format must be configured. Otherwise, the transcoding job fails.
     *
     * @example yuvj420p
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
     * @example veryfast
     *
     * @var string
     */
    public $preset;

    /**
     * @description The encoding profile. This parameter is returned only for the H.264 codec. Default value: **high**. Valid values:
     *
     * >  If multiple definitions are involved, we recommend that you use baseline for the lowest definition to ensure normal playback on low-definition devices, and use main or high for other definitions.
     *
     *   **baseline**: applicable to mobile devices.
     *   **main**: applicable to standard-definition devices.
     *   **high**: applicable to high-definition devices.
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
     * @description The priority of the resource.
     *
     * @example 1
     *
     * @var string
     */
    public $resoPriority;

    /**
     * @description The scan mode. Valid values:
     *
     *   If this parameter is **empty**, the scan mode of the input file is used.
     *   **auto**: automatic deinterlacing.
     *   **progressive**: progressive scan.
     *   **interlaced**: interlaced scan.
     *   **By default**, this parameter is empty.
     *
     **Best practice**: Interlaced scan consumes less bandwidth than progressive scan, but the image quality is poor. Therefore, mainstream video production uses progressive scan.
     *
     *   If **progressive scan** or **interlaced scan** is used when the scan mode of the input file is neither of them, the transcoding job fails.
     *   We recommend that you use **the scan mode of the input file** or **automatic deinterlacing** to improve compatibility.
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
     *   Default value: **the width of the input video**.
     *
     * @example 1080
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
