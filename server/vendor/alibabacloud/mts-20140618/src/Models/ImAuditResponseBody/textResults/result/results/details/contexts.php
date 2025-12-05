<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\textResults\result\results\details;

use AlibabaCloud\Tea\Model;

class contexts extends Model
{
    /**
     * @description The term that the moderated text hits. If the text hits a term, the term is returned. If the text hits the algorithmic model, this parameter is not returned.
     *
     * @example Door-to-door service
     *
     * @var string
     */
    public $context;

    /**
     * @description The code of the custom text library. This parameter is returned if the moderated text hits a term in the custom text library.
     *
     * @example 123456
     *
     * @var string
     */
    public $libCode;

    /**
     * @description The name of the custom text library. This parameter is returned if the moderated text hits a term in the custom text library.
     *
     * @example Name of your custom text library
     *
     * @var string
     */
    public $libName;

    /**
     * @description The position of the term that the moderated text hits in the original text.
     *
     * @var string[]
     */
    public $positions;

    /**
     * @description The behavior rule. This parameter is returned if the moderated text hits the behavior rule. Valid values:
     *
     *   user_id
     *   ip
     *   umid
     *   content
     *   similar_content
     *   imei
     *   imsi
     *
     * @example ip
     *
     * @var string
     */
    public $ruleType;
    protected $_name = [
        'context'   => 'context',
        'libCode'   => 'libCode',
        'libName'   => 'libName',
        'positions' => 'positions',
        'ruleType'  => 'ruleType',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->context) {
            $res['context'] = $this->context;
        }
        if (null !== $this->libCode) {
            $res['libCode'] = $this->libCode;
        }
        if (null !== $this->libName) {
            $res['libName'] = $this->libName;
        }
        if (null !== $this->positions) {
            $res['positions'] = $this->positions;
        }
        if (null !== $this->ruleType) {
            $res['ruleType'] = $this->ruleType;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return contexts
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['context'])) {
            $model->context = $map['context'];
        }
        if (isset($map['libCode'])) {
            $model->libCode = $map['libCode'];
        }
        if (isset($map['libName'])) {
            $model->libName = $map['libName'];
        }
        if (isset($map['positions'])) {
            if (!empty($map['positions'])) {
                $model->positions = $map['positions'];
            }
        }
        if (isset($map['ruleType'])) {
            $model->ruleType = $map['ruleType'];
        }

        return $model;
    }
}
