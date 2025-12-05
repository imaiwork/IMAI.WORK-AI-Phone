<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySnapshotJobListResponseBody\snapshotJobList\snapshotJob;
use AlibabaCloud\Tea\Model;

class snapshotJobList extends Model
{
    /**
     * @var snapshotJob[]
     */
    public $snapshotJob;
    protected $_name = [
        'snapshotJob' => 'SnapshotJob',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->snapshotJob) {
            $res['SnapshotJob'] = [];
            if (null !== $this->snapshotJob && \is_array($this->snapshotJob)) {
                $n = 0;
                foreach ($this->snapshotJob as $item) {
                    $res['SnapshotJob'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return snapshotJobList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['SnapshotJob'])) {
            if (!empty($map['SnapshotJob'])) {
                $model->snapshotJob = [];
                $n                  = 0;
                foreach ($map['SnapshotJob'] as $item) {
                    $model->snapshotJob[$n++] = null !== $item ? snapshotJob::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
