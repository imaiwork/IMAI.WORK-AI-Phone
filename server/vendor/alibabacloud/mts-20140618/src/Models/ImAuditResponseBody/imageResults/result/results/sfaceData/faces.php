<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result\results\sfaceData;

use AlibabaCloud\Tea\Model;

class faces extends Model
{
    /**
     * @description The ID of the detected face. The value is a string.
     *
     * @example AliFace_0001234
     *
     * @var string
     */
    public $idid;

    /**
     * @description This value is a string, which indicates the name of a similar person.
     *
     * @example Name
     *
     * @var string
     */
    public $name;

    /**
     * @description The score of the confidence level. The value is a float point number. Valid values: 0 to 100. A greater value indicates a higher confidence level for facial recognition.
     *
     * @example 91.54
     *
     * @var float
     */
    public $re;
    protected $_name = [
        'idid' => 'idid',
        'name' => 'name',
        're'   => 're',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->idid) {
            $res['idid'] = $this->idid;
        }
        if (null !== $this->name) {
            $res['name'] = $this->name;
        }
        if (null !== $this->re) {
            $res['re'] = $this->re;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return faces
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['idid'])) {
            $model->idid = $map['idid'];
        }
        if (isset($map['name'])) {
            $model->name = $map['name'];
        }
        if (isset($map['re'])) {
            $model->re = $map['re'];
        }

        return $model;
    }
}
