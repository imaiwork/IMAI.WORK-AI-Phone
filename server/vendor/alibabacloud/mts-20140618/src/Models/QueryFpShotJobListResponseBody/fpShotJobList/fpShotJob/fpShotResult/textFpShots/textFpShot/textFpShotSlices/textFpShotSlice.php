<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\textFpShots\textFpShot\textFpShotSlices;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryFpShotJobListResponseBody\fpShotJobList\fpShotJob\fpShotResult\textFpShots\textFpShot\textFpShotSlices\textFpShotSlice\inputFragment;
use AlibabaCloud\Tea\Model;

class textFpShotSlice extends Model
{
    /**
     * @description The text snippet that has similar fingerprints to the input text in the text fingerprint library.
     *
     * @example It\\"s snowy today.
     *
     * @var string
     */
    public $duplicationText;

    /**
     * @description The start point in time and duration of the similar text snippet in the input text.
     *
     * @var inputFragment
     */
    public $inputFragment;

    /**
     * @description The input text for text fingerprint analysis.
     *
     * @example It\\"s snowy today.
     *
     * @var string
     */
    public $inputText;

    /**
     * @description The similarity of the input text against the text snippet that has similar fingerprints to the input text in the text fingerprint library.
     *
     * @example 1.0
     *
     * @var string
     */
    public $similarity;
    protected $_name = [
        'duplicationText' => 'DuplicationText',
        'inputFragment'   => 'InputFragment',
        'inputText'       => 'InputText',
        'similarity'      => 'Similarity',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->duplicationText) {
            $res['DuplicationText'] = $this->duplicationText;
        }
        if (null !== $this->inputFragment) {
            $res['InputFragment'] = null !== $this->inputFragment ? $this->inputFragment->toMap() : null;
        }
        if (null !== $this->inputText) {
            $res['InputText'] = $this->inputText;
        }
        if (null !== $this->similarity) {
            $res['Similarity'] = $this->similarity;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return textFpShotSlice
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['DuplicationText'])) {
            $model->duplicationText = $map['DuplicationText'];
        }
        if (isset($map['InputFragment'])) {
            $model->inputFragment = inputFragment::fromMap($map['InputFragment']);
        }
        if (isset($map['InputText'])) {
            $model->inputText = $map['InputText'];
        }
        if (isset($map['Similarity'])) {
            $model->similarity = $map['Similarity'];
        }

        return $model;
    }
}
