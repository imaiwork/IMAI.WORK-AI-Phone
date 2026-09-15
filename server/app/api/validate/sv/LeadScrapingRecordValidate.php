<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 发布设置校验
 * Class LeadScrapingRecordValidate
 * @package app\api\validate\sv
 * @author Qasim
 */
class LeadScrapingRecordValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'accounts' => 'require',
    ];



    protected $message = [
        'id.require' => '任务id不能为空',
        'accounts.require' => '请选择发布账号',
    ];


    /**
     * @notes 添加
     */
    public function sceneAdd()
    {
        return $this->only(['accounts']);
    }

    /**
     * @notes 更新
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'accounts']);
    }

    /**
     * @notes 删除
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 详情
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }
    /**
     * @notes 详情
     */
    public function sceneLists()
    {
        return $this->only(['id']);
    }

    public function sceneRetry() {
        return $this->only(['id']);
    }

    public function sceneReLeadScraping()
    {
        return $this->only(['id']);
    }
}
