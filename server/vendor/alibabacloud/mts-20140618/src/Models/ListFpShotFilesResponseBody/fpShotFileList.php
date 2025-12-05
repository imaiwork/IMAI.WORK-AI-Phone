<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotFilesResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotFilesResponseBody\fpShotFileList\fpShotFile;
use AlibabaCloud\Tea\Model;

class fpShotFileList extends Model
{
    /**
     * @var fpShotFile[]
     */
    public $fpShotFile;
    protected $_name = [
        'fpShotFile' => 'FpShotFile',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpShotFile) {
            $res['FpShotFile'] = [];
            if (null !== $this->fpShotFile && \is_array($this->fpShotFile)) {
                $n = 0;
                foreach ($this->fpShotFile as $item) {
                    $res['FpShotFile'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpShotFileList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpShotFile'])) {
            if (!empty($map['FpShotFile'])) {
                $model->fpShotFile = [];
                $n                 = 0;
                foreach ($map['FpShotFile'] as $item) {
                    $model->fpShotFile[$n++] = null !== $item ? fpShotFile::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
