<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\subtitleConfig\subtitleList;

use AlibabaCloud\Tea\Model;

class subtitle extends Model
{
    /**
     * @description The sequence number of the video stream. The sequence number is the index of the video stream in the list and starts from 0. If you do not set the corresponding parameter in the request, the default video stream is selected.
     *
     * @example 0
     *
     * @var string
     */
    public $map;
    protected $_name = [
        'map' => 'Map',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->map) {
            $res['Map'] = $this->map;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return subtitle
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Map'])) {
            $model->map = $map['Map'];
        }

        return $model;
    }
}
