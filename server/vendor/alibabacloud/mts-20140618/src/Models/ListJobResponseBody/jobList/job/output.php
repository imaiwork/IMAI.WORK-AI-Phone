<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job;

use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\audio;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\clip;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\container;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\encryption;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\m3U8NonStandardSupport;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\mergeList;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\muxConfig;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\openingList;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\outputFile;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\outSubtitleList;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\properties;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\subtitleConfig;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\superReso;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\tailSlateList;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\transConfig;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\video;
use AlibabaCloud\SDK\Mts\V20140618\Models\ListJobResponseBody\jobList\job\output\waterMarkList;
use AlibabaCloud\Tea\Model;

class output extends Model
{
    /**
     * @description The audio configurations.
     *
     * @var audio
     */
    public $audio;

    /**
     * @description The sequence number of the audio stream.
     *
     *   Format: `0:a:{Sequence number}`.
     *   The sequence number is the index of the audio stream in the list and starts from 0. If no sequence number is specified, the default audio stream is used.
     *
     * @example 0:a:0
     *
     * @var string
     */
    public $audioStreamMap;

    /**
     * @description The information about the clip.
     *
     * @var clip
     */
    public $clip;

    /**
     * @description The container format configurations.
     *
     * @var container
     */
    public $container;

    /**
     * @description The configurations of watermark blurring. The value is a JSON object. For more information, see [Parameter details](https://help.aliyun.com/document_detail/29253.html).
     *
     * @example {"0": [{"l": 10,"t": 10,"w": 10,"h": 10},{"l": 100,"t": 0.1,"w": 10,"h": 10}],"128000": [],"250000": [{"l": 0.2,"t": 0.1,"w": 0.01,"h": 0.05}]}
     *
     * @var string
     */
    public $deWatermark;

    /**
     * @description The encryption configurations. Only outputs in the M3U8 format are supported.
     *
     * @var encryption
     */
    public $encryption;

    /**
     * @description The non-standard support configuration for M3U8. The value must be a JSON object. For more information, see [Parameter details](https://help.aliyun.com/document_detail/29253.html).
     *
     * @var m3U8NonStandardSupport
     */
    public $m3U8NonStandardSupport;

    /**
     * @description The URL of the merging configuration file. You can specify either MergeList or MergeConfigUrl when you submit the transcoding job.
     *
     *   The configuration file specified by MergeConfigUrl can contain up to 50 clips.
     *   MergeConfigUrl indicates the URL of the configuration file for merging clips.
     *   Make sure that the configuration file is stored as an object in OSS and that MPS can access the OSS object. For information about the file content, see the details of merging parameters.
     *   Example of the content of the merging configuration file: `{"MergeList":[{"MergeURL":"http://exampleBucket****.oss-cn-hangzhou.aliyuncs.com/video_01.mp4"}]}`.
     *
     * @example {"MergeList":[{"MergeURL":"http://exampleBucket****.oss-cn-hangzhou.aliyuncs.com/video_01.mp4"}]}
     *
     * @var string
     */
    public $mergeConfigUrl;

    /**
     * @description The configurations for merging clips.
     *
     * @var mergeList
     */
    public $mergeList;

    /**
     * @description The transmuxing configurations.
     *
     * @var muxConfig
     */
    public $muxConfig;

    /**
     * @description The opening parts.
     *
     * @var openingList
     */
    public $openingList;

    /**
     * @description The output subtitles.
     *
     * @var outSubtitleList
     */
    public $outSubtitleList;

    /**
     * @description The details of the output file.
     *
     * @var outputFile
     */
    public $outputFile;

    /**
     * @description The priority of the job in the MPS queue to which the job is added.
     *
     *   A value of 10 indicates the highest priority.
     *   Default value: **6**.
     *
     * @example 6
     *
     * @var string
     */
    public $priority;

    /**
     * @description The media properties.
     *
     * @var properties
     */
    public $properties;

    /**
     * @description The rotation angle of the video.
     *
     * @example 90
     *
     * @var string
     */
    public $rotate;

    /**
     * @description The subtitle configurations.
     *
     * @var subtitleConfig
     */
    public $subtitleConfig;

    /**
     * @description The configurations for using the resolution of the source video.
     *
     * @var superReso
     */
    public $superReso;

    /**
     * @description The ending parts.
     *
     * @var tailSlateList
     */
    public $tailSlateList;

    /**
     * @description The template ID.
     *
     * @example S00000000-000010
     *
     * @var string
     */
    public $templateId;

