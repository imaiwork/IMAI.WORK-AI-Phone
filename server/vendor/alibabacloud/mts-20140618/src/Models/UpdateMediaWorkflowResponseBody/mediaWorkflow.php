<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\UpdateMediaWorkflowResponseBody;

use AlibabaCloud\Tea\Model;

class mediaWorkflow extends Model
{
    /**
     * @description The time when the media workflow was created.
     *
     * @example 2016-04-01T05:29:38Z
     *
     * @var string
     */
    public $creationTime;

    /**
     * @description The ID of the media workflow that is updated.
     *
     * @example 6307eb0d3f85477882d205aa040f****
     *
     * @var string
     */
    public $mediaWorkflowId;

    /**
     * @description The name of the media workflow.
     *
     * @example examp-mediaworkflow-****
     *
     * @var string
     */
    public $name;

    /**
     * @description The state of the media workflow. Valid values:
     *
     *   **Inactive**: The media workflow is disabled.
     *   **Active**: The media workflow is enabled.
     *
     * @example Active
     *
     * @var string
     */
    public $state;

    /**
     * @description The topology of the media workflow.
     *
     * @example {
     * "Parameters": {
     * "PipelineId": "130266f58161436a80bf07cb12c8****",
     * "InputFile": "{\\"Bucket\\": \\"example-bucket-****\\",\\"Location\\": \\"cn-shanghai\\"}"
     * "Parameters": {
     * "Outputs": "[{\\"Object\\":\\"transcode/{ObjectPrefix}{FileName}\\",\\"TemplateId\\": \\"957d1719ee85ed6527b90cf62726****\\"}]",
     * "OutputBucket": "example-bucket-****",
     * }
     * @var string
     */
    public $topology;

    /**
     * @description The trigger mode of the media workflow. Valid values:
     *
     *   **OssAutoTrigger**: The media workflow is automatically triggered.
     *   **NotInAuto**: The media workflow is not automatically triggered.
     *
     * @example OssAutoTrigger
     *
     * @var string
     */
    public $triggerMode;
    protected $_name = [
        'creationTime'    => 'CreationTime',
        'mediaWorkflowId' => 'MediaWorkflowId',
        'name'            => 'Name',
        'state'           => 'State',
        'topology'        => 'Topology',
        'triggerMode'     => 'TriggerMode',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->creationTime) {
            $res['CreationTime'] = $this->creationTime;
        }
        if (null !== $this->mediaWorkflowId) {
            $res['MediaWorkflowId'] = $this->mediaWorkflowId;
        }
        if (null !== $this->name) {
            $res['Name'] = $this->name;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }
        if (null !== $this->topology) {
            $res['Topology'] = $this->topology;
        }
        if (null !== $this->triggerMode) {
            $res['TriggerMode'] = $this->triggerMode;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return mediaWorkflow
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CreationTime'])) {
            $model->creationTime = $map['CreationTime'];
        }
        if (isset($map['MediaWorkflowId'])) {
            $model->mediaWorkflowId = $map['MediaWorkflowId'];
        }
        if (isset($map['Name'])) {
            $model->name = $map['Name'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }
        if (isset($map['Topology'])) {
            $model->topology = $map['Topology'];
        }
        if (isset($map['TriggerMode'])) {
            $model->triggerMode = $map['TriggerMode'];
        }

        return $model;
    }
}
