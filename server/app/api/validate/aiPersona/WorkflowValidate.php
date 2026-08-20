<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;
use think\Validate;
/**
 * 自动流程校验
 * Class WorkflowValidate
 * @package app\api\validate\aiPersona
 * @author Qasim
 */
class WorkflowValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require|number',
        'persona_id' => 'require',
        'category_id' => 'require',
        'name' => 'require|max:100',
        'scene' => 'require|number|in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17',
        'schedule' => 'array',
        'schedule_id' => 'require|number',
        'status' => 'require|number',
    ];



    protected $message = [
        'id.require' => 'ID不能为空',
        'id.number' => 'ID必须是数字',
        'persona_id.require' => '人设ID不能为空',
        'category_id.require' => '分类ID不能为空',
        'name.require' => '工作流名称不能为空',
        'name.max' => '工作流名称最多100个字符',
        'scene.require' => '任务场景不能为空',
        'scene.number' => '任务场景必须是数字',
        'scene.in' => '任务场景不合法',
        'schedule.require' => '任务计划不能为空',
        'schedule.array' => '任务计划不能为空数组',
        'schedule_id.require' => '任务计划ID不能为空',
        'schedule_id.number' => '任务计划ID必须是数字',
        'status.require' => '状态不能为空',
        'status.number' => '状态必须是数字',
    ];


    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['persona_id']);
    }
    /**
     * @notes 详情模板
     * @return Validate
     */
    public function sceneDetailTemplate()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 可添加任务场景列表（无需额外参数）
     * @return Validate
     */
    public function sceneSceneLists()
    {
        return $this->only([]);
    }

    /**
     * @notes 分类
     * @return Validate
     */
    public function sceneCategory()
    {
        return $this->only(['persona_id']);
    }

    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only(['persona_id', 'name']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['persona_id', 'name']);
    }

    /**
     * @notes 删除
     * @return Validate
     */
    public function sceneDelete()
    {
        return $this->only([ 'template_id']);
    }

    /**
     * @notes 添加节点
     * @return Validate
     */
    public function sceneAddNode()
    {
        return $this->only(['persona_id', 'template_id', 'scene']);
    }

    /**
     * @notes 重置
     * @return Validate
     */
    public function sceneReset()
    {
        return $this->only(['persona_id', 'template_id']);
    }

    /**
     * @notes 提交
     * @return Validate
     */
    public function sceneUpdateNode()
    {
        return $this->only(['persona_id', 'template_id', 'schedule']);
    }
    /**
     * @notes 改变节点
     * @return Validate
     */
    public function sceneChangeStatusNode()
    {
        return $this->only(['persona_id', 'template_id', 'schedule_id', 'status']);
    }

    /**
     * @notes 使用
     * @return Validate
     */
    public function sceneUse()
    {
        return $this->only(['persona_id', 'template_id']);
    }
    /**
     * @notes 复制模板
     * @return Validate
     */
    public function sceneCopyTemplate()
    {
        return $this->only(['persona_id', 'template_id']);
    }

}

