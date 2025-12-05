<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListAllMediaBucketResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListAllMediaBucketResponseBody\mediaBucketList\mediaBucket;
use AlibabaCloud\Tea\Model;

class mediaBucketList extends Model
{
    /**
     * @var mediaBucket[]
     */
    public $mediaBucket;
    protected $_name = [
        'mediaBucket' => 'MediaBucket',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaBucket) {
            $res['MediaBucket'] = [];
            if (null !== $this->mediaBucket && \is_array($this->mediaBucket)) {
                $n = 0;
                foreach ($this->mediaBucket as $item) {
                    $res['MediaBucket'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return mediaBucketList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaBucket'])) {
            if (!empty($map['MediaBucket'])) {
                $model->mediaBucket = [];
                $n                  = 0;
                foreach ($map['MediaBucket'] as $item) {
                    $model->mediaBucket[$n++] = null !== $item ? mediaBucket::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
