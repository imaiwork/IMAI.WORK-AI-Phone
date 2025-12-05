<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\properties\format;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\properties\sourceLogos;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\properties\streams;
use AlibabaCloud\Tea\Model;

class properties extends Model
{
    /**
     * @description The video bitrate.
     *
     * @example 490
     *
     * @var string
     */
    public $bitrate;

    /**
     * @description The video duration.
     *
     * @example 17
     *
     * @var string
     */
    public $duration;

    /**
     * @description The video format.
     *
     * @example mp4
     *
     * @var string
     */
    public $fileFormat;

    /**
     * @description The size of the media file.
     *
     * @example 1057273
     *
     * @var string
     */
    public $fileSize;

    /**
     * @description The format information.
     *
     * @var format
     */
    public $format;

    /**
     * @description The frame rate of the video.
     *
     * @example 30
     *
     * @var string
     */
    public $fps;

    /**
     * @description The video height.
     *
     * @example 1280
     *
     * @var string
     */
    public $height;

    /**
     * @description The non-engine layer keywords.
     *
     * @var sourceLogos
     */
    public $sourceLogos;

    /**
     * @description The stream information.
     *
     * @var streams
     */
    public $streams;

    /**
     * @description The video width.
     *
     * @example 720
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'bitrate'     => 'Bitrate',
        'duration'    => 'Duration',
        'fileFormat'  => 'FileFormat',
        'fileSize'    => 'FileSize',
        'format'      => 'Format',
        'fps'         => 'Fps',
        'height'      => 'Height',
        'sourceLogos' => 'SourceLogos',
        'streams'     => 'Streams',
        'width'       => 'Width',
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
        if (null !== $this->fileFormat) {
            $res['FileFormat'] = $this->fileFormat;
        }
        if (null !== $this->fileSize) {
            $res['FileSize'] = $this->fileSize;
        }
        if (null !== $this->format) {
            $res['Format'] = null !== $this->format ? $this->format->toMap() : null;
        }
        if (null !== $this->fps) {
            $res['Fps'] = $this->fps;
        }
        if (null !== $this->height) {
            $res['Height'] = $this->height;
        }
        if (null !== $this->sourceLogos) {
            $res['SourceLogos'] = null !== $this->sourceLogos ? $this->sourceLogos->toMap() : null;
        }
        if (null !== $this->streams) {
            $res['Streams'] = null !== $this->streams ? $this->streams->toMap() : null;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return properties
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
        if (isset($map['FileFormat'])) {
            $model->fileFormat = $map['FileFormat'];
        }
        if (isset($map['FileSize'])) {
            $model->fileSize = $map['FileSize'];
        }
        if (isset($map['Format'])) {
            $model->format = format::fromMap($map['Format']);
        }
        if (isset($map['Fps'])) {
            $model->fps = $map['Fps'];
        }
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['SourceLogos'])) {
            $model->sourceLogos = sourceLogos::fromMap($map['SourceLogos']);
        }
        if (isset($map['Streams'])) {
            $model->streams = streams::fromMap($map['Streams']);
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }

        return $model;
    }
}
