<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaLocal extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $name = 'ai_persona_local';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    public function getClueContent()
    {
        $store_atmosphere = implode(',', $this->store_atmosphere);
        $content_preference = implode(',', $this->content_preference);
        $spokesperson = implode(',', $this->spokesperson);
        $this->clue_content = "我的门店及所在商圈是{$this->store_name}，由{$spokesperson}出镜揽客，希望以{$store_atmosphere}的门店氛围感生成内容。

            我们的招牌特色如下：

            {$this->signature_feature}

            主要想吸引进店的客户是{$this->target_customer}，偏好的引流内容：{$content_preference}。

            开店初衷/门店优势：{$this->open_story}。";
        return $this->clue_content;
    }

    // 获取器：出镜揽客JSON
    public function getSpokespersonAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：出镜揽客JSON
    public function setSpokespersonAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：门店氛围感JSON
    public function getStoreAtmosphereAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：门店氛围感JSON
    public function setStoreAtmosphereAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：引流内容偏好JSON
    public function getContentPreferenceAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：引流内容偏好JSON
    public function setContentPreferenceAttr($value)
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
