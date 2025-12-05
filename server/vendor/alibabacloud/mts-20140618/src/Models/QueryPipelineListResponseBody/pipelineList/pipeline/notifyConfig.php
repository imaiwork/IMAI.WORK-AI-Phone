<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryPipelineListResponseBody\pipelineList\pipeline;

use AlibabaCloud\Tea\Model;

class notifyConfig extends Model
{
    /**
     * @description The tag string.
     *
     * @example mts-test
     *
     * @var string
     */
    public $mqTag;

    /**
     * @description The queue of messages that are received.
     *
     * @example example1,example2
     *
     * @var string
     */
    public $mqTopic;

    /**
     * @description The name of the queue that is created in MNS.
     *
     * @example example-queue-****
     *
     * @var string
     */
    public $queueName;

    /**
     * @description The name of the topic that is created in MNS.
     *
     * @example example-topic-****
     *
     * @var string
     */
    public $topic;
    protected $_name = [
        'mqTag'     => 'MqTag',
        'mqTopic'   => 'MqTopic',
        'queueName' => 'QueueName',
        'topic'     => 'Topic',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->mqTag) {
            $res['MqTag'] = $this->mqTag;
        }
        if (null !== $this->mqTopic) {
            $res['MqTopic'] = $this->mqTopic;
        }
        if (null !== $this->queueName) {
            $res['QueueName'] = $this->queueName;
        }
        if (null !== $this->topic) {
            $res['Topic'] = $this->topic;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return notifyConfig
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['MqTag'])) {
            $model->mqTag = $map['MqTag'];
        }
        if (isset($map['MqTopic'])) {
            $model->mqTopic = $map['MqTopic'];
        }
        if (isset($map['QueueName'])) {
            $model->queueName = $map['QueueName'];
        }
        if (isset($map['Topic'])) {
            $model->topic = $map['Topic'];
        }

        return $model;
    }
}
