<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\textResults;

use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\textResults\result\results;
use AlibabaCloud\Tea\Model;

class result extends Model
{
    /**
     * @description The error code. The error code is the same as the HTTP status code. For more information, see [Error codes](https://help.aliyun.com/document_detail/29254.html).
     *
     * @example 200
     *
     * @var int
     */
    public $code;

    /**
     * @description The text that you specify in the moderation request.
     *
     * @example This is test text.
     *
     * @var string
     */
    public $content;

    /**
     * @description The sequence number of the text.
     *
     * @example cfd33235-71a4-468b-8137-a5ffe323****
     *
     * @var string
     */
    public $dataId;

    /**
     * @description The message that is returned for the request.
     *
     * @example OK
     *
     * @var string
     */
    public $msg;

    /**
     * @description The returned data. If the HTTP status code 200 is returned, the array in the returned results contains one or more elements. Each element is a struct.
     *
     * @var results[]
     */
    public $results;

    /**
     * @description The ID of the moderation task.
     *
     * @example txt6HB8NQoEbU@5fosnj2xVEM-1t****
     *
     * @var string
     */
    public $taskId;
    protected $_name = [
        'code'    => 'code',
        'content' => 'content',
        'dataId'  => 'dataId',
        'msg'     => 'msg',
        'results' => 'results',
        'taskId'  => 'taskId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->code) {
            $res['code'] = $this->code;
        }
        if (null !== $this->content) {
            $res['content'] = $this->content;
        }
        if (null !== $this->dataId) {
            $res['dataId'] = $this->dataId;
        }
        if (null !== $this->msg) {
            $res['msg'] = $this->msg;
        }
        if (null !== $this->results) {
            $res['results'] = [];
            if (null !== $this->results && \is_array($this->results)) {
                $n = 0;
                foreach ($this->results as $item) {
                    $res['results'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }
        if (null !== $this->taskId) {
            $res['taskId'] = $this->taskId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return result
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['code'])) {
            $model->code = $map['code'];
        }
        if (isset($map['content'])) {
            $model->content = $map['content'];
        }
        if (isset($map['dataId'])) {
            $model->dataId = $map['dataId'];
        }
        if (isset($map['msg'])) {
            $model->msg = $map['msg'];
        }
        if (isset($map['results'])) {
            if (!empty($map['results'])) {
                $model->results = [];
                $n              = 0;
                foreach ($map['results'] as $item) {
                    $model->results[$n++] = null !== $item ? results::fromMap($item) : $item;
                }
            }
        }
        if (isset($map['taskId'])) {
            $model->taskId = $map['taskId'];
        }

        return $model;
    }
}
