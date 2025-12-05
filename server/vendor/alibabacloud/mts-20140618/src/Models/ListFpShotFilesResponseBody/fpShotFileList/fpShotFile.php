<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotFilesResponseBody\fpShotFileList;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotFilesResponseBody\fpShotFileList\fpShotFile\inputFile;
use AlibabaCloud\Tea\Model;

class fpShotFile extends Model
{
    /**
     * @description The ID of the video file.
     *
     * @example 41e6536e4f2250e2e9bf26cdea19****
     *
     * @var string
     */
    public $fileId;

    /**
     * @description The information about the input file.
     *
     * @var inputFile
     */
    public $inputFile;

    /**
     * @description The unique primary key of the input video.
     *
     * @example fb712a6890464059b1b2ea7c8647****
     *
     * @var string
     */
    public $primaryKey;

    /**
     * @description The time when the media fingerprint file was stored. The time follows the ISO 8601 standard in the `YYYY-MM-DDThh:mm:ssZ` format. The time is displayed in UTC.
     *
     * > This parameter is available only in the China (Beijing), China (Hangzhou), and China (Shanghai) regions.
     * @example 2022-09-08T23:32:56Z
     *
     * @var string
     */
    public $storeTime;
    protected $_name = [
        'fileId'     => 'FileId',
        'inputFile'  => 'InputFile',
        'primaryKey' => 'PrimaryKey',
        'storeTime'  => 'StoreTime',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fileId) {
            $res['FileId'] = $this->fileId;
        }
        if (null !== $this->inputFile) {
            $res['InputFile'] = null !== $this->inputFile ? $this->inputFile->toMap() : null;
        }
        if (null !== $this->primaryKey) {
            $res['PrimaryKey'] = $this->primaryKey;
        }
        if (null !== $this->storeTime) {
            $res['StoreTime'] = $this->storeTime;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return fpShotFile
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FileId'])) {
            $model->fileId = $map['FileId'];
        }
        if (isset($map['InputFile'])) {
            $model->inputFile = inputFile::fromMap($map['InputFile']);
        }
        if (isset($map['PrimaryKey'])) {
            $model->primaryKey = $map['PrimaryKey'];
        }
        if (isset($map['StoreTime'])) {
            $model->storeTime = $map['StoreTime'];
        }

        return $model;
    }
}
