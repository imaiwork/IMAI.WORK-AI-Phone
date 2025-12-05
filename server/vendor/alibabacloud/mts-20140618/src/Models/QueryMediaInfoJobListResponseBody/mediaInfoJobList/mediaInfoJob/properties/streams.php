<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList\mediaInfoJob\properties;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList\mediaInfoJob\properties\streams\audioStreamList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList\mediaInfoJob\properties\streams\subtitleStreamList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList\mediaInfoJob\properties\streams\videoStreamList;
use AlibabaCloud\Tea\Model;

class streams extends Model
{
    /**
     * @description The information about each audio stream.
     *
     * @var audioStreamList
     */
    public $audioStreamList;

    /**
     * @description The information about each subtitle stream.
     *
     * @var subtitleStreamList
     */
    public $subtitleStreamList;

    /**
     * @description The information about each video stream.
     *
     * @var videoStreamList
     */
    public $videoStreamList;
    protected $_name = [
        'audioStreamList'    => 'AudioStreamList',
        'subtitleStreamList' => 'SubtitleStreamList',
        'videoStreamList'    => 'VideoStreamList',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->audioStreamList) {
            $res['AudioStreamList'] = null !== $this->audioStreamList ? $this->audioStreamList->toMap() : null;
        }
        if (null !== $this->subtitleStreamList) {
            $res['SubtitleStreamList'] = null !== $this->subtitleStreamList ? $this->subtitleStreamList->toMap() : null;
        }
        if (null !== $this->videoStreamList) {
            $res['VideoStreamList'] = null !== $this->videoStreamList ? $this->videoStreamList->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return streams
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AudioStreamList'])) {
            $model->audioStreamList = audioStreamList::fromMap($map['AudioStreamList']);
        }
        if (isset($map['SubtitleStreamList'])) {
            $model->subtitleStreamList = subtitleStreamList::fromMap($map['SubtitleStreamList']);
        }
        if (isset($map['VideoStreamList'])) {
            $model->videoStreamList = videoStreamList::fromMap($map['VideoStreamList']);
        }

        return $model;
    }
}
