<?php


namespace app\adminapi\validate\channel;


use app\common\validate\BaseValidate;

/**
 * 小程序设置
 * Class MnpSettingsValidate
 * @package app\adminapi\validate\channel
 */
class MnpShareSettingsValidate extends BaseValidate
{
    protected $rule = [
        'share_title' => 'require',
        'share_desc' => 'require',
    ];

    protected $message = [
        'share_title.require' => '请填写分享标题',
        'share_desc.require' => '请填写分享描述',
    ];
}
