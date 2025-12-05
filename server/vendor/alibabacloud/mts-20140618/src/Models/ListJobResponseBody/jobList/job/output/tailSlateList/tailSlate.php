<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\tailSlateList;

use AlibabaCloud\Tea\Model;

class tailSlate extends Model
{
    /**
     * @description The color of the bars that are added to the ending part if the size of the ending part is smaller than that of the main part. Default value: **White**. For more information, see [Background colors](https://docs-aliyun.cn-hangzhou.oss.aliyun-inc.com/assets/attach/29253/cn_zh/1502784952344/color.txt?spm=a2c4g.11186623.2.63.1df840f74IH4Eq\\&file=color.txt).
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
     * @description The height of the ending part. Valid values: values in the range of (0, 4096), -1, and full.
     *
     *   A value of -1 indicates that the height of the source of the ending part is retained.
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
     * @description The start time.
     *
     * @example 1
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
     * @description The width of the ending part. Valid values: values in the range of (0, 4096), -1, and full.
     *
     *   A value of -1 indicates that the width of the source of the ending part is retained. A value of full indicates that the width of the ending part equals the width of the main part.
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
