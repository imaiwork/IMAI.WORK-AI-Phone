<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob;

use AlibabaCloud\Tea\Model;

class fpShotConfig extends Model
{
    /**
     * @description The ID of the media fingerprint library.
     *
     * @example 2288c6ca184c0e47098a5b665e2a12****
     *
     * @var string
     */
    public $fpDBId;

    /**
     * @description The unique primary key of the video.
     *
     * @example 3ca84a39a9024f19853b21be9cf9****
     *
     * @var string
     */
    public $primaryKey;

    /**
     * @description The storage type. Valid values:
     *
     *   **nosave**: The fingerprints of the job input are not saved to the media fingerprint library.
     *   **save**: The fingerprints of the job input are saved to the media fingerprint library only if the job input is not duplicated with media content in the media fingerprint library.
     *   **forcesave**: The fingerprints of the job input are forcibly saved to the media fingerprint library.
     *
     * @example save
     *
     * @var string
     */
    public $saveType;
    protected $_name = [
        'fpDBId'     => 'FpDBId',
        'primaryKey' => 'PrimaryKey',
        'saveType'   => 'SaveType',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpDBId) {
            $res['FpDBId'] = $this->fpDBId;
        }
        if (null !== $this->primaryKey) {
            $res['PrimaryKey'] = $this->primaryKey;
        }
        if (null !== $this->saveType) {
            $res['SaveType'] = $this->saveType;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpShotConfig
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpDBId'])) {
            $model->fpDBId = $map['FpDBId'];
        }
        if (isset($map['PrimaryKey'])) {
            $model->primaryKey = $map['PrimaryKey'];
        }
        if (isset($map['SaveType'])) {
            $model->saveType = $map['SaveType'];
        }

        return $model;
    }
}
