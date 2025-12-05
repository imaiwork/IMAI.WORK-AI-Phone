<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\subtitleConfig\extSubtitleList;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\subtitleConfig\extSubtitleList\extSubtitle\input;
use AlibabaCloud\Tea\Model;

class extSubtitle extends Model
{
    /**
     * @description The character set used by the external subtitle.
     *
     *   Valid values: **UTF-8**, **GBK**, **BIG5**, and **auto**.
     *   Default value: **auto**.
     *
     * >  If this parameter is set to **auto**, the detected character set may not be the actual character set. We recommend that you set this parameter to another value.
     * @example UTF-8
     *
     * @var string
     */
    public $charEnc;

    /**
     * @description The font of the hardcoded subtitles converted from external subtitles. Default value: **SimSun**. For more information, see [Fonts](https://help.aliyun.com/document_detail/59950.html).
     *
     * @example "WenQuanYi Zen Hei", "Yuanti SC Regular", "SimSun"
     *
     * @var string
     */
    public $fontName;

    /**
     * @description The OSS object that is used as the external subtitle. The value is a JSON object. Files in the **SRT** or **ASS** format are supported.
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
