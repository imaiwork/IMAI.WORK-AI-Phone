<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody\mediaWorkflowExecutionList\mediaWorkflowExecution\activityList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody\mediaWorkflowExecutionList\mediaWorkflowExecution\activityList\activity\MNSMessageResult;
use AlibabaCloud\Tea\Model;

class activity extends Model
{
    /**
     * @description The error code.
     *
     *   This parameter is returned only if **Fail** is returned for the State parameter.
     *   This parameter is not returned if the method status is **Success**.
     *
     * @example InvalidParameter.ResourceContentBad
     *
     * @var string
     */
    public $code;

    /**
     * @description The time when the method ends.
     *
     * @example 2016-04-01T06:53:44Z
     *
     * @var string
     */
    public $endTime;

    /**
     * @description The IDs of the jobs that are generated when the methods are called, such as the job IDs for the analysis, transcode, and snapshot methods.
     *
     * @example 2376030d9d0849399cd20e20f4f3****
     *
     * @var string
     */
    public $jobId;

    /**
     * @description The message sent by Message Service (MNS) to notify the user of the job result.
     *
     * @var MNSMessageResult
     */
    public $MNSMessageResult;

    /**
     * @description The error message.
     *
     *   This parameter is returned only if **Fail** is returned for the State parameter.
     *   This parameter is not returned if the method status is **Success**.
     *
     * @example The resource operated InputFile is bad
     *
     * @var string
     */
    public $message;

    /**
     * @description The name of the method.
     *
     * > The name of each method in a media workflow is unique.
     * @example Start
     *
     * @var string
     */
    public $name;

    /**
     * @description The time when the method is called.
     *
     * @example 2016-04-01T06:53:44Z
     *
     * @var string
     */
    public $startTime;

    /**
     * @description The status of the workflow method. Valid values:
     *
     *   Running: The method is being called.
     *   Success: The method is called.
     *   Fail: The method failed to be called.
     *   Skipped: The method is skipped.
     *
     * > For example, after the analysis is complete, the transcode method is called and high-definition and standard-definition transcoding jobs are created. The system determines whether to run the jobs based on the analysis result. If the resolution of the input video is low, the high-definition transcoding job may be skipped.
     * @example Running
     *
     * @var string
     */
    public $state;

    /**
     * @description The methods that are supported in the media workflow. Valid values: Start, Snapshot, Transcode, Analysis, and Report. For more information, see [Methods supported for media workflows](https://help.aliyun.com/document_detail/68494.html).
     *
     * @example Start
     *
     * @var string
     */
    public $type;
    protected $_name = [
        'code'             => 'Code',
        'endTime'          => 'EndTime',
        'jobId'            => 'JobId',
        'MNSMessageResult' => 'MNSMessageResult',
        'message'          => 'Message',
        'name'             => 'Name',
        'startTime'        => 'StartTime',
        'state'            => 'State',
        'type'             => 'Type',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->code) {
            $res['Code'] = $this->code;
        }
        if (null !== $this->endTime) {
            $res['EndTime'] = $this->endTime;
        }
        if (null !== $this->jobId) {
            $res['JobId'] = $this->jobId;
        }
        if (null !== $this->MNSMessageResult) {
            $res['MNSMessageResult'] = null !== $this->MNSMessageResult ? $this->MNSMessageResult->toMap() : null;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->name) {
            $res['Name'] = $this->name;
        }
        if (null !== $this->startTime) {
            $res['StartTime'] = $this->startTime;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }
        if (null !== $this->type) {
            $res['Type'] = $this->type;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return activity
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Code'])) {
            $model->code = $map['Code'];
        }
        if (isset($map['EndTime'])) {
            $model->endTime = $map['EndTime'];
        }
        if (isset($map['JobId'])) {
            $model->jobId = $map['JobId'];
        }
        if (isset($map['MNSMessageResult'])) {
            $model->MNSMessageResult = MNSMessageResult::fromMap($map['MNSMessageResult']);
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['Name'])) {
            $model->name = $map['Name'];
        }
        if (isset($map['StartTime'])) {
            $model->startTime = $map['StartTime'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }
        if (isset($map['Type'])) {
            $model->type = $map['Type'];
        }

        return $model;
    }
}
