<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListByURLResponseBody\mediaList\media\summaryList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListByURLResponseBody\mediaList\media\summaryList\summary\file;
use AlibabaCloud\Tea\Model;

class summary extends Model
{
    /**
     * @description The name of the workflow activity.
     *
     * @example example-activity-****
     *
     * @var string
     */
    public $activityName;

    /**
     * @description The information about the input file.
     *
     * @var file
     */
    public $file;

    /**
     * @description The ID of the workflow that generates the summary.
     *
     * @example 93ab850b4f6f44eab54b6e91d24d****
     *
     * @var string
     */
    public $mediaWorkflowId;

    /**
     * @description The name of the workflow that generates the summary.
     *
     * @example example-mediaworkflow-****
     *
     * @var string
     */
    public $mediaWorkflowName;

    /**
     * @description The type of the summary. Valid values:
     *
     *   **Video**: video
     *   **Gif**: dynamic image
     *
     * @example Gif
     *
     * @var string
     */
    public $type;
    protected $_name = [
        'activityName'      => 'ActivityName',
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
     * @return summary
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['ActivityName'])) {
            $model->activityName = $map['ActivityName'];
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
