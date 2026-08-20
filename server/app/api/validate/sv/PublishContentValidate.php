<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 今日待发布内容校验
 */
class PublishContentValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'source' => 'in:sv,circle',
        'platform' => 'max:20',
        'date' => 'dateFormat:Y-m-d',
        'persona_id' => 'number',
        'title' => 'max:255',
        'topic' => 'max:255',
        'shanjian_video_task_id' => 'number',
    ];

    protected $message = [
        'id.require' => '任务id不能为空',
        'id.number' => '任务id格式错误',
        'source.require' => '任务来源不能为空',
        'source.in' => '任务来源格式错误',
        'date.dateFormat' => '日期格式错误',
        'persona_id.require' => 'IP人设ID不能为空',
        'persona_id.number' => 'IP人设ID格式错误',
        'title.max' => '标题长度不能超过255个字符',
        'topic.max' => '话题长度不能超过255个字符',
        'shanjian_video_task_id.number' => '壹传媒视频任务id格式错误',
    ];

    public function sceneLists()
    {
        return $this->only(['platform', 'date', 'persona_id'])
            ->append('persona_id', 'require');
    }

    public function sceneSave()
    {
        return $this->only(['id', 'source', 'title', 'content', 'topic', 'date', 'persona_id'])
            ->append('source', 'require');
    }

    public function sceneRegenerate()
    {
        return $this->only(['id', 'shanjian_video_task_id', 'persona_id']);
    }
}
