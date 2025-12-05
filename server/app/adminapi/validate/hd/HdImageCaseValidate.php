<?php


namespace app\adminapi\validate\hd;


use app\common\validate\BaseValidate;


/**
 * HdImageCaseValidate
 * @desc
 * @author dagouzi
 */
class HdImageCaseValidate extends BaseValidate
{

    /**
     * 设置校验规则
     * @var string[]
     */
    protected $rule = [
        'id'            => 'require',
        'case_type'     => 'require',
        'params'        => 'require',
        'result_image'  => 'require',
        'status'        => 'require',
    ];


    /**
     * 参数描述
     * @var string[]
     */
    protected $field = [
        'id'            => 'id',
        'case_type'     => '案例类型',
        'params'        => '参数',
        'result_image'  => '成品图片',
        'status'        => '案例状态',
    ];


    /**
     * @desc 添加场景
     * @return HdCueImageCategoryValidate
     * @date 2024/7/26 16:50
     * @author dagouzi
     */
    public function sceneAdd()
    {

        if ($this->request->post('case_type', 0) == 4) {

            return $this->only(['case_type', 'result_image', 'status']);
        }

        return $this->only(['case_type', 'params', 'result_image', 'status']);
    }


    /**
     * @desc 编辑场景
     * @return HdCueImageCategoryValidate
     * @date 2024/7/26 16:50
     * @author dagouzi
     */
    public function sceneEdit()
    {
        if ($this->request->post('case_type', 0) == 4) {

            return $this->only(['case_type', 'result_image', 'status']);
        }
        return $this->only(['id', 'case_type', 'params', 'result_image', 'status']);
    }


    /**
     * @desc 删除场景
     * @return HdCueImageCategoryValidate
     * @date 2024/7/26 16:50
     * @author dagouzi
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }


    /**
     * @desc 详情场景
     * @return HdCueImageCategoryValidate
     * @date 2024/7/26 16:51
     * @author dagouzi
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @desc 修改状态
     * @return HdCueImageCategoryValidate
     * @date 2024/7/26 16:51
     * @author dagouzi
     */
    public function sceneUpdateStatus()
    {
        return $this->only(['id', 'status']);
    }
}
