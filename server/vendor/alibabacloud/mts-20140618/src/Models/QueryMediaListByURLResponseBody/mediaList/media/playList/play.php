<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListByURLResponseBody\mediaList\media\playList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaListByURLResponseBody\mediaList\media\playList\play\file;
use AlibabaCloud\Tea\Model;

class play extends Model
{
    /**
     * @description The name of the workflow activity.
     *
     * @example test name
     *
     * @var string
     */
    public $activityName;

    /**
     * @description The bitrate of the media file.
     *
     * @example 25.067
     *
     * @var string
     */
    public $bitrate;

    /**
     * @description The duration.
     *
     * @example 7.965000
     *
     * @var string
     */
    public $duration;

    /**
     * @description Indicates whether the media file is encrypted. Valid values:
     *
     *   **0**: The media file is not encrypted.
     *   **1**: The media file is encrypted.
     *
     * @example 1
     *
     * @var string
     */
    public $encryption;

    /**
     * @description The playback file.
     *
     * @var file
     */
    public $file;

    /**
     * @description The encoding format of the media file. Valid values: mov, mp4, m4a, 3gp, 3g2, and mj2.
     *
     * @example mov
     *
     * @var string
     */
    public $format;

    /**
     * @description The frame rate.
     *
     * @example 25.0
     *
     * @var string
     */
    public $fps;

    /**
     * @description The height of the media file.
     *
     * @example 10
     *
     * @var string
     */
    public $height;

    /**
     * @description The ID of the workflow that generates the playback file.
     *
     * @example 6cc3aa66d1cb4bb2adf14e726c0a****
     *
     * @var string
     */
    public $mediaWorkflowId;

    /**
     * @description The name of the workflow that generates the playback file.
     *
     * @example example-mediaworkflow-****
     *
     * @var string
     */
    public $mediaWorkflowName;

    /**
     * @description The size.
     *
     * @example 100
     *
     * @var string
     */
    public $size;

    /**
     * @description The width.
     *
     * @example 11
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'activityName'      => 'ActivityName',
        'bitrate'           => 'Bitrate',
        'duration'          => 'Duration',
        'encryption'        => 'Encryption',
        'file'              => 'File',
        'format'            => 'Format',
        'fps'               => 'Fps',
        'height'            => 'Height',
        'mediaWorkflowId'   => 'MediaWorkflowId',
        'mediaWorkflowName' => 'MediaWorkflowName',
        'size'              => 'Size',
        'width'             => 'Width',
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
        if (null !== $this->bitrate) {
            $res['Bitrate'] = $this->bitrate;
        }
        if (null !== $this->duration) {
            $res['Duration'] = $this->duration;
        }
        if (null !== $this->encryption) {
            $res['Encryption'] = $this->encryption;
        }
        if (null !== $this->file) {
            $res['File'] = null !== $this->file ? $this->file->toMap() : null;
        }
        if (null !== $this->format) {
            $res['Format'] = $this->format;
        }
        if (null !== $this->fps) {
            $res['Fps'] = $this->fps;
        }
        if (null !== $this->height) {
            $res['Height'] = $this->height;
        }
        if (null !== $this->mediaWorkflowId) {
            $res['MediaWorkflowId'] = $this->mediaWorkflowId;
        }
        if (null !== $this->mediaWorkflowName) {
            $res['MediaWorkflowName'] = $this->mediaWorkflowName;
        }
        if (null !== $this->size) {
            $res['Size'] = $this->size;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return play
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['ActivityName'])) {
            $model->activityName = $map['ActivityName'];
        }
        if (isset($map['Bitrate'])) {
            $model->bitrate = $map['Bitrate'];
        }
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['Encryption'])) {
            $model->encryption = $map['Encryption'];
        }
        if (isset($map['File'])) {
            $model->file = file::fromMap($map['File']);
        }
        if (isset($map['Format'])) {
            $model->format = $map['Format'];
        }
        if (isset($map['Fps'])) {
            $model->fps = $map['Fps'];
        }
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['MediaWorkflowId'])) {
            $model->mediaWorkflowId = $map['MediaWorkflowId'];
        }
        if (isset($map['MediaWorkflowName'])) {
            $model->mediaWorkflowName = $map['MediaWorkflowName'];
        }
        if (isset($map['Size'])) {
            $model->size = $map['Size'];
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }

        return $model;
    }
}
