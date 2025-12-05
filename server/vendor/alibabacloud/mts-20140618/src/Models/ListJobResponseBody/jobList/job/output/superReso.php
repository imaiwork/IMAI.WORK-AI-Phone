<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output;

use AlibabaCloud\Tea\Model;

class superReso extends Model
{
    /**
     * @description Indicates whether parameters related to the sampling rate are obtained. Valid values:
     *
     *   **true**
     *   **false**
     *
     * @example true
     *
     * @var string
     */
    public $isHalfSample;
    protected $_name = [
        'isHalfSample' => 'IsHalfSample',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->isHalfSample) {
            $res['IsHalfSample'] = $this->isHalfSample;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return superReso
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['IsHalfSample'])) {
            $model->isHalfSample = $map['IsHalfSample'];
        }

        return $model;
    }
}
