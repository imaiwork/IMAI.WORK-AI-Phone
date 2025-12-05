<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\openingList;

use AlibabaCloud\Tea\Model;

class opening extends Model
{
    /**
     * @description The height of the opening part.
     *
     *   Valid values: values in the range of (0,4096), -1, and full.
     *   A value of -1 indicates that the original height of the opening part is retained.
     *   A value of full indicates that the height of the opening part equals the height of the main part.
     *   Default value: **-1**.
     *
     * @example -1
     *
     * @var string
     */
    public $height;

    /**
     * @description The amount of time after which the opening part is played.
     *
     *   The value starts from 0.
     *   Unit: seconds.
     *   Default value: **0**.
     *
     * @example 0
     *
     * @var string
     */
    public $start;

    /**
     * @description The width of the opening part.
     *
     *   Valid values: values in the range of (0,4096), -1, and full.
     *   A value of -1 indicates that the original width of the opening part is retained.
     *   A value of full indicates that the width of the opening part equals the width of the main part.
     *   Default value: **-1**.
     *
     * @example -1
     *
     * @var string
     */
    public $width;

    /**
     * @description The OSS URL of the opening part.
     *
     * @example http://example.oss-cn-shanghai.aliyuncs.com/t5.mp4
     *
     * @var string
     */
    public $openUrl;
    protected $_name = [
        'height'  => 'Height',
        'start'   => 'Start',
        'width'   => 'Width',
        'openUrl' => 'openUrl',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->height) {
            $res['Height'] = $this->height;
        }
        if (null !== $this->start) {
            $res['Start'] = $this->start;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }
        if (null !== $this->openUrl) {
            $res['openUrl'] = $this->openUrl;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return opening
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['Start'])) {
            $model->start = $map['Start'];
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }
        if (isset($map['openUrl'])) {
            $model->openUrl = $map['openUrl'];
        }

        return $model;
    }
}
