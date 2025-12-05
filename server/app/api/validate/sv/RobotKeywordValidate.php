<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 机器人关键词校验
 * Class RobotKeywordValidate
 * @package app\api\validate\sv
 * @author Qasim
 */
class RobotKeywordValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'robot_id' => 'require',
        'match_type' => 'require',
        'keyword' => 'require',
        'reply' => 'require',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'robot_id.require' => '请输入机器人ID',
        'match_type.require' => '请输入匹配模式',
        'keyword.require' => '请输入关键词',
        'reply.require' => '请输入回复内容',
    ];


    /**
     * @notes 添加机器人关键词
     * @return WechatValidate
     */
    public function sceneAdd()
    {
        return $this->only(['match_type', 'keyword', 'reply', 'robot_id']);
    }

    /**
     * @notes 更新机器人关键词
     * @return WechatValidate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'match_type', 'keyword', 'reply']);
    }

    /**
     * @notes 删除机器人关键词
     * @return WechatValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 导入机器人关键词
     * @return WechatValidate
     */
    public function sceneImport()
    {
        return $this->only(['robot_id']);
    }
}
