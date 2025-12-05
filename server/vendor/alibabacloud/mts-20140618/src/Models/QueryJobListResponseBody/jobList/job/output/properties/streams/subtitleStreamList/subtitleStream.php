<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\properties\streams\subtitleStreamList;

use AlibabaCloud\Tea\Model;

class subtitleStream extends Model
{
    /**
     * @description The sequence number of the caption stream. The value indicates the position of the caption stream in all caption streams.
     *
     * @example 1
     *
     * @var string
     */
    public $index;

    /**
     * @description The language of the caption stream. For more information, see [FFmpeg documentation](https://www.ffmpeg.org/ffmpeg-all.html#Metadata) and [ISO 639](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes).
     *
     * @example eng
     *
     * @var string
     */
    public $lang;
    protected $_name = [
        'index' => 'Index',
        'lang'  => 'Lang',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->index) {
            $res['Index'] = $this->index;
        }
        if (null !== $this->lang) {
            $res['Lang'] = $this->lang;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return subtitleStream
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Index'])) {
            $model->index = $map['Index'];
        }
        if (isset($map['Lang'])) {
            $model->lang = $map['Lang'];
        }

        return $model;
    }
}
