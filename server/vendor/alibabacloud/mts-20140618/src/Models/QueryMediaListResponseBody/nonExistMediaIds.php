<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListResponseBody;

use AlibabaCloud\Tea\Model;

class nonExistMediaIds extends Model
{
    /**
     * @var string[]
     */
    public $mediaId;
    protected $_name = [
        'mediaId' => 'MediaId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaId) {
            $res['MediaId'] = $this->mediaId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return nonExistMediaIds
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaId'])) {
            if (!empty($map['MediaId'])) {
                $model->mediaId = $map['MediaId'];
            }
        }

        return $model;
    }
}
