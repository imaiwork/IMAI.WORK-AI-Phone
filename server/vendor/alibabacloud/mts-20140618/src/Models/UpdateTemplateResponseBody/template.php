<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody;

use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template\audio;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template\container;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template\muxConfig;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template\transConfig;
use AlibabaCloud\SDK\Mts\V20140618\Models\UpdateTemplateResponseBody\template\video;
use AlibabaCloud\Tea\Model;

class template extends Model
{
    /**
     * @description The audio codec settings.
     *
     * @var audio
     */
    public $audio;

    /**
     * @description The container format.
     *
     * @var container
     */
    public $container;

    /**
     * @description The container configurations.
     *
     * @example 16f01ad6175e4230ac42bb5182cd****
     *
     * @var string
     */
    public $id;

    /**
     * @description The transmuxing configurations for WebP.
     *
     * @var muxConfig
     */
    public $muxConfig;

    /**
     * @description The audio codec configurations.
     *
     * @example MPS-example
     *
     * @var string
     */
    public $name;

    /**
     * @description The transmuxing configurations.
     *
     * @example Normal
     *
     * @var string
     */
    public $state;

    /**
     * @description Indicates whether the audio bitrate is checked. If the bitrate of the output audio is greater than the bitrate of the input audio, the bitrate of the input audio is retained after transcoding. In this case, the specified audio bitrate does not take effect. This parameter has a lower priority than the IsCheckAudioBitrateFail parameter. Valid values:
     *
     *   **true**: The audio bitrate is checked.
     *
     *   **false**: The audio bitrate is not checked.
     *
     *   Default value:
     *
     *   If the parameter is left empty and the codec of the output audio is different from that of the input audio, the default value is false.
     *   If the parameter is left empty and the codec of the output audio is the same as that of the input audio, the default value is true.
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
        'audio'       => 'Audio',
        'container'   => 'Container',
        'id'          => 'Id',
        'muxConfig'   => 'MuxConfig',
        'name'        => 'Name',
        'state'       => 'State',
        'transConfig' => 'TransConfig',
        'video'       => 'Video',
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
