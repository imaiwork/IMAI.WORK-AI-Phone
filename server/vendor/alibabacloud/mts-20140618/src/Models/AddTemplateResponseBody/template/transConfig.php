<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\AddTemplateResponseBody\template;

use AlibabaCloud\Tea\Model;

class transConfig extends Model
{
    /**
     * @description The method of resolution adjustment. Default value: **none**. Valid values:
     *
     *   **rescale**: The input video is rescaled.
     *   **crop**: The input video is cropped.
     *   **none**: No change is made.
     *
     * @example rescale
     *
     * @var string
     */
    public $adjDarMethod;

    /**
     * @description Indicates whether the audio bitrate is checked.
     *
     * If this feature is enabled and the system detects that the audio bitrate of the output file is greater than that of the input file, the audio bitrate of the input file is retained after transcoding.
     *
     *   **true**: The audio bitrate is checked.
     *   **false**: The audio bitrate is not checked.
     *   Default value: **false**.
     *
     * @example true
     *
     * @var string
     */
    public $isCheckAudioBitrate;

    /**
     * @description Indicates whether the audio bitrate is checked. If this feature is enabled and the system detects that the audio bitrate of the output file is higher than that of the input file, the input file is not transcoded. This parameter has a higher priority than the **IsCheckAudioBitrate** parameter. Valid values:
     *
     *   **true**: The audio bitrate is checked. In this case, if the audio bitrate of the output file is higher than that of the input file, the input file is not transcoded.
     *   **false**: The audio bitrate is not checked.
     *   Default value: **false**.
     *
     * @example true
     *
     * @var string
     */
    public $isCheckAudioBitrateFail;

    /**
     * @description Indicates whether the resolution is checked.
     *
     *   **true**: The resolution is checked.
     *   **false**: The resolution is not checked.
     *   Default value: **false**.
     *
     * > If this feature is enabled and the system detects that the resolution of the output file is higher than that of the input file based on the width or height, the resolution of the input file is retained after transcoding.
     * @example true
     *
     * @var string
     */
    public $isCheckReso;

    /**
     * @description Indicates whether the resolution is checked.
     *
     *   **true**: The resolution is checked.
     *   **false**: The resolution is not checked.
     *   Default value: **false**.
     *
     * > If this feature is enabled and the system detects that the resolution of the output file is higher than that of the input file based on the width or height, an error that indicates a transcoding failure is returned.
     * @example true
     *
     * @var string
     */
    public $isCheckResoFail;

    /**
     * @description Indicates whether the video bitrate is checked.
     *
     *   **true**: The video bitrate is checked.
     *   **false**: The video bitrate is not checked.
     *   Default value: **false**.
     *
     * > If this feature is enabled and the system detects that the video bitrate of the output file is greater than that of the input file, the video bitrate of the input file is retained after transcoding.
     * @example true
     *
     * @var string
     */
    public $isCheckVideoBitrate;

    /**
     * @description Indicates whether the video bitrate is checked. If this feature is enabled and the system detects that the video bitrate of the output file is higher than that of the input file, the input file is not transcoded. This parameter has a higher priority than the IsCheckVideoBitrate parameter.
     *
     *   **true**: The video bitrate is checked. In this case, if the video bitrate of the output file is higher than that of the input file, the input file is not transcoded.
     *   **false**: The video bitrate is not checked.
     *   Default value: **false**.
     *
     * @example true
     *
     * @var string
     */
    public $isCheckVideoBitrateFail;

    /**
     * @description The transcoding mode. Valid values:
     *
     *   **onepass**
     *   **twopass**
     *   **CBR**
     *   Default value: **onepass**.
     *
     * @example onepass
     *
     * @var string
     */
    public $transMode;
    protected $_name = [
        'adjDarMethod'            => 'AdjDarMethod',
        'isCheckAudioBitrate'     => 'IsCheckAudioBitrate',
        'isCheckAudioBitrateFail' => 'IsCheckAudioBitrateFail',
        'isCheckReso'             => 'IsCheckReso',
        'isCheckResoFail'         => 'IsCheckResoFail',
        'isCheckVideoBitrate'     => 'IsCheckVideoBitrate',
        'isCheckVideoBitrateFail' => 'IsCheckVideoBitrateFail',
        'transMode'               => 'TransMode',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->adjDarMethod) {
            $res['AdjDarMethod'] = $this->adjDarMethod;
        }
        if (null !== $this->isCheckAudioBitrate) {
            $res['IsCheckAudioBitrate'] = $this->isCheckAudioBitrate;
        }
        if (null !== $this->isCheckAudioBitrateFail) {
            $res['IsCheckAudioBitrateFail'] = $this->isCheckAudioBitrateFail;
        }
        if (null !== $this->isCheckReso) {
            $res['IsCheckReso'] = $this->isCheckReso;
        }
        if (null !== $this->isCheckResoFail) {
            $res['IsCheckResoFail'] = $this->isCheckResoFail;
        }
        if (null !== $this->isCheckVideoBitrate) {
            $res['IsCheckVideoBitrate'] = $this->isCheckVideoBitrate;
        }
        if (null !== $this->isCheckVideoBitrateFail) {
            $res['IsCheckVideoBitrateFail'] = $this->isCheckVideoBitrateFail;
        }
        if (null !== $this->transMode) {
            $res['TransMode'] = $this->transMode;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return transConfig
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['AdjDarMethod'])) {
            $model->adjDarMethod = $map['AdjDarMethod'];
        }
        if (isset($map['IsCheckAudioBitrate'])) {
            $model->isCheckAudioBitrate = $map['IsCheckAudioBitrate'];
        }
        if (isset($map['IsCheckAudioBitrateFail'])) {
            $model->isCheckAudioBitrateFail = $map['IsCheckAudioBitrateFail'];
        }
        if (isset($map['IsCheckReso'])) {
            $model->isCheckReso = $map['IsCheckReso'];
        }
        if (isset($map['IsCheckResoFail'])) {
            $model->isCheckResoFail = $map['IsCheckResoFail'];
        }
        if (isset($map['IsCheckVideoBitrate'])) {
            $model->isCheckVideoBitrate = $map['IsCheckVideoBitrate'];
        }
        if (isset($map['IsCheckVideoBitrateFail'])) {
            $model->isCheckVideoBitrateFail = $map['IsCheckVideoBitrateFail'];
        }
        if (isset($map['TransMode'])) {
            $model->transMode = $map['TransMode'];
        }

        return $model;
    }
}
