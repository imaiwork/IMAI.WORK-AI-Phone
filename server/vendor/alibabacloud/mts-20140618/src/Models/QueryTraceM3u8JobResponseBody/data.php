<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryTraceM3u8JobResponseBody;

use AlibabaCloud\Tea\Model;

class data extends Model
{
    /**
     * @example 1627357322
     *
     * @var int
     */
    public $gmtCreate;

    /**
     * @example 1627357327
     *
     * @var int
     */
    public $gmtModified;

    /**
     * @example 88c6ca184c0e47098a5b665e2a12****
     *
     * @var string
     */
    public $jobId;

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
     * @example success
     *
     * @var string
     */
    public $status;

    /**
     * @example 平头哥半导体(上海)
     *
     * @var string
     */
    public $trace;

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
        'gmtCreate'   => 'GmtCreate',
        'gmtModified' => 'GmtModified',
        'jobId'       => 'JobId',
        'mediaId'     => 'MediaId',
        'output'      => 'Output',
        'status'      => 'Status',
        'trace'       => 'Trace',
        'userData'    => 'UserData',
        'userId'      => 'UserId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->gmtCreate) {
            $res['GmtCreate'] = $this->gmtCreate;
        }
        if (null !== $this->gmtModified) {
            $res['GmtModified'] = $this->gmtModified;
        }
        if (null !== $this->jobId) {
            $res['JobId'] = $this->jobId;
        }
        if (null !== $this->mediaId) {
            $res['MediaId'] = $this->mediaId;
        }
        if (null !== $this->output) {
            $res['Output'] = $this->output;
        }
        if (null !== $this->status) {
            $res['Status'] = $this->status;
        }
        if (null !== $this->trace) {
            $res['Trace'] = $this->trace;
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
        if (isset($map['GmtCreate'])) {
            $model->gmtCreate = $map['GmtCreate'];
        }
        if (isset($map['GmtModified'])) {
            $model->gmtModified = $map['GmtModified'];
        }
        if (isset($map['JobId'])) {
            $model->jobId = $map['JobId'];
        }
        if (isset($map['MediaId'])) {
            $model->mediaId = $map['MediaId'];
        }
        if (isset($map['Output'])) {
            $model->output = $map['Output'];
        }
        if (isset($map['Status'])) {
            $model->status = $map['Status'];
        }
        if (isset($map['Trace'])) {
            $model->trace = $map['Trace'];
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
