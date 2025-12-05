<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\tailSlateList;

use AlibabaCloud\Tea\Model;

class tailSlate extends Model
{
    /**
     * @description The color of the bars that are added to the ending part if the size of the ending part is smaller than that of the main part. Default value: White. For more information, see [Parameter details](https://help.aliyun.com/document_detail/29253.html).
     *
     * @example White
     *
     * @var string
     */
    public $bgColor;

    /**
     * @description The duration of the transition between the main part and the ending part. A fade transition is used: The last frame of the main part fades out, and the first frame of the ending part fades in. Unit: seconds. Default value: 0.
     *
     * @example 0
     *
     * @var string
     */
    public $blendDuration;

    /**
     * @description The height of the ending part.
     *
     *   Valid values: values in the range of (0,4096), -1, and full.
     *   A value of -1 indicates that the original height of the ending part is retained.
     *   A value of full indicates that the height of the ending part equals the height of the main part.
     *   Default value: -1.
     *
     * @example -1
     *
     * @var string
     */
    public $height;

    /**
     * @description Indicates whether the audio content of the ending part is merged. Valid values:
     *
     *   **true**
     *   **false**
     *
     * @example true
     *
     * @var bool
     */
    public $isMergeAudio;

    /**
     * @description The time when the ending part is played.
     *
     * @example 00000.00
     *
     * @var string
     */
    public $start;

    /**
     * @description The OSS URL of the ending part.
     *
     * @example http://example-bucket-****.oss-cn-hangzhou.aliyuncs.com/opening_01.flv
     *
     * @var string
     */
    public $tailUrl;

    /**
     * @description The width of the ending part. Valid values: values in the range of (0,4096), -1, and full.
     *
     *   A value of -1 indicates that the original width of the ending part is retained.
     *   A value of full indicates that the width of the ending part equals the width of the main part.
     *   Default value: -1.
     *
     * @example -1
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'bgColor'       => 'BgColor',
        'blendDuration' => 'BlendDuration',
        'height'        => 'Height',
        'isMergeAudio'  => 'IsMergeAudio',
        'start'         => 'Start',
        'tailUrl'       => 'TailUrl',
        'width'         => 'Width',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->bgColor) {
            $res['BgColor'] = $this->bgColor;
        }
        if (null !== $this->blendDuration) {
            $res['BlendDuration'] = $this->blendDuration;
        }
        if (null !== $this->height) {
            $res['Height'] = $this->height;
        }
        if (null !== $this->isMergeAudio) {
            $res['IsMergeAudio'] = $this->isMergeAudio;
        }
        if (null !== $this->start) {
            $res['Start'] = $this->start;
        }
        if (null !== $this->tailUrl) {
            $res['TailUrl'] = $this->tailUrl;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return tailSlate
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['BgColor'])) {
            $model->bgColor = $map['BgColor'];
        }
        if (isset($map['BlendDuration'])) {
            $model->blendDuration = $map['BlendDuration'];
        }
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['IsMergeAudio'])) {
            $model->isMergeAudio = $map['IsMergeAudio'];
        }
        if (isset($map['Start'])) {
            $model->start = $map['Start'];
        }
        if (isset($map['TailUrl'])) {
            $model->tailUrl = $map['TailUrl'];
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }

        return $model;
    }
}