    /**
     * @description The general transcoding configurations. If this parameter is specified in the request, the corresponding parameters in the specified transcoding template are overwritten.
     *
     * @var transConfig
     */
    public $transConfig;

    /**
     * @description The custom data.
     *
     * @example test-001
     *
     * @var string
     */
    public $userData;

    /**
     * @description The video configurations.
     *
     * @var video
     */
    public $video;

    /**
     * @description The sequence number of the video stream.
     *
     *   Format: 0:a:{Sequence number}. Example value: 0:a:0.
     *   The sequence number is the index of the video stream in the list and starts from 0.
     *   If no sequence number is specified, the default video stream is used.
     *
     * @example 0:a:0
     *
     * @var string
     */
    public $videoStreamMap;

    /**
     * @description The URL of the watermark configuration file.
     *
     * @example http://example.com/configure
     *
     * @var string
     */
    public $waterMarkConfigUrl;

    /**
     * @description The watermarks.
     *
     * @var waterMarkList
     */
    public $waterMarkList;
    protected $_name = [
        'audio'                  => 'Audio',
        'audioStreamMap'         => 'AudioStreamMap',
        'clip'                   => 'Clip',
        'container'              => 'Container',
        'deWatermark'            => 'DeWatermark',
        'encryption'             => 'Encryption',
        'm3U8NonStandardSupport' => 'M3U8NonStandardSupport',
        'mergeConfigUrl'         => 'MergeConfigUrl',
        'mergeList'              => 'MergeList',
        'muxConfig'              => 'MuxConfig',
        'openingList'            => 'OpeningList',
        'outSubtitleList'        => 'OutSubtitleList',
        'outputFile'             => 'OutputFile',
        'priority'               => 'Priority',
        'properties'             => 'Properties',
        'rotate'                 => 'Rotate',
        'subtitleConfig'         => 'SubtitleConfig',
        'superReso'              => 'SuperReso',
        'tailSlateList'          => 'TailSlateList',
        'templateId'             => 'TemplateId',
        'transConfig'            => 'TransConfig',
        'userData'               => 'UserData',
        'video'                  => 'Video',
        'videoStreamMap'         => 'VideoStreamMap',
        'waterMarkConfigUrl'     => 'WaterMarkConfigUrl',
        'waterMarkList'          => 'WaterMarkList',
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
        if (null !== $this->audioStreamMap) {
            $res['AudioStreamMap'] = $this->audioStreamMap;
        }
        if (null !== $this->clip) {
            $res['Clip'] = null !== $this->clip ? $this->clip->toMap() : null;
        }
        if (null !== $this->container) {
            $res['Container'] = null !== $this->container ? $this->container->toMap() : null;
        }
        if (null !== $this->deWatermark) {
            $res['DeWatermark'] = $this->deWatermark;
        }
        if (null !== $this->encryption) {
            $res['Encryption'] = null !== $this->encryption ? $this->encryption->toMap() : null;
        }
        if (null !== $this->m3U8NonStandardSupport) {
            $res['M3U8NonStandardSupport'] = null !== $this->m3U8NonStandardSupport ? $this->m3U8NonStandardSupport->toMap() : null;
        }
        if (null !== $this->mergeConfigUrl) {
            $res['MergeConfigUrl'] = $this->mergeConfigUrl;
        }
        if (null !== $this->mergeList) {
            $res['MergeList'] = null !== $this->mergeList ? $this->mergeList->toMap() : null;
        }
        if (null !== $this->muxConfig) {
            $res['MuxConfig'] = null !== $this->muxConfig ? $this->muxConfig->toMap() : null;
        }
        if (null !== $this->openingList) {
            $res['OpeningList'] = null !== $this->openingList ? $this->openingList->toMap() : null;
        }
        if (null !== $this->outSubtitleList) {
            $res['OutSubtitleList'] = null !== $this->outSubtitleList ? $this->outSubtitleList->toMap() : null;
        }
        if (null !== $this->outputFile) {
            $res['OutputFile'] = null !== $this->outputFile ? $this->outputFile->toMap() : null;
        }
        if (null !== $this->priority) {
            $res['Priority'] = $this->priority;
        }
        if (null !== $this->properties) {
            $res['Properties'] = null !== $this->properties ? $this->properties->toMap() : null;
        }
        if (null !== $this->rotate) {
            $res['Rotate'] = $this->rotate;
        }
        if (null !== $this->subtitleConfig) {
            $res['SubtitleConfig'] = null !== $this->subtitleConfig ? $this->subtitleConfig->toMap() : null;
        }
        if (null !== $this->superReso) {
            $res['SuperReso'] = null !== $this->superReso ? $this->superReso->toMap() : null;
        }
        if (null !== $this->tailSlateList) {
            $res['TailSlateList'] = null !== $this->tailSlateList ? $this->tailSlateList->toMap() : null;
        }
        if (null !== $this->templateId) {
            $res['TemplateId'] = $this->templateId;
        }
        if (null !== $this->transConfig) {
            $res['TransConfig'] = null !== $this->transConfig ? $this->transConfig->toMap() : null;
        }
        if (null !== $this->userData) {
            $res['UserData'] = $this->userData;
        }
        if (null !== $this->video) {
            $res['Video'] = null !== $this->video ? $this->video->toMap() : null;
        }
        if (null !== $this->videoStreamMap) {
            $res['VideoStreamMap'] = $this->videoStreamMap;
        }
        if (null !== $this->waterMarkConfigUrl) {
            $res['WaterMarkConfigUrl'] = $this->waterMarkConfigUrl;
        }
        if (null !== $this->waterMarkList) {
            $res['WaterMarkList'] = null !== $this->waterMarkList ? $this->waterMarkList->toMap() : null;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return output
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Audio'])) {
            $model->audio = audio::fromMap($map['Audio']);
        }
        if (isset($map['AudioStreamMap'])) {
            $model->audioStreamMap = $map['AudioStreamMap'];
        }
        if (isset($map['Clip'])) {
            $model->clip = clip::fromMap($map['Clip']);
        }
        if (isset($map['Container'])) {
            $model->container = container::fromMap($map['Container']);
        }
        if (isset($map['DeWatermark'])) {
            $model->deWatermark = $map['DeWatermark'];
        }
        if (isset($map['Encryption'])) {
            $model->encryption = encryption::fromMap($map['Encryption']);
        }
        if (isset($map['M3U8NonStandardSupport'])) {
            $model->m3U8NonStandardSupport = m3U8NonStandardSupport::fromMap($map['M3U8NonStandardSupport']);
        }
        if (isset($map['MergeConfigUrl'])) {
            $model->mergeConfigUrl = $map['MergeConfigUrl'];
        }
        if (isset($map['MergeList'])) {
            $model->mergeList = mergeList::fromMap($map['MergeList']);
        }
        if (isset($map['MuxConfig'])) {
            $model->muxConfig = muxConfig::fromMap($map['MuxConfig']);
        }
        if (isset($map['OpeningList'])) {
            $model->openingList = openingList::fromMap($map['OpeningList']);
        }
        if (isset($map['OutSubtitleList'])) {
            $model->outSubtitleList = outSubtitleList::fromMap($map['OutSubtitleList']);
        }
        if (isset($map['OutputFile'])) {
            $model->outputFile = outputFile::fromMap($map['OutputFile']);
        }
        if (isset($map['Priority'])) {
            $model->priority = $map['Priority'];
        }
        if (isset($map['Properties'])) {
            $model->properties = properties::fromMap($map['Properties']);
        }
        if (isset($map['Rotate'])) {
            $model->rotate = $map['Rotate'];
        }
        if (isset($map['SubtitleConfig'])) {
            $model->subtitleConfig = subtitleConfig::fromMap($map['SubtitleConfig']);
        }
        if (isset($map['SuperReso'])) {
            $model->superReso = superReso::fromMap($map['SuperReso']);
        }
        if (isset($map['TailSlateList'])) {
            $model->tailSlateList = tailSlateList::fromMap($map['TailSlateList']);
        }
        if (isset($map['TemplateId'])) {
            $model->templateId = $map['TemplateId'];
        }
        if (isset($map['TransConfig'])) {
            $model->transConfig = transConfig::fromMap($map['TransConfig']);
        }
        if (isset($map['UserData'])) {
            $model->userData = $map['UserData'];
        }
        if (isset($map['Video'])) {
            $model->video = video::fromMap($map['Video']);
        }
        if (isset($map['VideoStreamMap'])) {
            $model->videoStreamMap = $map['VideoStreamMap'];
        }
        if (isset($map['WaterMarkConfigUrl'])) {
            $model->waterMarkConfigUrl = $map['WaterMarkConfigUrl'];
        }
        if (isset($map['WaterMarkList'])) {
            $model->waterMarkList = waterMarkList::fromMap($map['WaterMarkList']);
        }

        return $model;
    }
}
