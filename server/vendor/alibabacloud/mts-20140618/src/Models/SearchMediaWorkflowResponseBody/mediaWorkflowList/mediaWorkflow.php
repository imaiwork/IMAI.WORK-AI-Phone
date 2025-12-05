<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SearchMediaWorkflowResponseBody\mediaWorkflowList;

use AlibabaCloud\Tea\Model;

class mediaWorkflow extends Model
{
    /**
     * @description The time when the media workflow was created.
     *
     * @example 2016-04-01T05:38:41Z
     *
     * @var string
     */
    public $creationTime;

    /**
     * @description The ID of the media workflow.
     *
     * @example 88c6ca184c0e4578645b665e2a12****
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
     * @description The status of the media workflow. Valid values:
     *
     *   **Inactive**: The media workflow is deactivated.
     *   **Active**: The media workflow is activated.
     *   **Deleted**: The media workflow is deleted.
     *
     * @example Active
     *
     * @var string
     */
    public $state;

    /**
     * @description The topology of the media workflow.
     *
     * @example {"MediaWorkflowList":{"MediaWorkflow":[{"CreationTime":"2016-04-01T05:29:38Z","Name":"example-mediaworkflow-****","State":"Active","Topology":"{\\"Activities\\":{\\"Act-Start\\":{\\"Parameters\\":{\\"PipelineId\\":\\"130266f58161436a80bf07cb12c8****\\",\\"InputFile\\":\\"{\\\\\\"Bucket\\\\\\": \\\\\\"example-bucket-****\\\\\\",\\\\\\"Location\\\\\\": \\\\\\"example-location\\\\\\"}\\"},\\"Type\\":\\"Start\\"},\\"Act-Report\\":{\\"Parameters\\":{},\\"Type\\":\\"Report\\"},\\"Act-Transcode-M3U8\\":{\\"Parameters\\":{\\"Outputs\\":\\"[{\\\\\\"Object\\\\\\":\\\\\\"transcode/{ObjectPrefix}{FileName}\\\\\\",\\\\\\"TemplateId\\\\\\": \\\\\\"957d1719ee85ed6527b90cf62726****\\\\\\"}]\\",\\"OutputBucket\\":\\"example-bucket-****\\",\\"OutputLocation\\":\\"example-location\\"},\\"Type\\":\\"Transcode\\"}},\\"Dependencies\\":{\\"Act-Start\\":[\\"Act-Transcode-M3U8\\"],\\"Act-Report\\":[],\\"Act-Transcode-M3U8\\":[\\"Act-Report\\"]}}","MediaWorkflowId":"93ab850b4f6f44eab54b6e91d24d****"}]},"RequestId":"16CD0CDD-457E-420D-9755-8385075A1234"}
     *
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
