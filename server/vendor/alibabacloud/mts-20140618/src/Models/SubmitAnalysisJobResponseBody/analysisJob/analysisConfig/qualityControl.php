<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitAnalysisJobResponseBody\analysisJob\analysisConfig;

use AlibabaCloud\Tea\Model;

class qualityControl extends Model
{
    /**
     * @description The playback mode. Valid values:
     *
     *   **network**: online playback
     *   **local**: playback on local devices
     *   Default value: **network**.
     *
     * @example network
     *
     * @var string
     */
    public $methodStreaming;

    /**
     * @description The quality level of the output file.
     *
     * @example 50
     *
     * @var string
     */
    public $rateQuality;
    protected $_name = [
        'methodStreaming' => 'MethodStreaming',
        'rateQuality'     => 'RateQuality',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->methodStreaming) {
            $res['MethodStreaming'] = $this->methodStreaming;
        }
        if (null !== $this->rateQuality) {
            $res['RateQuality'] = $this->rateQuality;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return qualityControl
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MethodStreaming'])) {
            $model->methodStreaming = $map['MethodStreaming'];
        }
        if (isset($map['RateQuality'])) {
            $model->rateQuality = $map['RateQuality'];
        }

        return $model;
    }
}
