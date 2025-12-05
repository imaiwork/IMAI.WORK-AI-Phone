<?php


namespace app\api\validate\knowledge;


use app\common\validate\BaseValidate;


/**
 * KnowledgeBind验证器
 * Class KnowledgeBindValidate
 * @package app\api\validate\knowledge
 */
class KnowledgeBindValidate extends BaseValidate
{

     /**
      * 设置校验规则
      * @var string[]
      */
    protected $rule = [
        'id' => 'require',
    ];


    /**
     * 参数描述
     * @var string[]
     */
    protected $field = [
        'id' => 'id',
    ];


    /**
     * @notes 添加场景
     * @return KnowledgeBindValidate
     * @author my
     * @date 2025/04/18 16:19
     */
    public function sceneAdd()
    {
        return $this->remove('id', true);
    }


    /**
     * @notes 编辑场景
     * @return KnowledgeBindValidate
     * @author my
     * @date 2025/04/18 16:19
     */
    public function sceneEdit()
    {
        return $this->only(['id']);
    }


    /**
     * @notes 删除场景
     * @return KnowledgeBindValidate
     * @author my
     * @date 2025/04/18 16:19
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }


    /**
     * @notes 详情场景
     * @return KnowledgeBindValidate
     * @author my
     * @date 2025/04/18 16:19
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

}