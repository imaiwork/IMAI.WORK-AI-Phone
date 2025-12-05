<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\templateList\template;

use AlibabaCloud\Tea\Model;

class container extends Model
{
    /**
     * @description The format of the container. Valid values: flv, mp4, ts, m3u8, gif, mp3, ogg, and flac.
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
