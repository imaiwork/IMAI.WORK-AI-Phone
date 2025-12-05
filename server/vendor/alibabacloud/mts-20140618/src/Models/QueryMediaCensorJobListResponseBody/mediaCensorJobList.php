<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobListResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaCensorJobListResponseBody\mediaCensorJobList\mediaCensorJob;
use AlibabaCloud\Tea\Model;

class mediaCensorJobList extends Model
{
    /**
     * @var mediaCensorJob[]
     */
    public $mediaCensorJob;
    protected $_name = [
        'mediaCensorJob' => 'MediaCensorJob',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaCensorJob) {
            $res['MediaCensorJob'] = [];
            if (null !== $this->mediaCensorJob && \is_array($this->mediaCensorJob)) {
                $n = 0;
                foreach ($this->mediaCensorJob as $item) {
                    $res['MediaCensorJob'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return mediaCensorJobList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaCensorJob'])) {
            if (!empty($map['MediaCensorJob'])) {
                $model->mediaCensorJob = [];
                $n                     = 0;
                foreach ($map['MediaCensorJob'] as $item) {
                    $model->mediaCensorJob[$n++] = null !== $item ? mediaCensorJob::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
