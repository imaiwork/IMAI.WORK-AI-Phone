<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListResponseBody\mediaList\media;

use AlibabaCloud\Tea\Model;

class file extends Model
{
    /**
     * @description The status of the input file. Valid values:
     *
     *   **Normal**: normal
     *   **Deleted**: deleted
     *
     * @example Normal
     *
     * @var string
     */
    public $state;

    /**
     * @description The OSS URL of the input file.
     *
     * @example http://example-bucket-****.oss-cn-hangzhou.aliyuncs.com//example-****.mp4
     *
     * @var string
     */
    public $URL;
    protected $_name = [
        'state' => 'State',
        'URL'   => 'URL',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }
        if (null !== $this->URL) {
            $res['URL'] = $this->URL;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return file
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }
        if (isset($map['URL'])) {
            $model->URL = $map['URL'];
        }

        return $model;
    }
}
