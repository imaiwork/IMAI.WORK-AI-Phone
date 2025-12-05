<?php


namespace app\api\validate\kb;

use app\common\validate\BaseValidate;

/**
 * 知识库参数验证器
 */
class KbKnowValidate extends BaseValidate
{
    protected $rule = [
        'id'              => 'require',
        'fd_id'           => 'require',
        'kb_id'           => 'require',
//        'to_fd_id'        => 'require|number',
        'image'           => 'require|max:250',
        'name'            => 'require|max:100',
        'intro'           => 'max:500',
        'embedding_model' => 'require',
        'documents_model' => 'require'
    ];

    protected $message = [
        'kb_id.require'           => '请指定知识库',
        'fd_id.require'           => '请选择文件',
//        'to_fd_id.require'        => '请选择目标文件',
        'image.require'           => '请选择封面图',
        'image.max'               => '封面选择异常',
        'name.require'            => '请填写名称',
        'name.max'                => '名称不能大于100个字符',
        'intro.max'               => '知识库简介不能大于500个字符',
        'embedding_model_id.require'     => '请选择向量模型',
        'documents_model_id.require'     => '请选择处理模型',
        'documents_model_sub_id.require' => '请选择处理模型',
    ];

    /**
     * @notes ID场景
     * @return KbKnowValidate
     * @author kb
     */
    public function sceneId(): KbKnowValidate
    {
        return $this->only(['id']);
    }

    /**
     * @notes 新增场景
     * @return KbKnowValidate
     * @author kb
     */
    public function sceneAdd(): KbKnowValidate
    {
        return $this->only(['image', 'name', 'intro', 'embedding_model_id', 'documents_model_id', 'documents_model_sub_id']);
    }

    /**
     * @notes 编辑场景
     * @return KbKnowValidate
     * @author kb
     */
    public function sceneEdit(): KbKnowValidate
    {
        return $this->only(['id', 'image', 'name', 'intro', 'documents_model_id', 'documents_model_sub_id']);
    }

    /**
     * @notes 重命名场景
     * @return KbKnowValidate
     * @author kb
     */
    public function sceneRename(): KbKnowValidate
    {
        return $this->only(['fd_id', 'name']);
    }

//    /**
//     * @notes 文件迁移场景
//     * @return KbKnowValidate
//     * @author kb
//     */
//    public function sceneMove(): KbKnowValidate
//    {
//        return $this->only(['kb_id', 'fd_id', 'to_fd_id']);
//    }

    /**
     * @notes 文件ID场景
     * @return KbKnowValidate
     * @author kb
     */
    public function sceneFid(): KbKnowValidate
    {
        return $this->only(['fd_id']);
    }

    /**
     * @notes 知识库场景
     * @return KbKnowValidate
     * @author kb
     */
    public function sceneKid(): KbKnowValidate
    {
        return $this->only(['kb_id']);
    }
}