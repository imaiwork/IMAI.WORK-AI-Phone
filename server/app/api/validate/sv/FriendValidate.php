<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 好友校验
 * Class FriendValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class FriendValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'account' => 'require',
        'account_type' => 'require',
        'friend_id' => 'require',
        'friends.*.friend_id' => 'require',
        'friends.*.friend_no' => 'require',
        'friends.*.nickname' => 'require',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'account.require' => '请输入账号ID',
        'account_type.require' => '请输入账号类型',
        'friend_id.require' => '请输入好友ID',
        'friends.*.friend_id.require' => '请输入好友ID',
        'friends.*.friend_no.require' => '请输入好友编号',
        'friends.*.nickname.require' => '请输入好友昵称',
        'remark.require' => '请输入备注',
        'phone.require' => '请输入手机号',
        'birth_date.require' => '请输入生日',
        'contact_address.require' => '请输入联系地址',
    ];


    /**
     * @notes 批量添加好友
     * @return WechatValidate
     */
    public function sceneBatch()
    {
        return $this->only(['account', 'friends']);
    }

    /**
     * @notes 获取信息
     * @return WechatValidate
     */
    public function sceneInfo()
    {
        return $this->only(['account', 'friend_id']);
    }

    /**
     * @notes 更新好友
     * @return WechatValidate
     */
    public function sceneUpdate()
    {
        return $this->only(['account', 'friend_id']);
    }

    /**
     * @notes 删除好友
     * @return WechatValidate
     */
    public function sceneDelete()
    {
        return $this->only(['account', 'friend_id']);
    }


    /**
     * @notes 添加好友
     * @return WechatValidate
     */
    public function sceneAdd()
    {
        return $this->only(['account', 'friend_id']);
    }
}
