<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class SubmitTraceAbJobRequest extends Model
{
    /**
     * @example http://example.com/callback
     *
     * @var string
     */
    public $callBack;

    /**
     * @example Qh6OdgIMcliQSI1fReOw****
     *
     * @var string
     */
    public $cipherBase64ed;

    /**
     * @example {"Bucket":"ivison-test","Location":"oss-cn-shanghai","Object":"test.mp4"}
     *
     * @var string
     */
    public $input;

    /**
     * @example 2
     *
     * @var string
     */
    public $level;

    /**
     * @description This parameter is required.
     *
     * @example {"Bucket":"ivison-test","Location":"oss-cn-shanghai","Dir":"out/"}
     *
     * @var string
     */
    public $output;

    /**
     * @example 0
     *
     * @var string
     */
    public $startTime;

    /**
     * @example 360
     *
     * @var string
     */
    public $totalTime;

    /**
     * @example http://www.example.com/video/test.mp4
     *
     * @var string
     */
    public $url;

    /**
     * @example 123
     *
     * @var string
     */
    public $userData;
    protected $_name = [
        'callBack'       => 'CallBack',
        'cipherBase64ed' => 'CipherBase64ed',
        'input'          => 'Input',
        'level'          => 'Level',
        'output'         => 'Output',
        'startTime'      => 'StartTime',
        'totalTime'      => 'TotalTime',
        'url'            => 'Url',
        'userData'       => 'UserData',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->callBack) {
            $res['CallBack'] = $this->callBack;
        }
        if (null !== $this->cipherBase64ed) {
            $res['CipherBase64ed'] = $this->cipherBase64ed;
        }
        if (null !== $this->input) {
            $res['Input'] = $this->input;
        }
        if (null !== $this->level) {
            $res['Level'] = $this->level;
        }
        if (null !== $this->output) {
            $res['Output'] = $this->output;
        }
        if (null !== $this->startTime) {
            $res['StartTime'] = $this->startTime;
        }
        if (null !== $this->totalTime) {
            $res['TotalTime'] = $this->totalTime;
        }
        if (null !== $this->url) {
            $res['Url'] = $this->url;
        }
        if (null !== $this->userData) {
            $res['UserData'] = $this->userData;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return SubmitTraceAbJobRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CallBack'])) {
            $model->callBack = $map['CallBack'];
        }
        if (isset($map['CipherBase64ed'])) {
            $model->cipherBase64ed = $map['CipherBase64ed'];
        }
        if (isset($map['Input'])) {
            $model->input = $map['Input'];
        }
        if (isset($map['Level'])) {
            $model->level = $map['Level'];
        }
        if (isset($map['Output'])) {
            $model->output = $map['Output'];
        }
        if (isset($map['StartTime'])) {
            $model->startTime = $map['StartTime'];
        }
        if (isset($map['TotalTime'])) {
            $model->totalTime = $map['TotalTime'];
        }
        if (isset($map['Url'])) {
            $model->url = $map['Url'];
        }
        if (isset($map['UserData'])) {
            $model->userData = $map['UserData'];
        }

        return $model;
    }
}
