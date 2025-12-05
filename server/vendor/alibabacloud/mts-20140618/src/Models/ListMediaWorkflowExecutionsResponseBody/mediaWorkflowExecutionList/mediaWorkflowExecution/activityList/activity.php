<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListMediaWorkflowExecutionsResponseBody\mediaWorkflowExecutionList\mediaWorkflowExecution\activityList;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListMediaWorkflowExecutionsResponseBody\mediaWorkflowExecutionList\mediaWorkflowExecution\activityList\activity\MNSMessageResult;
use AlibabaCloud\Tea\Model;

class activity extends Model
{
    /**
     * @description The error code returned if the request failed.
     *
     *   The specific error code appears if the state of the activity is **Fail**.
     *   This parameter is not returned if the state of the activity is **Success**.
     *
     * @example null
     *
     * @var string
     */
    public $code;

    /**
     * @description The end time of the activity.
     *
     * @example 2016-04-01T06:54:00Z
     *
     * @var string
     */
    public $endTime;

    /**
     * @description The ID of the job generated when the activity is executed. We recommend that you keep this ID for subsequent operation calls.
     *
     * @example 2376030d9d0849399cd20e20c876****
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
     * @description The error message returned if the request failed.
     *
     *   The detailed error message appears if the state of the activity is **Fail**.
     *   This parameter is not returned if the state of the activity is **Success**.
     *
     * @example null
     *
     * @var string
     */
    public $message;

    /**
     * @description The name of the media workflow activity.
     *
     * > The name of an activity in a media workflow is unique.
     * @example Act-2
     *
     * @var string
     */
    public $name;

    /**
     * @description The start time of the activity.
     *
     * @example 2016-04-01T06:53:45Z
     *
     * @var string
     */
    public $startTime;

    /**
     * @description The status of the activity. Valid values:
     *
     *   **Running**: The activity is being executed.
     *   **Fail**: The activity failed to be executed.
     *   **Skipped**: The activity was skipped.
     *   **Success**: The activity was successfully executed.
     *
     * > For example, the high-definition and standard-definition transcoding activities are to be run after the analysis activity is complete. The system determines the activity to run based on the analysis result. If the definition of the input video content is insufficient, the high-definition transcoding activity may be skipped.
     * @example Success
     *
     * @var string
     */
    public $state;

    /**
     * @description The type of the media workflow activity. Valid values: Start, Snapshot, Transcode, Analysis, and Report. For more information, see [Methods supported for media workflows](https://help.aliyun.com/document_detail/68494.html).
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
