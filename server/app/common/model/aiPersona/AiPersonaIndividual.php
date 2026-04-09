<?php
namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaIndividual extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $name = 'ai_persona_individual';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    public function getClueContent()
    {
        $personality_tags = implode(',', $this->personality_tags);
        $monetize_paths = implode(',', $this->monetize_paths);
        $identity = implode(',', $this->identity);
        $this->clue_content = "\"我的昵称/网名是{$this->nickname}，真实身份/职业是{$identity}，希望以{$personality_tags}的性格标签语气生成内容。

        我能提供的核心价值如下：
        {$this->core_value}

        想吸引的粉丝是{$this->target_audience}，主要变现路径：{$monetize_paths}。

        个人高光/逆袭故事：{$this->highlight_story}。\"

        我的产品内容：{$this->main_business}";
        return $this->clue_content;
    }

    // 获取器：性格标签JSON
    public function getIdentityAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：性格标签JSON
    public function setIdentityAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：真实身份JSON
    public function getPersonalityTagsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：真实身份JSON
    public function setPersonalityTagsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：变现路径JSON
    public function getMonetizePathsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：变现路径JSON
    public function setMonetizePathsAttr($value)
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