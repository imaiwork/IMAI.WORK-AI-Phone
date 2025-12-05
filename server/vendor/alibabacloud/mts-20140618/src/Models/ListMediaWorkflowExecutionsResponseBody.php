<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListMediaWorkflowExecutionsResponseBody\mediaWorkflowExecutionList;
use AlibabaCloud\Tea\Model;

class ListMediaWorkflowExecutionsResponseBody extends Model
{
    /**
     * @description The details of the media workflows.
     *
     * @var mediaWorkflowExecutionList
     */
    public $mediaWorkflowExecutionList;

    /**
     * @description The returned value of NextPageToken is a pagination token, which can be used in the next request to retrieve a new page of results.
     *
     * @example 39f8e0bc005e4f309379701645f4****
     *
     * @var string
     */
    public $nextPageToken;

    /**
     * @description The ID of the request.
     *
     * @example D1D5C080-8E2F-5030-8AB4-13092F17631B
     *
     * @var string
     */
    public $requestId;
    protected $_name = [
        'mediaWorkflowExecutionList' => 'MediaWorkflowExecutionList',
        'nextPageToken'              => 'NextPageToken',
        'requestId'                  => 'RequestId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mediaWorkflowExecutionList) {
            $res['MediaWorkflowExecutionList'] = null !== $this->mediaWorkflowExecutionList ? $this->mediaWorkflowExecutionList->toMap() : null;
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
     * @return ListMediaWorkflowExecutionsResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MediaWorkflowExecutionList'])) {
            $model->mediaWorkflowExecutionList = mediaWorkflowExecutionList::fromMap($map['MediaWorkflowExecutionList']);
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
