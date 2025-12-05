<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponseBody\template\audio;

use AlibabaCloud\Tea\Model;

class volume extends Model
{
    /**
     * @description The output volume.
     *
     * Default value: -6.
     * @example -6
     *
     * @var string
     */
    public $integratedLoudnessTarget;

    /**
     * @description The volume adjustment range.
     *
     *   Default value: **-20**.
     *   Unit: dB.
     *
     * @example -20
     *
     * @var string
     */
    public $level;

    /**
     * @description The range of the volume relative to the output volume.
     *
     * Default value: 8.
     * @example 8
     *
     * @var string
     */
    public $loudnessRangeTarget;

    /**
     * @description The volume adjustment method. Valid values:
     *
     *   **auto**: The volume is automatically adjusted.
     *   **dynamic**: The volume is dynamically adjusted.
     *   **linear**: The volume is linearly adjusted.
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
     * Default value: -1.
     * @example 0
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
