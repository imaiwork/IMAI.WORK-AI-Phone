<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\subtitleConfig\extSubtitleList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output\subtitleConfig\extSubtitleList\extSubtitle\input;
use AlibabaCloud\Tea\Model;

class extSubtitle extends Model
{
    /**
     * @description The character set used by the external caption.
     *
     *   Valid values: UTF-8, GBK, BIG5, and auto.
     *   Default value: **auto**.
     *
     * >  If the value of CharEnc is auto, the detected character set may not be the actual character set. We recommend that you set this parameter to another value.
     * @example auto
     *
     * @var string
     */
    public $charEnc;

    /**
     * @description The font of the hardcoded captions converted from external captions. Default value: SimSum. For more information, see [Fonts](https://help.aliyun.com/document_detail/59950.html).
     *
     * @example "WenQuanYi Zen Hei", "Yuanti SC Regular", "SimSun"
     *
     * @var string
     */
    public $fontName;

    /**
     * @description The input caption file.
     *
     *   SRT and ASS files are supported. For more information, see the Input section of the [Parameter details](https://help.aliyun.com/document_detail/29253.html) topic.
     *   Example: `{"Bucket":"example-bucket","Location":"oss-cn-hangzhou","Object":"example.srt"}`.
     *
     * @var input
     */
    public $input;
    protected $_name = [
        'charEnc'  => 'CharEnc',
        'fontName' => 'FontName',
        'input'    => 'Input',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->charEnc) {
            $res['CharEnc'] = $this->charEnc;
        }
        if (null !== $this->fontName) {
            $res['FontName'] = $this->fontName;
        }
        if (null !== $this->input) {
            $res['Input'] = null !== $this->input ? $this->input->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return extSubtitle
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['CharEnc'])) {
            $model->charEnc = $map['CharEnc'];
        }
        if (isset($map['FontName'])) {
            $model->fontName = $map['FontName'];
        }
        if (isset($map['Input'])) {
            $model->input = input::fromMap($map['Input']);
        }

        return $model;
    }
}
