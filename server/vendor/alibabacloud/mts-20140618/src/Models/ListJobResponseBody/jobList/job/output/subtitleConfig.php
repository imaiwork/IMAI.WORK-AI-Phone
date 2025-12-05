<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\subtitleConfig\extSubtitleList;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\subtitleConfig\subtitleList;
use AlibabaCloud\Tea\Model;

class subtitleConfig extends Model
{
    /**
     * @description The external subtitles. The value is a JSON array.
     *
     * @var extSubtitleList
     */
    public $extSubtitleList;

    /**
     * @description The subtitles.
     *
     * @var subtitleList
     */
    public $subtitleList;
    protected $_name = [
        'extSubtitleList' => 'ExtSubtitleList',
        'subtitleList'    => 'SubtitleList',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->extSubtitleList) {
            $res['ExtSubtitleList'] = null !== $this->extSubtitleList ? $this->extSubtitleList->toMap() : null;
        }
        if (null !== $this->subtitleList) {
            $res['SubtitleList'] = null !== $this->subtitleList ? $this->subtitleList->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return subtitleConfig
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['ExtSubtitleList'])) {
            $model->extSubtitleList = extSubtitleList::fromMap($map['ExtSubtitleList']);
        }
        if (isset($map['SubtitleList'])) {
            $model->subtitleList = subtitleList::fromMap($map['SubtitleList']);
        }

        return $model;
    }
}
