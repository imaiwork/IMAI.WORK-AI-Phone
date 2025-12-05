<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListByURLResponseBody\mediaList\media\mediaInfo\streams\subtitleStreamList;

use AlibabaCloud\Tea\Model;

class subtitleStream extends Model
{
    /**
     * @description The sequence number of the subtitle stream. The value indicates the position of the subtitle stream in all subtitle streams.
     *
     * @example 1
     *
     * @var string
     */
    public $index;

    /**
     * @description The language. For more information, see [FFmpeg language definition](https://www.ffmpeg.org/ffmpeg-all.html#Metadata).
     *
     * @example und
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
