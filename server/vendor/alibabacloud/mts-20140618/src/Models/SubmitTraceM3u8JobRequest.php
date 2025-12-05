<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class SubmitTraceM3u8JobRequest extends Model
{
    /**
     * @example https://cipher.abc.com
     *
     * @var string
     */
    public $keyUri;

    /**
     * @example 437bd2b516ffda105d07b12a9a82****
     *
     * @var string
     */
    public $mediaId;

    /**
     * @example {"Bucket":"exampleBucket","Location":"oss-cn-shanghai","Object":"out.m3u8"}
     *
     * @var string
     */
    public $output;

    /**
     * @example {"m3u8Type":"v1"}
     *
     * @var string
     */
    public $params;

    /**
     * @var string
     */
    public $trace;
    protected $_name = [
        'keyUri'  => 'KeyUri',
        'mediaId' => 'MediaId',
        'output'  => 'Output',
        'params'  => 'Params',
        'trace'   => 'Trace',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->keyUri) {
            $res['KeyUri'] = $this->keyUri;
        }
        if (null !== $this->mediaId) {
            $res['MediaId'] = $this->mediaId;
        }
        if (null !== $this->output) {
            $res['Output'] = $this->output;
        }
        if (null !== $this->params) {
            $res['Params'] = $this->params;
        }
        if (null !== $this->trace) {
            $res['Trace'] = $this->trace;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SubmitTraceM3u8JobRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['KeyUri'])) {
            $model->keyUri = $map['KeyUri'];
        }
        if (isset($map['MediaId'])) {
            $model->mediaId = $map['MediaId'];
        }
        if (isset($map['Output'])) {
            $model->output = $map['Output'];
        }
        if (isset($map['Params'])) {
            $model->params = $map['Params'];
        }
        if (isset($map['Trace'])) {
            $model->trace = $map['Trace'];
        }

        return $model;
    }
}
