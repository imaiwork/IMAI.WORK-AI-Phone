<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListResponseBody\jobList\job\output;

use AlibabaCloud\Tea\Model;

class multiSpeedInfo extends Model
{
    /**
     * @description The error code returned if high-speed transcoding is not enabled.
     *
     * @example Boost.NotNeedSpeed
     *
     * @var string
     */
    public $code;

    /**
     * @description The downgrade policy if high-speed transcoding is not supported.
     *
     * @example NormalSpeed
     *
     * @var string
     */
    public $downgradePolicy;

    /**
     * @description The duration of the output video.
     *
     * @example 21.0
     *
     * @var float
     */
    public $duration;

    /**
     * @description Indicates whether high-speed transcoding is enabled.
     *
     * @example true
     *
     * @var string
     */
    public $enable;

    /**
     * @description The error message returned if high-speed transcoding is not enabled.
     *
     * @example success
     *
     * @var string
     */
    public $message;

    /**
     * @description The actual transcoding speed.
     *
     * @example 6.576886940181647
     *
     * @var float
     */
    public $realSpeed;

    /**
     * @description The speed setting.
     *
     * @example 30
     *
     * @var int
     */
    public $settingSpeed;

    /**
     * @description The amount of time consumed.
     *
     * @example 3.193
     *
     * @var float
     */
    public $timeCost;
    protected $_name = [
        'code'            => 'Code',
        'downgradePolicy' => 'DowngradePolicy',
        'duration'        => 'Duration',
        'enable'          => 'Enable',
        'message'         => 'Message',
        'realSpeed'       => 'RealSpeed',
        'settingSpeed'    => 'SettingSpeed',
        'timeCost'        => 'TimeCost',
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
        if (null !== $this->downgradePolicy) {
            $res['DowngradePolicy'] = $this->downgradePolicy;
        }
        if (null !== $this->duration) {
            $res['Duration'] = $this->duration;
        }
        if (null !== $this->enable) {
            $res['Enable'] = $this->enable;
        }
        if (null !== $this->message) {
            $res['Message'] = $this->message;
        }
        if (null !== $this->realSpeed) {
            $res['RealSpeed'] = $this->realSpeed;
        }
        if (null !== $this->settingSpeed) {
            $res['SettingSpeed'] = $this->settingSpeed;
        }
        if (null !== $this->timeCost) {
            $res['TimeCost'] = $this->timeCost;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return multiSpeedInfo
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['Code'])) {
            $model->code = $map['Code'];
        }
        if (isset($map['DowngradePolicy'])) {
            $model->downgradePolicy = $map['DowngradePolicy'];
        }
        if (isset($map['Duration'])) {
            $model->duration = $map['Duration'];
        }
        if (isset($map['Enable'])) {
            $model->enable = $map['Enable'];
        }
        if (isset($map['Message'])) {
            $model->message = $map['Message'];
        }
        if (isset($map['RealSpeed'])) {
            $model->realSpeed = $map['RealSpeed'];
        }
        if (isset($map['SettingSpeed'])) {
            $model->settingSpeed = $map['SettingSpeed'];
        }
        if (isset($map['TimeCost'])) {
            $model->timeCost = $map['TimeCost'];
        }

        return $model;
    }
}
