<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob\snapshotConfig;

use AlibabaCloud\Tea\Model;

class timeArray extends Model
{
    /**
     * @var int[]
     */
    public $timePointList;
    protected $_name = [
        'timePointList' => 'TimePointList',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->timePointList) {
            $res['TimePointList'] = $this->timePointList;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return timeArray
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['TimePointList'])) {
            if (!empty($map['TimePointList'])) {
                $model->timePointList = $map['TimePointList'];
            }
        }

        return $model;
    }
}
