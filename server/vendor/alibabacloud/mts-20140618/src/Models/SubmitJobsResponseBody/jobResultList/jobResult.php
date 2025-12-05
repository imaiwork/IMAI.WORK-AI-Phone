<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList;

use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsResponseBody\jobResultList\jobResult\job;
use AlibabaCloud\Tea\Model;

class jobResult extends Model
{
    /**
     * @description The error code returned if the job failed to be created. This parameter is not returned if the job was created.
     *
     * @example InvalidParameter.NullValue
     *
     * @var string
     */
    public $code;

    /**
     * @description The details of the job. If the job fails to be submitted, no job ID is generated.
     *
     * @var job
     */
    public $job;

    /**
     * @description The error message returned if the job failed to be created. This parameter is not returned if the job was created.
     *
     * @example The specified parameter "%s" cannot be null.
     *
     * @var string
     */
    public $message;

    /**
     * @description Indicates whether the job was successful. Valid values:
     *
     *   **true**
     *   **false**
     *
     * @example true
     *
     * @var bool
     */
    public $success;
    protected $_name = [
        'code'    => 'Code',
        'job'     => 'Job',
        'message' => 'Message',
        'success' => 'Success',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->code) {
            $res['Code'] = $this->code;
        }
        if (null !== $this->job) {
            $res['Job'] = null !== $this->job ? $this->job->toMap() : null;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->success) {
            $res['Success'] = $this->success;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return jobResult
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Code'])) {
            $model->code = $map['Code'];
        }
        if (isset($map['Job'])) {
            $model->job = job::fromMap($map['Job']);
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['Success'])) {
            $model->success = $map['Success'];
        }

        return $model;
    }
}
