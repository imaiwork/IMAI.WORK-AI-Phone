<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryTraceExtractJobResponseBody;

use AlibabaCloud\Tea\Model;

class data extends Model
{
    /**
     * @example example water mark
     *
     * @var string
     */
    public $trace;
    protected $_name = [
        'trace' => 'Trace',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->trace) {
            $res['Trace'] = $this->trace;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return data
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Trace'])) {
            $model->trace = $map['Trace'];
        }

        return $model;
    }
}
