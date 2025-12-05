<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\textFpShots;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\textFpShots\textFpShot\textFpShotSlices;
use AlibabaCloud\Tea\Model;

class textFpShot extends Model
{
    /**
     * @description The unique primary key of the input text.
     *
     * @example 3e34ac649945b53a1b0f863ce030****
     *
     * @var string
     */
    public $primaryKey;

    /**
     * @description The similarity of the input text against text snippets that have similar fingerprints to the input text in the text fingerprint library.
     *
     * @example 1.0
     *
     * @var string
     */
    public $similarity;

    /**
     * @description The text snippets that have similar fingerprints to the input text in the text fingerprint library.
     *
     * @var textFpShotSlices
     */
    public $textFpShotSlices;
    protected $_name = [
        'primaryKey'       => 'PrimaryKey',
        'similarity'       => 'Similarity',
        'textFpShotSlices' => 'TextFpShotSlices',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->primaryKey) {
            $res['PrimaryKey'] = $this->primaryKey;
        }
        if (null !== $this->similarity) {
            $res['Similarity'] = $this->similarity;
        }
        if (null !== $this->textFpShotSlices) {
            $res['TextFpShotSlices'] = null !== $this->textFpShotSlices ? $this->textFpShotSlices->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return textFpShot
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['PrimaryKey'])) {
            $model->primaryKey = $map['PrimaryKey'];
        }
        if (isset($map['Similarity'])) {
            $model->similarity = $map['Similarity'];
        }
        if (isset($map['TextFpShotSlices'])) {
            $model->textFpShotSlices = textFpShotSlices::fromMap($map['TextFpShotSlices']);
        }

        return $model;
    }
}
