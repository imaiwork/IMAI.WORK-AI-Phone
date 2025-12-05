<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults;

use AlibabaCloud\SDK\Mts\V20140618\Models\ImAuditResponseBody\imageResults\result\results;
use AlibabaCloud\Tea\Model;

class result extends Model
{
    /**
     * @description The error code. The error code is the same as the HTTP status code. This parameter is not returned if the request is successful.
     *
     * @example 200
     *
     * @var int
     */
    public $code;

    /**
     * @description The ID of the moderated object.
     *
     * >  If you set the dataId parameter in the moderation request, the dataId parameter is returned in the response.
     * @example uuid-1234-1234-1234
     *
     * @var string
     */
    public $dataId;

    /**
     * @description The additional information about the image. If ad is specified for the Scenes parameter, the following content may be returned for this parameter: hitLibInfo: the information about the custom text library that is hit by the text in the image. The value of this parameter is an array. For more information about the structure, see [hitLibInfo](https://help.aliyun.com/document_detail/268644.html).
     *
     * @var mixed[]
     */
    public $extras;

    /**
     * @description The message that is returned for the request.
     *
     * @example ok
     *
     * @var string
     */
    public $msg;

    /**
     * @description The returned data. If the call is successful, the array in the returned results contains one or more elements. Each element is a struct.
     *
     * @var results[]
     */
    public $results;

    /**
     * @description The ID of the moderation task.
     *
     * @example img4wlJcb7p4wH4lAP3111111-12****
     *
     * @var string
     */
    public $taskId;

    /**
     * @description The URL of the moderated object.
     *
     * @example http://example.com/example-****.jpg
     *
     * @var string
     */
    public $url;
    protected $_name = [
        'code'    => 'code',
        'dataId'  => 'dataId',
        'extras'  => 'extras',
        'msg'     => 'msg',
        'results' => 'results',
        'taskId'  => 'taskId',
        'url'     => 'url',
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
        if (null !== $this->dataId) {
            $res['dataId'] = $this->dataId;
        }
        if (null !== $this->extras) {
            $res['extras'] = $this->extras;
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
        if (null !== $this->url) {
            $res['url'] = $this->url;
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
        if (isset($map['dataId'])) {
            $model->dataId = $map['dataId'];
        }
        if (isset($map['extras'])) {
            $model->extras = $map['extras'];
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
        if (isset($map['url'])) {
            $model->url = $map['url'];
        }

        return $model;
    }
}
