<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\amixList;

use AlibabaCloud\Tea\Model;

class amix extends Model
{
    /**
     * @description The URL of the audio track that is mixed as the background music.
     *
     *   The URL can be an OSS URL or the string `input`.
     *   A value of input indicates that two audio tracks are mixed in a video.
     *
     * @example https://outpu***.oss-cn-shanghai.aliyuncs.com/mp4-to-mp3%5E1571025263578816%40.mp3
     *
     * @var string
     */
    public $amixURL;

    /**
     * @description The duration of the mixed audio track. The value is in the number or time format.
     *
     * @example 20
     *
     * @var string
     */
    public $duration;

    /**
     * @description The audio track that is mixed. Format: 0:a:{audio_index}. Example: 0:a:0.
     *
     * @example 0:a:0
     *
     * @var string
     */
    public $map;

    /**
     * @description The mode to specify the mixing duration. Valid values: **first** and **long**.
     *
     *   **first**: The length of the output media equals the length of the input media.
     *   **long**: The length of the output media equals the length of the output media or the length of the input media, whichever is longer.
     *   Default value: **long**.
     *
     * @example long
     *
     * @var string
     */
    public $mixDurMode;

    /**
     * @description The start time. The value is in the number or time format. Examples: 1:25:36.240 and 32000.23.
     *
     * @example 0
     *
     * @var string
     */
    public $start;
    protected $_name = [
        'amixURL'    => 'AmixURL',
        'duration'   => 'Duration',
        'map'        => 'Map',
        'mixDurMode' => 'MixDurMode',
        'start'      => 'Start',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->amixURL) {
            $res['AmixURL'] = $this->amixURL;
        }
        if (null !== $this->duration) {
            $res['Duration'] = $this->duration;
        }
        if (null !== $this->map) {
            $res['Map'] = $this->map;
        }
        if (null !== $this->mixDurMode) {
            $res['MixDurMode'] = $this->mixDurMode;
        }
        if (null !== $this->start) {
            $res['Start'] = $this->start;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return amix
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AmixURL'])) {
            $model->amixURL = $map['AmixURL'];
        }
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['Map'])) {
            $model->map = $map['Map'];
        }
        if (isset($map['MixDurMode'])) {
            $model->mixDurMode = $map['MixDurMode'];
        }
        if (isset($map['Start'])) {
            $model->start = $map['Start'];
        }

        return $model;
    }
}
