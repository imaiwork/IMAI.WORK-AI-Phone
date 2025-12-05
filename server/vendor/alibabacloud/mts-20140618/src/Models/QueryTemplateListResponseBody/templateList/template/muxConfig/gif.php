<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\templateList\template\muxConfig;

use AlibabaCloud\Tea\Model;

class gif extends Model
{
    /**
     * @description The color dithering algorithm of the palette. Valid values: sierra and bayer.
     *
     * @example sierra
     *
     * @var string
     */
    public $ditherMode;

    /**
     * @description The duration for which the final frame is paused.
     *
     * @example 0
     *
     * @var string
     */
    public $finalDelay;

    /**
     * @description Indicates whether a custom palette is used.
     *
     * @example false
     *
     * @var string
     */
    public $isCustomPalette;

    /**
     * @description The loop count.
     *
     * @example 0
     *
     * @var string
     */
    public $loop;
    protected $_name = [
        'ditherMode'      => 'DitherMode',
        'finalDelay'      => 'FinalDelay',
        'isCustomPalette' => 'IsCustomPalette',
        'loop'            => 'Loop',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->ditherMode) {
            $res['DitherMode'] = $this->ditherMode;
        }
        if (null !== $this->finalDelay) {
            $res['FinalDelay'] = $this->finalDelay;
        }
        if (null !== $this->isCustomPalette) {
            $res['IsCustomPalette'] = $this->isCustomPalette;
        }
        if (null !== $this->loop) {
            $res['Loop'] = $this->loop;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return gif
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['DitherMode'])) {
            $model->ditherMode = $map['DitherMode'];
        }
        if (isset($map['FinalDelay'])) {
            $model->finalDelay = $map['FinalDelay'];
        }
        if (isset($map['IsCustomPalette'])) {
            $model->isCustomPalette = $map['IsCustomPalette'];
        }
        if (isset($map['Loop'])) {
            $model->loop = $map['Loop'];
        }

        return $model;
    }
}
