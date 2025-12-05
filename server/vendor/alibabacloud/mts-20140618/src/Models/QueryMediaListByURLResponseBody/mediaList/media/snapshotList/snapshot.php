<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListByURLResponseBody\mediaList\media\snapshotList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListByURLResponseBody\mediaList\media\snapshotList\snapshot\file;
use AlibabaCloud\Tea\Model;

class snapshot extends Model
{
    /**
     * @description The name of the workflow activity that generates the snapshot.
     *
     * @example example-activity1-****
     *
     * @var string
     */
    public $activityName;

    /**
     * @description The number of snapshots. This parameter is valid only when the value of the **Type** parameter is **Sequence**.
     *
     * @example 3
     *
     * @var string
     */
    public $count;

    /**
     * @description The snapshot.
     *
     * @var file
     */
    public $file;

    /**
     * @description The ID of the workflow that generates the snapshot.
     *
     * @example 6cc3aa66d1cb4bb2adf14e726c0a****
     *
     * @var string
     */
    public $mediaWorkflowId;

    /**
     * @description The name of the workflow that generates the snapshot.
     *
     * @example example-workflow-****
     *
     * @var string
     */
    public $mediaWorkflowName;

    /**
     * @description The type of the snapshot. Valid values:
     *
     * - **Single**: a single snapshot
     * - **Sequence**: snapshots in sequence
     * @example Single
     *
     * @var string
     */
    public $type;
    protected $_name = [
        'activityName'      => 'ActivityName',
        'count'             => 'Count',
        'file'              => 'File',
        'mediaWorkflowId'   => 'MediaWorkflowId',
        'mediaWorkflowName' => 'MediaWorkflowName',
        'type'              => 'Type',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->activityName) {
            $res['ActivityName'] = $this->activityName;
        }
        if (null !== $this->count) {
            $res['Count'] = $this->count;
        }
        if (null !== $this->file) {
            $res['File'] = null !== $this->file ? $this->file->toMap() : null;
        }
        if (null !== $this->mediaWorkflowId) {
            $res['MediaWorkflowId'] = $this->mediaWorkflowId;
        }
        if (null !== $this->mediaWorkflowName) {
            $res['MediaWorkflowName'] = $this->mediaWorkflowName;
        }
        if (null !== $this->type) {
            $res['Type'] = $this->type;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return snapshot
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['ActivityName'])) {
            $model->activityName = $map['ActivityName'];
        }
        if (isset($map['Count'])) {
            $model->count = $map['Count'];
        }
        if (isset($map['File'])) {
            $model->file = file::fromMap($map['File']);
        }
        if (isset($map['MediaWorkflowId'])) {
            $model->mediaWorkflowId = $map['MediaWorkflowId'];
        }
        if (isset($map['MediaWorkflowName'])) {
            $model->mediaWorkflowName = $map['MediaWorkflowName'];
        }
        if (isset($map['Type'])) {
            $model->type = $map['Type'];
        }

        return $model;
    }
}
