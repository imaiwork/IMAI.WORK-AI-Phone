<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotDBResponseBody\fpShotDBList;

use AlibabaCloud\Tea\Model;

class fpShotDB extends Model
{
    /**
     * @description The description of the media fingerprint library.
     *
     * @example The library is a text fingerprint library.
     *
     * @var string
     */
    public $description;

    /**
     * @description The ID of the media fingerprint library.
     *
     * @example 88c6ca184c0e47098a5b665e2a12****
     *
     * @var string
     */
    public $fpDBId;

    /**
     * @description The model ID of the media fingerprint library. A value of **11** indicates that the library is a text fingerprint library. A value of **12** indicates that the library is a video fingerprint library. A value of **13** indicates that the library is an audio fingerprint library. A value of **14** indicates that the library is an image fingerprint library.
     *
     * @example 11
     *
     * @var int
     */
    public $modelId;

    /**
     * @description The name of the media fingerprint library.
     *
     * @example test-****
     *
     * @var string
     */
    public $name;

    /**
     * @description The status of the media fingerprint library. Default value: **offline**. ****Valid values:
     *
     *   **offline**: The media fingerprint library is offline.
     *   **active**: The media fingerprint library is online.
     *   **paused**: The media fingerprint library is paused.
     *   **deleted**: The media fingerprint library is deleted.
     *
     * @example active
     *
     * @var string
     */
    public $status;
    protected $_name = [
        'description' => 'Description',
        'fpDBId'      => 'FpDBId',
        'modelId'     => 'ModelId',
        'name'        => 'Name',
        'status'      => 'Status',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
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
        if (null !== $this->status) {
            $res['Status'] = $this->status;
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
        if (isset($map['Status'])) {
            $model->status = $map['Status'];
        }

        return $model;
    }
}
