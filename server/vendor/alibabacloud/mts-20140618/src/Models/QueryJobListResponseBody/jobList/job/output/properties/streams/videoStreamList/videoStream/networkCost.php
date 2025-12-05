<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\properties\streams\videoStreamList\videoStream;

use AlibabaCloud\Tea\Model;

class networkCost extends Model
{
    /**
     * @description The average bitrate.
     *
     * @example 300
     *
     * @var string
     */
    public $avgBitrate;

    /**
     * @description The maximum bandwidth that was consumed.
     *
     * @example 10
     *
     * @var string
     */
    public $costBandwidth;

    /**
     * @description The amount of time consumed to preload the video stream.
     *
     * @example 8
     *
     * @var string
     */
    public $preloadTime;
    protected $_name = [
        'avgBitrate'    => 'AvgBitrate',
        'costBandwidth' => 'CostBandwidth',
        'preloadTime'   => 'PreloadTime',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->avgBitrate) {
            $res['AvgBitrate'] = $this->avgBitrate;
        }
        if (null !== $this->costBandwidth) {
            $res['CostBandwidth'] = $this->costBandwidth;
        }
        if (null !== $this->preloadTime) {
            $res['PreloadTime'] = $this->preloadTime;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return networkCost
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AvgBitrate'])) {
            $model->avgBitrate = $map['AvgBitrate'];
        }
        if (isset($map['CostBandwidth'])) {
            $model->costBandwidth = $map['CostBandwidth'];
        }
        if (isset($map['PreloadTime'])) {
            $model->preloadTime = $map['PreloadTime'];
        }

        return $model;
    }
}
