<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\properties;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\properties\sourceLogos\sourceLogo;
use AlibabaCloud\Tea\Model;

class sourceLogos extends Model
{
    /**
     * @var sourceLogo[]
     */
    public $sourceLogo;
    protected $_name = [
        'sourceLogo' => 'SourceLogo',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->sourceLogo) {
            $res['SourceLogo'] = [];
            if (null !== $this->sourceLogo && \is_array($this->sourceLogo)) {
                $n = 0;
                foreach ($this->sourceLogo as $item) {
                    $res['SourceLogo'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return sourceLogos
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['SourceLogo'])) {
            if (!empty($map['SourceLogo'])) {
                $model->sourceLogo = [];
                $n                 = 0;
                foreach ($map['SourceLogo'] as $item) {
                    $model->sourceLogo[$n++] = null !== $item ? sourceLogo::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
