<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job\output\m3U8NonStandardSupport\TS;
use AlibabaCloud\Tea\Model;

class m3U8NonStandardSupport extends Model
{
    /**
     * @description The non-standard support configurations for TS files. The value is a JSON object. For more information, see the **TS** section of the [Parameter details](https://help.aliyun.com/document_detail/29253.html) topic.
     *
     * @var TS
     */
    public $TS;
    protected $_name = [
        'TS' => 'TS',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->TS) {
            $res['TS'] = null !== $this->TS ? $this->TS->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return m3U8NonStandardSupport
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['TS'])) {
            $model->TS = TS::fromMap($map['TS']);
        }

        return $model;
    }
}
