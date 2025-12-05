<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\audio;

use AlibabaCloud\Tea\Model;

class volume extends Model
{
    /**
     * @description The volume adjustment range.
     *
     *   Unit: decibel.
     *   Default value: **-20**.
     *
     * @example -20
     *
     * @var string
     */
    public $level;

    /**
     * @description The method that is used to adjust the volume. Valid values:
     *
     *   **auto**
     *   **dynamic**
     *   **linear**
     *
     * @example auto
     *
     * @var string
     */
    public $method;
    protected $_name = [
        'level'  => 'Level',
        'method' => 'Method',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->level) {
            $res['Level'] = $this->level;
        }
        if (null !== $this->method) {
            $res['Method'] = $this->method;
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
        if (isset($map['Level'])) {
            $model->level = $map['Level'];
        }
        if (isset($map['Method'])) {
            $model->method = $map['Method'];
        }

        return $model;
    }
}
