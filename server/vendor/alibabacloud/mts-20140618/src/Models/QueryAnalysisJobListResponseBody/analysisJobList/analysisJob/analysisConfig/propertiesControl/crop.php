<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryAnalysisJobListResponseBody\analysisJobList\analysisJob\analysisConfig\propertiesControl;

use AlibabaCloud\Tea\Model;

class crop extends Model
{
    /**
     * @description The height of the video image after the margins are cropped out.
     *
     * >  This parameter is invalid if the Mode parameter is set to Auto or None.
     * @example 8
     *
     * @var string
     */
    public $height;

    /**
     * @description The left margin to be cropped out.
     *
     * >  This parameter is invalid if the Mode parameter is set to Auto or None.
     * @example 8
     *
     * @var string
     */
    public $left;

    /**
     * @description The cropping mode. Valid values:
     *
     *   **Auto**: Cropping was automatically run.
     *   **Force**: Cropping was forced to run.
     *   **None**: Cropping was forced not to run.
     *   This parameter is required if the value of the Crop parameter is not an empty JSON {}.
     *
     * @example Auto
     *
     * @var string
     */
    public $mode;

    /**
     * @description The top margin to be cropped out.
     *
     * >  This parameter is invalid if the Mode parameter is set to Auto or None.
     * @example 8
     *
     * @var string
     */
    public $top;

    /**
     * @description The width of the video image after the margins are cropped out.
     *
     * >  This parameter is invalid if the Mode parameter is set to Auto or None.
     * @example 8
     *
     * @var string
     */
    public $width;
    protected $_name = [
        'height' => 'Height',
        'left'   => 'Left',
        'mode'   => 'Mode',
        'top'    => 'Top',
        'width'  => 'Width',
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
        if (null !== $this->left) {
            $res['Left'] = $this->left;
        }
        if (null !== $this->mode) {
            $res['Mode'] = $this->mode;
        }
        if (null !== $this->top) {
            $res['Top'] = $this->top;
        }
        if (null !== $this->width) {
            $res['Width'] = $this->width;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return crop
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Height'])) {
            $model->height = $map['Height'];
        }
        if (isset($map['Left'])) {
            $model->left = $map['Left'];
        }
        if (isset($map['Mode'])) {
            $model->mode = $map['Mode'];
        }
        if (isset($map['Top'])) {
            $model->top = $map['Top'];
        }
        if (isset($map['Width'])) {
            $model->width = $map['Width'];
        }

        return $model;
    }
}
