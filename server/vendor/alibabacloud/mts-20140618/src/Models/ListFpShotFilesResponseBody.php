<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListFpShotFilesResponseBody\fpShotFileList;
use AlibabaCloud\Tea\Model;

class ListFpShotFilesResponseBody extends Model
{
    /**
     * @description The media fingerprint files. For more information, see the "FpShotFile" section of the [Data types](https://help.aliyun.com/document_detail/29251.html) topic.
     *
     * @var fpShotFileList
     */
    public $fpShotFileList;

    /**
     * @description The returned value of NextPageToken is a pagination token, which can be used in the next request to retrieve a new page of results.
     *
     * @example ae0fd49c0840e14daf0d66a75b83****
     *
     * @var string
     */
    public $nextPageToken;

    /**
     * @description The ID of the request.
     *
     * @example 25818875-5F78-4A13-BEF6-D7393642CA58
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'fpShotFileList' => 'FpShotFileList',
        'nextPageToken'  => 'NextPageToken',
        'requestId'      => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->fpShotFileList) {
            $res['FpShotFileList'] = null !== $this->fpShotFileList ? $this->fpShotFileList->toMap() : null;
        }
        if (null !== $this->nextPageToken) {
            $res['NextPageToken'] = $this->nextPageToken;
        }
        if (null !== $this->requestId) {
            $res['RequestId'] = $this->requestId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return ListFpShotFilesResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['FpShotFileList'])) {
            $model->fpShotFileList = fpShotFileList::fromMap($map['FpShotFileList']);
        }
        if (isset($map['NextPageToken'])) {
            $model->nextPageToken = $map['NextPageToken'];
        }
        if (isset($map['RequestId'])) {
            $model->requestId = $map['RequestId'];
        }

        return $model;
    }
}
