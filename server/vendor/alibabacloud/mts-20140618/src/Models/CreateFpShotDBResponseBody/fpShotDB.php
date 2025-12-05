<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\CreateFpShotDBResponseBody;

use AlibabaCloud\Tea\Model;

class fpShotDB extends Model
{
    /**
     * @description The configurations of the media fingerprint library.
     *
     * @example null
     *
     * @var string
     */
    public $config;

    /**
     * @description The description of the media fingerprint library.
     *
     * @example The library is a text fingerprint library.
     *
     * @var string
     */
    public $description;

    /**
     * @description The ID of the media fingerprint library. We recommend that you keep this ID for subsequent operation calls.
     *
     * @example 88c6ca184c0e47098a5b665e2a12****
     *
     * @var string
     */
    public $fpDBId;

    /**
     * @description The model ID of the media fingerprint library.
     *
     * @example 11
     *
     * @var int
     */
    public $modelId;

    /**
     * @description The name of the media fingerprint library.
     *
     * @example example-name-****
     *
     * @var string
     */
    public $name;

    /**
     * @description The status of the media fingerprint library. After the media fingerprint library is created, it enters the **offline** state. After the media fingerprint library is processed at the backend, it enters the **active** state.
     *
     * @example offline
     *
     * @var string
     */
    public $state;
    protected $_name = [
        'config'      => 'Config',
        'description' => 'Description',
        'fpDBId'      => 'FpDBId',
        'modelId'     => 'ModelId',
        'name'        => 'Name',
        'state'       => 'State',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->config) {
            $res['Config'] = $this->config;
        }
        if (null !== $this->description) {
            $res['Description'] = $this->description;
        }
        if (null !== $this->fpDBId) {
            $res['FpDBId'] = $this->fpDBId;
        }
        if (null !== $this->modelId) {
            $res['ModelId'] = $this->modelId;
        }
        if (null !== $this->name) {
            $res['Name'] = $this->name;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpShotDB
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Config'])) {
            $model->config = $map['Config'];
        }
        if (isset($map['Description'])) {
            $model->description = $map['Description'];
        }
        if (isset($map['FpDBId'])) {
            $model->fpDBId = $map['FpDBId'];
        }
        if (isset($map['ModelId'])) {
            $model->modelId = $map['ModelId'];
        }
        if (isset($map['Name'])) {
            $model->name = $map['Name'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }

        return $model;
    }
}
