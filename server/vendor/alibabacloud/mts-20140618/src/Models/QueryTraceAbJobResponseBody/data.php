<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryTraceAbJobResponseBody;

use AlibabaCloud\Tea\Model;

class data extends Model
{
    /**
     * @example http://callbacktest.com/callback
     *
     * @var string
     */
    public $callback;

    /**
     * @example 1627357322
     *
     * @var int
     */
    public $gmtCreate;

    /**
     * @example 1627357325
     *
     * @var int
     */
    public $gmtModified;

    /**
     * @example {"Bucket":"ivison-test","Location":"oss-cn-shanghai","Object":"test.mp4"}
     *
     * @var string
     */
    public $input;

    /**
     * @example bfb786c639894f4d80648792021e****
     *
     * @var string
     */
    public $jobId;

    /**
     * @example 2
     *
     * @var int
     */
    public $level;

    /**
     * @example 437bd2b516ffda105d07b12a9a82****
     *
     * @var string
     */
    public $mediaId;

    /**
     * @example {"Bucket":"ivison-test","Location":"oss-cn-shanghai","Object":"out.mp4"}
     *
     * @var string
     */
    public $output;

    /**
     * @example {"Code":"success","Message":"ok"}
     *
     * @var string
     */
    public $result;

    /**
     * @example success
     *
     * @var string
     */
    public $status;

    /**
     * @example 123
     *
     * @var string
     */
    public $userData;

    /**
     * @example 13466****
     *
     * @var int
     */
    public $userId;
    protected $_name = [
        'callback'    => 'Callback',
        'gmtCreate'   => 'GmtCreate',
        'gmtModified' => 'GmtModified',
        'input'       => 'Input',
        'jobId'       => 'JobId',
        'level'       => 'Level',
        'mediaId'     => 'MediaId',
        'output'      => 'Output',
        'result'      => 'Result',
        'status'      => 'Status',
        'userData'    => 'UserData',
        'userId'      => 'UserId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->callback) {
            $res['Callback'] = $this->callback;
        }
        if (null !== $this->gmtCreate) {
            $res['GmtCreate'] = $this->gmtCreate;
        }
        if (null !== $this->gmtModified) {
            $res['GmtModified'] = $this->gmtModified;
        }
        if (null !== $this->input) {
            $res['Input'] = $this->input;
        }
        if (null !== $this->jobId) {
            $res['JobId'] = $this->jobId;
        }
        if (null !== $this->level) {
            $res['Level'] = $this->level;
        }
        if (null !== $this->mediaId) {
            $res['MediaId'] = $this->mediaId;
        }
        if (null !== $this->output) {
            $res['Output'] = $this->output;
        }
        if (null !== $this->result) {
            $res['Result'] = $this->result;
        }
        if (null !== $this->status) {
            $res['Status'] = $this->status;
        }
        if (null !== $this->userData) {
            $res['UserData'] = $this->userData;
        }
        if (null !== $this->userId) {
            $res['UserId'] = $this->userId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return data
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Callback'])) {
            $model->callback = $map['Callback'];
        }
        if (isset($map['GmtCreate'])) {
            $model->gmtCreate = $map['GmtCreate'];
        }
        if (isset($map['GmtModified'])) {
            $model->gmtModified = $map['GmtModified'];
        }
        if (isset($map['Input'])) {
            $model->input = $map['Input'];
        }
        if (isset($map['JobId'])) {
            $model->jobId = $map['JobId'];
        }
        if (isset($map['Level'])) {
            $model->level = $map['Level'];
        }
        if (isset($map['MediaId'])) {
            $model->mediaId = $map['MediaId'];
        }
        if (isset($map['Output'])) {
            $model->output = $map['Output'];
        }
        if (isset($map['Result'])) {
            $model->result = $map['Result'];
        }
        if (isset($map['Status'])) {
            $model->status = $map['Status'];
        }
        if (isset($map['UserData'])) {
            $model->userData = $map['UserData'];
        }
        if (isset($map['UserId'])) {
            $model->userId = $map['UserId'];
        }

        return $model;
    }
}
