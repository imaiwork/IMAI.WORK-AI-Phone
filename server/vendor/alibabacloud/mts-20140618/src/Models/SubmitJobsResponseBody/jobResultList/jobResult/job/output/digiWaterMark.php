<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\digiWaterMark\inputFile;
use AlibabaCloud\Tea\Model;

class digiWaterMark extends Model
{
    /**
     * @description The transparency of the text or image.
     *
     *   Value values: **(0,1]**.
     *   Default value: **1.0**.
     *
     * @example 1.0
     *
     * @var string
     */
    public $alpha;

    /**
     * @description The details of the input file.
     *
     * @var inputFile
     */
    public $inputFile;

    /**
     * @description The type of the watermark. If this parameter is specified in the request, the corresponding parameter in the watermark template is overwritten. Valid values:
     *
     *   **Image** (default)
     *   **Text**
     *
     * @example Image
     *
     * @var string
     */
    public $type;
    protected $_name = [
        'alpha'     => 'Alpha',
        'inputFile' => 'InputFile',
        'type'      => 'Type',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->alpha) {
            $res['Alpha'] = $this->alpha;
        }
        if (null !== $this->inputFile) {
            $res['InputFile'] = null !== $this->inputFile ? $this->inputFile->toMap() : null;
        }
        if (null !== $this->type) {
            $res['Type'] = $this->type;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return digiWaterMark
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Alpha'])) {
            $model->alpha = $map['Alpha'];
        }
        if (isset($map['InputFile'])) {
            $model->inputFile = inputFile::fromMap($map['InputFile']);
        }
        if (isset($map['Type'])) {
            $model->type = $map['Type'];
        }

        return $model;
    }
}
