<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template\video;

use AlibabaCloud\Tea\Model;

class narrowBand extends Model
{
    /**
     * @description The upper limit of the dynamic bitrate. If this parameter is set, the average bitrate is in the range of (0, 1000000].
     *
     * @example 3000
     *
     * @var float
     */
    public $abrmax;

    /**
     * @description The maximum ratio of the upper limit of dynamic bitrate. If this parameter is set, the value of Abrmax does not exceed x times of the source video bitrate. Valid values: (0,1.0].
     *
     * @example 1.0
     *
     * @var float
     */
    public $maxAbrRatio;

    /**
     * @description The Narrowband HD version. Only 1.0 may be returned.
     *
     * @example 1.0
     *
     * @var string
     */
    public $version;
    protected $_name = [
        'abrmax'      => 'Abrmax',
        'maxAbrRatio' => 'MaxAbrRatio',
        'version'     => 'Version',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->abrmax) {
            $res['Abrmax'] = $this->abrmax;
        }
        if (null !== $this->maxAbrRatio) {
            $res['MaxAbrRatio'] = $this->maxAbrRatio;
        }
        if (null !== $this->version) {
            $res['Version'] = $this->version;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return narrowBand
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Abrmax'])) {
            $model->abrmax = $map['Abrmax'];
        }
        if (isset($map['MaxAbrRatio'])) {
            $model->maxAbrRatio = $map['MaxAbrRatio'];
        }
        if (isset($map['Version'])) {
            $model->version = $map['Version'];
        }

        return $model;
    }
}
