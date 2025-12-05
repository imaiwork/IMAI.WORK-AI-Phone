<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\templateList\template\audio;

use AlibabaCloud\Tea\Model;

class volume extends Model
{
    /**
     * @description The output volume.
     *
     *   This parameter takes effect only if the value of Method is dynamic.
     *   Unit: dB.
     *   Valid values: [-70,-5].
     *   Default value: -6.
     *
     * @example -6
     *
     * @var string
     */
    public $integratedLoudnessTarget;

    /**
     * @description The increased volume relative to the volume of the input audio.
     *
     *   This parameter takes effect only if the value of Method is linear.
     *   Unit: dB.
     *   Valid values: less than or equal to 20.
     *   Default value: -20.
     *
     * @example -20
     *
     * @var string
     */
    public $level;

    /**
     * @description The range of the volume relative to the output volume.
     *
     *   This parameter takes effect only if the value of Method is dynamic.
     *   Unit: dB.
     *   Valid values: [1,20].
     *   Default value: 8.
     *
     * @example 8
     *
     * @var string
     */
    public $loudnessRangeTarget;

    /**
     * @description The method that is used to adjust the volume. Valid values:
     *
     *   **auto**
     *   **dynamic**
     *   **linear**
     *   Default value: dynamic.
     *
     * @example auto
     *
     * @var string
     */
    public $method;

    /**
     * @description The volume adjustment coefficient.
     *
     * Default value: 0.9.
     * @example 0.9
     *
     * @var string
     */
    public $peakLevel;

    /**
     * @description The peak volume.
     *
     *   This parameter takes effect only if the value of Method is dynamic.
     *   Unit: dB.
     *   Valid values: [-9,0].
     *   Default value: -1.
     *
     * @example -1
     *
     * @var string
     */
    public $truePeak;
    protected $_name = [
        'integratedLoudnessTarget' => 'IntegratedLoudnessTarget',
        'level'                    => 'Level',
        'loudnessRangeTarget'      => 'LoudnessRangeTarget',
        'method'                   => 'Method',
        'peakLevel'                => 'PeakLevel',
        'truePeak'                 => 'TruePeak',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->integratedLoudnessTarget) {
            $res['IntegratedLoudnessTarget'] = $this->integratedLoudnessTarget;
        }
        if (null !== $this->level) {
            $res['Level'] = $this->level;
        }
        if (null !== $this->loudnessRangeTarget) {
            $res['LoudnessRangeTarget'] = $this->loudnessRangeTarget;
        }
        if (null !== $this->method) {
            $res['Method'] = $this->method;
        }
        if (null !== $this->peakLevel) {
            $res['PeakLevel'] = $this->peakLevel;
        }
        if (null !== $this->truePeak) {
            $res['TruePeak'] = $this->truePeak;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return volume
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['IntegratedLoudnessTarget'])) {
            $model->integratedLoudnessTarget = $map['IntegratedLoudnessTarget'];
        }
        if (isset($map['Level'])) {
            $model->level = $map['Level'];
        }
        if (isset($map['LoudnessRangeTarget'])) {
            $model->loudnessRangeTarget = $map['LoudnessRangeTarget'];
        }
        if (isset($map['Method'])) {
            $model->method = $map['Method'];
        }
        if (isset($map['PeakLevel'])) {
            $model->peakLevel = $map['PeakLevel'];
        }
        if (isset($map['TruePeak'])) {
            $model->truePeak = $map['TruePeak'];
        }

        return $model;
    }
}
