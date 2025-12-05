<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody\mediaWorkflowExecutionList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody\mediaWorkflowExecutionList\mediaWorkflowExecution\activityList;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody\mediaWorkflowExecutionList\mediaWorkflowExecution\input;
use AlibabaCloud\Tea\Model;

class mediaWorkflowExecution extends Model
{
    /**
     * @description The methods that are called in the media workflow.
     *
     * @var activityList
     */
    public $activityList;

    /**
     * @description The time when the media workflow was created.
     *
     * @example 016-04-01T06:53:43Z
     *
     * @var string
     */
    public $creationTime;

    /**
     * @description The input data of the media workflow.
     *
     * @var input
     */
    public $input;

    /**
     * @description The ID of the media asset. A media file contains all the information about a media workflow.
     *
     * @example 512046582a924698a41e0f8b0d2b****
     *
     * @var string
     */
    public $mediaId;

    /**
     * @description The ID of the media workflow.
     *
     * @example 93ab850b4f6f44eab54b6e91****81d4
     *
     * @var string
     */
    public $mediaWorkflowId;

    /**
     * @description The name of the media workflow.
     *
     * @example example-mediaworkflow-****
     *
     * @var string
     */
    public $name;

    /**
     * @description The ID of the execution instance.
     *
     * @example 48e33690ac19445488c706924321****
     *
     * @var string
     */
    public $runId;

    /**
     * @description The status of the media workflow. Valid values:
     *
     *   Running: The media workflow is running.
     *   Completed: The media workflow is complete.
     *
     * > Completed only indicates that the media workflow is complete. View the status of each method in the workflow, such as the transcode and snapshot methods, to check whether the method is called.
     *
     *   Fail: The media workflow fails.
     *
     * @example Completed
     *
     * @var string
     */
    public $state;
    protected $_name = [
        'activityList'    => 'ActivityList',
        'creationTime'    => 'CreationTime',
        'input'           => 'Input',
        'mediaId'         => 'MediaId',
        'mediaWorkflowId' => 'MediaWorkflowId',
        'name'            => 'Name',
        'runId'           => 'RunId',
        'state'           => 'State',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->activityList) {
            $res['ActivityList'] = null !== $this->activityList ? $this->activityList->toMap() : null;
        }
        if (null !== $this->creationTime) {
            $res['CreationTime'] = $this->creationTime;
        }
        if (null !== $this->input) {
            $res['Input'] = null !== $this->input ? $this->input->toMap() : null;
        }
        if (null !== $this->mediaId) {
            $res['MediaId'] = $this->mediaId;
        }
        if (null !== $this->mediaWorkflowId) {
            $res['MediaWorkflowId'] = $this->mediaWorkflowId;
        }
        if (null !== $this->name) {
            $res['Name'] = $this->name;
        }
        if (null !== $this->runId) {
            $res['RunId'] = $this->runId;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return mediaWorkflowExecution
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['ActivityList'])) {
            $model->activityList = activityList::fromMap($map['ActivityList']);
        }
        if (isset($map['CreationTime'])) {
            $model->creationTime = $map['CreationTime'];
        }
        if (isset($map['Input'])) {
            $model->input = input::fromMap($map['Input']);
        }
        if (isset($map['MediaId'])) {
            $model->mediaId = $map['MediaId'];
        }
        if (isset($map['MediaWorkflowId'])) {
            $model->mediaWorkflowId = $map['MediaWorkflowId'];
        }
        if (isset($map['Name'])) {
            $model->name = $map['Name'];
        }
        if (isset($map['RunId'])) {
            $model->runId = $map['RunId'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }

        return $model;
    }
}
