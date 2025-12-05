<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryMediaWorkflowExecutionListResponseBody\mediaWorkflowExecutionList\mediaWorkflowExecution\activityList\activity;

use AlibabaCloud\Tea\Model;

class MNSMessageResult extends Model
{
    /**
     * @description The error code returned if the MNS message fails to be sent. This parameter is not returned if the MNS message is sent.
     *
     * @example The Topic/Queue config is empty, not send message
     *
     * @var string
     */
    public $errorCode;

    /**
     * @description The error message returned if the MNS message fails to be sent. This parameter is not returned if the MNS message is sent.
     *
     * @example MessageConfigEmpty
     *
     * @var string
     */
    public $errorMessage;

    /**
     * @description The ID of the message that indicates the MNS message is sent. This parameter is not returned if the MNS message fails to be sent.
     *
     * @example 4f3bc83233de4e2f81c7dade443e****
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
