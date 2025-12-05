<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QuerySmarttagTemplateListResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\QuerySmarttagTemplateListResponseBody\templates\template;
use AlibabaCloud\Tea\Model;

class templates extends Model
{
    /**
     * @var template[]
     */
    public $template;
    protected $_name = [
        'template' => 'Template',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->template) {
            $res['Template'] = [];
            if (null !== $this->template && \is_array($this->template)) {
                $n = 0;
                foreach ($this->template as $item) {
                    $res['Template'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return templates
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Template'])) {
            if (!empty($map['Template'])) {
                $model->template = [];
                $n               = 0;
                foreach ($map['Template'] as $item) {
                    $model->template[$n++] = null !== $item ? template::fromMap($item) : $item;
                }
            }
        }

        return $model;
    }
}
