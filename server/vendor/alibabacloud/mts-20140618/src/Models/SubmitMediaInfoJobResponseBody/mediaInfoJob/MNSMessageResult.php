<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitMediaInfoJobResponseBody\mediaInfoJob;

use AlibabaCloud\Tea\Model;

class MNSMessageResult extends Model
{
    /**
     * @description The error code that is returned if the job fails. This parameter is not returned if the job is successful.
     *
     * @example The parameter \\"Input\\" does not conform to the JSON Object specification
     *
     * @var string
     */
    public $errorCode;

    /**
     * @description The error message that is returned if the job fails. This parameter is not returned if the job is successful.
     *
     * @example InvalidParameter.JsonObjectFormatInvalid
     *
     * @var string
     */
    public $errorMessage;

    /**
     * @description The ID of the message that is returned if the job is successful. This parameter is not returned if the job fails.
     *
     * @example 16f01ad6175e4230ac42bb5182cd****
     *
     * @var string
     */
    public $messageId;
    protected $_name = [
        'errorCode'    => 'ErrorCode',
        'errorMessage' => 'ErrorMessage',
        'messageId'    => 'MessageId',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->errorCode) {
            $res['ErrorCode'] = $this->errorCode;
        }
        if (null !== $this->errorMessage) {
            $res['ErrorMessage'] = $this->errorMessage;
        }
        if (null !== $this->messageId) {
            $res['MessageId'] = $this->messageId;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return MNSMessageResult
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['ErrorCode'])) {
            $model->errorCode = $map['ErrorCode'];
        }
        if (isset($map['ErrorMessage'])) {
            $model->errorMessage = $map['ErrorMessage'];
        }
        if (isset($map['MessageId'])) {
            $model->messageId = $map['MessageId'];
        }

        return $model;
    }
}
