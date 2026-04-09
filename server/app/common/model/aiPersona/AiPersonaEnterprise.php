<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaEnterprise extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $name = 'ai_persona_enterprise';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    public function getClueContent()
    {
        $brand_tone = implode(',', $this->brand_tone);
        $account_goal = implode(',', $this->account_goal);
        $spokesperson = implode(',', $this->spokesperson);
        $this->clue_content = "我的企业/品牌名称是{$this->brand_name}，由{$spokesperson}代表公司出镜，希望以{$brand_tone}的品牌调性生成内容。

        主打的产品/解决方案如下：

        {$this->main_product}

        目标客户画像是{$this->target_customer}，账号核心目的：{$account_goal}。

        行业背书/标杆案例：{$this->industry_case}。";
        return $this->clue_content;
    }

    // 获取器：代表公司出镜JSON
    public function getSpokespersonAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：代表公司出镜JSON
    public function setSpokespersonAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：品牌调性JSON
    public function getBrandToneAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：品牌调性JSON
    public function setBrandToneAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：账号核心目的JSON
    public function getAccountGoalAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：账号核心目的JSON
    public function setAccountGoalAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：获客截流线索词JSON
    public function getClueAcquireKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：获客截流线索词JSON
    public function setClueAcquireKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }


    // 获取器：获客截流匹配词JSON
    public function getClueInterceptKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：获客截流匹配词JSON
    public function setClueInterceptKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：获客截流评论词JSON
    public function getClueCommentScriptsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：获客截流评论词JSON
    public function setClueCommentScriptsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：获客截流私信话术JSON
    public function getClueDmScriptsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：获客截流私信话术JSON
    public function setClueDmScriptsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：好友申请备注JSON
    public function getWechatAddFriendScriptAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：好友申请备注JSON
    public function setWechatAddFriendScriptAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：朋友圈评论话术JSON
    public function getWechatCommentSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：朋友圈评论话术JSON
    public function setWechatCommentSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}
