<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\outSubtitleList;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\outSubtitleList\outSubtitle\outSubtitleFile;
use AlibabaCloud\Tea\Model;

class outSubtitle extends Model
{
    /**
     * @description The video track. Format: `0:{Stream}:{Stream sequence number}`, which is `0:v:{video_index}`. The value of Stream is v, which indicates a video stream. The sequence number is the index of the video stream in the list and starts from 0.
     *
     * @example 0:v:0
     *
     * @var string
     */
    public $map;

    /**
     * @description The error message returned if the job failed to be created. This parameter is not returned if the job was created.
     *
     * @example The specified parameter “%s” cannot be null.
     *
     * @var string
     */
    public $message;

    /**
     * @description The details of the output file.
     *
     * @var outSubtitleFile
     */
    public $outSubtitleFile;

    /**
     * @description Indicates whether the job was created. Valid values:
     *
     *   **true**
     *   **false**
     *
     * @example true
     *
     * @var bool
     */
    public $success;
    protected $_name = [
        'map'             => 'Map',
        'message'         => 'Message',
        'outSubtitleFile' => 'OutSubtitleFile',
        'success'         => 'Success',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->map) {
            $res['Map'] = $this->map;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->outSubtitleFile) {
            $res['OutSubtitleFile'] = null !== $this->outSubtitleFile ? $this->outSubtitleFile->toMap() : null;
        }
        if (null !== $this->success) {
            $res['Success'] = $this->success;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return outSubtitle
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Map'])) {
            $model->map = $map['Map'];
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['OutSubtitleFile'])) {
            $model->outSubtitleFile = outSubtitleFile::fromMap($map['OutSubtitleFile']);
        }
        if (isset($map['Success'])) {
            $model->success = $map['Success'];
        }

        return $model;
    }
}
