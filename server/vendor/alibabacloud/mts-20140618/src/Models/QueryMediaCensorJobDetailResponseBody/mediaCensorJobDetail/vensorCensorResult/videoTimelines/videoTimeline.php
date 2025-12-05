<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\vensorCensorResult\videoTimelines;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobDetailResponseBody\mediaCensorJobDetail\vensorCensorResult\videoTimelines\videoTimeline\censorResults;
use AlibabaCloud\Tea\Model;

class videoTimeline extends Model
{
    /**
     * @description The moderation results that include information such as labels and scores.
     *
     * @var censorResults
     */
    public $censorResults;

    /**
     * @description The one or more OSS objects that are generated as the output snapshots.
     *
     * > In the example, {Count} is a placeholder. The OSS objects that are generated as output snapshots are named `output00001-****.jpg`, `output00002-****.jpg`, and so on.
     * @example output{Count}.jpg
     *
     * @var string
     */
    public $object;

    /**
     * @description The position in the video. Format: `hh:mm:ss[.SSS]`.
     *
     * @example 00:02:59.999
     *
     * @var string
     */
    public $timestamp;
    protected $_name = [
        'censorResults' => 'CensorResults',
        'object'        => 'Object',
        'timestamp'     => 'Timestamp',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->censorResults) {
            $res['CensorResults'] = null !== $this->censorResults ? $this->censorResults->toMap() : null;
        }
        if (null !== $this->object) {
            $res['Object'] = $this->object;
        }
        if (null !== $this->timestamp) {
            $res['Timestamp'] = $this->timestamp;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return videoTimeline
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CensorResults'])) {
            $model->censorResults = censorResults::fromMap($map['CensorResults']);
        }
        if (isset($map['Object'])) {
            $model->object = $map['Object'];
        }
        if (isset($map['Timestamp'])) {
            $model->timestamp = $map['Timestamp'];
        }

        return $model;
    }
}
