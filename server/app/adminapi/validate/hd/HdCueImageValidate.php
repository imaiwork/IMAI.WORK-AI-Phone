<?php


namespace app\adminapi\validate\hd;


use app\common\validate\BaseValidate;


/**
 * HdCueImageValidate
 * @desc
 * @author dagouzi
 */
class HdCueImageValidate extends BaseValidate
{

    /**
     * 设置校验规则
     * @var string[]
     */
    protected $rule = [
        'id' => 'require',
        'title' => 'require',
        'status' => 'require',
    ];


    /**
     * 参数描述
     * @var string[]
     */
    protected $field = [
        'id' => 'id',
        'title' => '标题',
        'status' => '状态',
    ];


    /**
     * @desc 添加场景
     * @return HdCueWordValidate
     * @date 2024/5/23 11:41
     * @author dagouzi
     */
    public function sceneAdd()
    {
        return $this->only(['title']);
    }


    /**
     * @desc 编辑场景
     * @return HdCueWordValidate
     * @date 2024/5/23 11:41
     * @author dagouzi
     */
    public function sceneEdit()
    {
        return $this->only(['id', 'title']);
    }


    /**
     * @desc 删除场景
     * @return HdCueWordValidate
     * @date 2024/5/23 11:41
     * @author dagouzi
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }


    /**
     * @desc 详情场景
     * @return HdCueWordValidate
     * @date 2024/5/23 11:41
     * @author dagouzi
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @desc 修改状态
     * @return HdCueWordValidate
     * @date 2024/5/23 11:41
     * @author dagouzi
     */
    public function sceneUpdateStatus()
    {
        return $this->only(['id', 'status']);
    }
}
