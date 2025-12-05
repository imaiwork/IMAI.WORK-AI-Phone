<?php


namespace app\api\validate\kb;

use app\common\validate\BaseValidate;

/**
 * 机器人参数验证器
 */
class KbRobotValidate extends BaseValidate
{
    protected $rule = [
        'id'                => 'require|number',
        'kb_ids'            => 'array',
        'icons'             => 'max:250',
        'image'             => 'require|max:250',
        'name'              => 'require|max:100',
        'intro'             => 'max:500',
        'sort'              => 'number',
        'model_id'          => 'require|number',
        'model_sub_id'      => 'require|number',
        'search_similarity' => 'require|float',
        'search_limits'     => 'require|number',
        'search_empty_type' => 'require|in:1,2',
        'is_show_context'   => 'require|in:0,1',
        'is_show_quote'     => 'require|in:0,1',
        'related_issues_num' => 'require|number',
    ];

    protected $message = [
        'kb_ids.array'              => '知识库选择异常',
        'icons.max'                 => '对话图标选择异常',
        'image.require'             => '请选择机器人图标',
        'image.max'                 => '机器人图标选择异常',
        'name.require'              => '请填写机器人名称',
        'name.max'                  => '机器人名称不能超出100个字符',
        'intro.max'                 => '机器人简介不能超出500个字符',
        'sort.number'               => '排序编号必须为数字',
        'model_id.require'          => '请选择主模型',
        'model_sub_id.require'      => '请选择子模型',
        'search_similarity.require' => '请调整匹配相似度值',
        'search_limits.require'     => '请调整单词搜索数量',
        'search_empty_type.require' => '请选择空搜索回复类型',
        'is_show_context.require'   => '请选择是否实现上下文',
        'is_show_quote.require'     => '请选择是否实现引用词',
        'related_issues_num.require' => '请设置相关问题返回数量',
        'related_issues_num.number'  => '相关问题返回数量必须是数字',
    ];

    public function sceneId(): KbRobotValidate
    {
        return $this->only(['id']);
    }
}