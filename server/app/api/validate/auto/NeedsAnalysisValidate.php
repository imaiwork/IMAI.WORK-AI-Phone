<?php

namespace app\api\validate\auto;

use app\common\validate\BaseValidate;

/**
 * 自动任务需求分析请求校验
 * Class TouchValidate
 * @package app\api\validate\auto
 */
class NeedsAnalysisValidate extends BaseValidate
{

    protected $rule = [
        'content'           => 'require',
        'contents'          => 'require',
        'business_type'     => 'require',
        'account_stage'     => 'require',
        'target_audience'   => 'require',
        'core_pain'         => 'require',
        'main_platform'     => 'require',
        'platform_focus'    => 'require',
        'content_style'     => 'require',
        'main_block'        => 'require',
        'risk_tolerance'    => 'require',
        'benchmark_account' => 'require',
    ];

    protected $message = [
        'content.require'           => '请输入聊天内容',
        'contents.require'          => '请输入一句话分析内容',
        'business_type.require'     => '业务类型',
        'account_stage.require'     => '账号阶段',
        'target_audience.require'   => '客户对象',
        'core_pain.require'         => '客户核心痛点',
        'main_platform.require'     => '主要运营平台',
        'platform_focus.require'    => '平台侧重点',
        'content_style.require'     => '内容风格倾向',
        'main_block.require'        => '当前最大运营卡点',
        'risk_tolerance.require'    => '账号风险承受度',
        'benchmark_account.require' => '对标账号',
    ];

    /**
     * @notes 聊天
     * @return NeedsAnalysisValidate
     */
    public function sceneChat()
    {
        return $this->only(['content']);
    }

    /**
     * @notes 聊天返回
     * @return NeedsAnalysisValidate
     */
    public function sceneRetrieve()
    {
        return $this->only(['chat_id']);
    }

    /**
     * @notes 分析
     * @return NeedsAnalysisValidate
     */
    public function sceneAnalysis()
    {
        return $this->only(['contents']);
    }

    public function sceneUpdate()
    {
        return $this->only([
                               'conversation_id',
                               'business_type',
                               'account_stage',
                               'target_audience',
                               'core_pain',
                               'main_platform',
                               'platform_focus',
                               'content_style',
                               'main_block',
                               'risk_tolerance',
                               'benchmark_account'
                           ]);
    }
}

