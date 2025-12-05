<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListByURLResponseBody;

use AlibabaCloud\Tea\Model;

class nonExistFileURLs extends Model
{
    /**
     * @var string[]
     */
    public $fileURL;
    protected $_name = [
        'fileURL' => 'FileURL',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fileURL) {
            $res['FileURL'] = $this->fileURL;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return nonExistFileURLs
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FileURL'])) {
            if (!empty($map['FileURL'])) {
                $model->fileURL = $map['FileURL'];
            }
        }

        return $model;
    }
}
