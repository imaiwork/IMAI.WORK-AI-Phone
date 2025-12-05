<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SearchWaterMarkTemplateResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\SearchWaterMarkTemplateResponseBody\waterMarkTemplateList\waterMarkTemplate;
use AlibabaCloud\Tea\Model;

class waterMarkTemplateList extends Model
{
    /**
     * @var waterMarkTemplate[]
     */
    public $waterMarkTemplate;
    protected $_name = [
        'waterMarkTemplate' => 'WaterMarkTemplate',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->waterMarkTemplate) {
            $res['WaterMarkTemplate'] = [];
            if (null !== $this->waterMarkTemplate && \is_array($this->waterMarkTemplate)) {
                $n = 0;
                foreach ($this->waterMarkTemplate as $item) {
                    $res['WaterMarkTemplate'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return waterMarkTemplateList
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['WaterMarkTemplate'])) {
            if (!empty($map['WaterMarkTemplate'])) {
                $model->waterMarkTemplate = [];
                $n                        = 0;
                foreach ($map['WaterMarkTemplate'] as $item) {
                    $model->waterMarkTemplate[$n++] = null !== $item ? waterMarkTemplate::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
