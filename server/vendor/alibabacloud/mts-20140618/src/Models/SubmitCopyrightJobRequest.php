<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models;

use AlibabaCloud\Tea\Model;

class SubmitCopyrightJobRequest extends Model
{
    /**
     * @example http://example.com/callback
     *
     * @var string
     */
    public $callBack;

    /**
     * @var string
     */
    public $description;

    /**
     * @example {"Bucket":"example-bucket","Location":"oss-cn-shanghai","Object":"example.mp4"}
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
     * @var string
     */
    public $message;

    /**
     * @description This parameter is required.
     *
     * @example {"Bucket":"example-bucket","Location":"oss-cn-shanghai","Object":"example_result.mp4"}
     *
     * @var string
     */
    public $output;

    /**
     * @example {"algoType":"v2"}
     *
     * @var string
     */
    public $params;

    /**
     * @example 0
     *
     * @var string
     */
    public $startTime;

    /**
     * @example 10
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
        'callBack'    => 'CallBack',
        'description' => 'Description',
        'input'       => 'Input',
        'level'       => 'Level',
        'message'     => 'Message',
        'output'      => 'Output',
        'params'      => 'Params',
        'startTime'   => 'StartTime',
        'totalTime'   => 'TotalTime',
        'url'         => 'Url',
        'userData'    => 'UserData',
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
        if (null !== $this->description) {
            $res['Description'] = $this->description;
        }
        if (null !== $this->input) {
            $res['Input'] = $this->input;
        }
        if (null !== $this->level) {
            $res['Level'] = $this->level;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->output) {
            $res['Output'] = $this->output;
        }
        if (null !== $this->params) {
            $res['Params'] = $this->params;
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
     * @return SubmitCopyrightJobRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CallBack'])) {
            $model->callBack = $map['CallBack'];
        }
        if (isset($map['Description'])) {
            $model->description = $map['Description'];
        }
        if (isset($map['Input'])) {
            $model->input = $map['Input'];
        }
        if (isset($map['Level'])) {
            $model->level = $map['Level'];
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['Output'])) {
            $model->output = $map['Output'];
        }
        if (isset($map['Params'])) {
            $model->params = $map['Params'];
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
