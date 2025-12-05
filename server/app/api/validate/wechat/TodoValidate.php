<?php

namespace app\api\validate\wechat;

use app\common\validate\BaseValidate;

/**
 * 待办校验
 * Class WechatValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class TodoValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'wechat_id' => 'require',
        'friend_id' => 'require',
        'todo_type' => 'require|in:0,1',
        'todo_content' => 'require',
        'todo_time' => 'require',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'wechat_id.require' => '请输入微信ID',
        'friend_id.require' => '请输入好友ID',
        'todo_type.require' => '请输入待办类型',
        'todo_type.in' => '待办类型值只能是0或1',
        'todo_content.require' => '请输入待办内容',
        'todo_time.require' => '请输入待办时间',
    ];


    /**
     * @notes 添加待办
     * @return WechatValidate
     */
    public function sceneAdd()
    {
        return $this->only(['wechat_id', 'friend_id', 'todo_type', 'todo_content', 'todo_time']);
    }

    /**
     * @notes 编辑待办
     * @return WechatValidate
     */
    public function sceneUpdate()
    {
        return $this->only(['id','wechat_id', 'friend_id', 'todo_type', 'todo_content', 'todo_time']);
    }

    /**
     * @notes 删除
     * @return WechatValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 获取列表
     * @return WechatValidate
     */
    public function sceneLists()
    {
        return $this->only(['wechat_id', 'friend_id']);
    }
}
