<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output;

use AlibabaCloud\Tea\Model;

class container extends Model
{
    /**
     * @description The container format.
     *
     *   Default value: mp4.
     *   Video formats include FLV, MP4, HLS (M3U8 + TS), and MPEG-DASH (MPD + fMP4).
     *   Audio formats include MP3, MP4, Ogg, FLAC, and M4A.
     *   Image formats include GIF and WebP.
     *   If the container format is GIF, the video codec must be GIF.
     *   If the container format is WebP, the video codec must be WebP.
     *   If the container format is FLV, the video codec cannot be H.265.
     *
     * @example flv
     *
     * @var string
     */
    public $format;
    protected $_name = [
        'format' => 'Format',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->format) {
            $res['Format'] = $this->format;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return container
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Format'])) {
            $model->format = $map['Format'];
        }

        return $model;
    }
}
