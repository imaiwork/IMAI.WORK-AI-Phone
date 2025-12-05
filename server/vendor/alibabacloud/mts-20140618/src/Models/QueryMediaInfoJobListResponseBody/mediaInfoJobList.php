<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaInfoJobListResponseBody\mediaInfoJobList\mediaInfoJob;
use AlibabaCloud\Tea\Model;

class mediaInfoJobList extends Model
{
    /**
     * @var mediaInfoJob[]
     */
    public $mediaInfoJob;
    protected $_name = [
        'mediaInfoJob' => 'MediaInfoJob',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaInfoJob) {
            $res['MediaInfoJob'] = [];
            if (null !== $this->mediaInfoJob && \is_array($this->mediaInfoJob)) {
                $n = 0;
                foreach ($this->mediaInfoJob as $item) {
                    $res['MediaInfoJob'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return mediaInfoJobList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaInfoJob'])) {
            if (!empty($map['MediaInfoJob'])) {
                $model->mediaInfoJob = [];
                $n                   = 0;
                foreach ($map['MediaInfoJob'] as $item) {
                    $model->mediaInfoJob[$n++] = null !== $item ? mediaInfoJob::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
