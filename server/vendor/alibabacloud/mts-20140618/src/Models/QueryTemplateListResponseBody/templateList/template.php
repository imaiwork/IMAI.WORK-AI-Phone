<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\templateList;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\templateList\template\audio;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\templateList\template\container;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\templateList\template\muxConfig;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\templateList\template\transConfig;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryTemplateListResponseBody\templateList\template\video;
use AlibabaCloud\Tea\Model;

class template extends Model
{
    /**
     * @description The audio codec configurations.
     *
     * @var audio
     */
    public $audio;

    /**
     * @description The container format configurations.
     *
     * @var container
     */
    public $container;

    /**
     * @description The time when the template was created.
     *
     * @example 2021-03-04T06:44:43Z
     *
     * @var string
     */
    public $creationTime;

    /**
     * @description The transcoding template ID.
     *
     * @example 16f01ad6175e4230ac42bb5182cd****
     *
     * @var string
     */
    public $id;

    /**
     * @description The transmuxing configurations.
     *
     * @var muxConfig
     */
    public $muxConfig;

    /**
     * @description The name of the template.
     *
     * @example MPS-example
     *
     * @var string
     */
    public $name;

    /**
     * @description The status of the template. Valid values:
     *
     *   **Normal**
     *   **Deleted**
     *
     * @example Normal
     *
     * @var string
     */
    public $state;

    /**
     * @description The general transcoding configurations.
     *
     * @var transConfig
     */
    public $transConfig;

    /**
     * @description The video codec configurations.
     *
     * @var video
     */
    public $video;
    protected $_name = [
        'audio'        => 'Audio',
        'container'    => 'Container',
        'creationTime' => 'CreationTime',
        'id'           => 'Id',
        'muxConfig'    => 'MuxConfig',
        'name'         => 'Name',
        'state'        => 'State',
        'transConfig'  => 'TransConfig',
        'video'        => 'Video',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->audio) {
            $res['Audio'] = null !== $this->audio ? $this->audio->toMap() : null;
        }
        if (null !== $this->container) {
            $res['Container'] = null !== $this->container ? $this->container->toMap() : null;
        }
        if (null !== $this->creationTime) {
            $res['CreationTime'] = $this->creationTime;
        }
        if (null !== $this->id) {
            $res['Id'] = $this->id;
        }
        if (null !== $this->muxConfig) {
            $res['MuxConfig'] = null !== $this->muxConfig ? $this->muxConfig->toMap() : null;
        }
        if (null !== $this->name) {
            $res['Name'] = $this->name;
        }
        if (null !== $this->state) {
            $res['State'] = $this->state;
        }
        if (null !== $this->transConfig) {
            $res['TransConfig'] = null !== $this->transConfig ? $this->transConfig->toMap() : null;
        }
        if (null !== $this->video) {
            $res['Video'] = null !== $this->video ? $this->video->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return template
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Audio'])) {
            $model->audio = audio::fromMap($map['Audio']);
        }
        if (isset($map['Container'])) {
            $model->container = container::fromMap($map['Container']);
        }
        if (isset($map['CreationTime'])) {
            $model->creationTime = $map['CreationTime'];
        }
        if (isset($map['Id'])) {
            $model->id = $map['Id'];
        }
        if (isset($map['MuxConfig'])) {
            $model->muxConfig = muxConfig::fromMap($map['MuxConfig']);
        }
        if (isset($map['Name'])) {
            $model->name = $map['Name'];
        }
        if (isset($map['State'])) {
            $model->state = $map['State'];
        }
        if (isset($map['TransConfig'])) {
            $model->transConfig = transConfig::fromMap($map['TransConfig']);
        }
        if (isset($map['Video'])) {
            $model->video = video::fromMap($map['Video']);
        }

        return $model;
    }
}
