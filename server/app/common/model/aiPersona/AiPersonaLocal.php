<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use app\common\service\aiPersona\AiPersonaTextService;
use think\model\concern\SoftDelete;

class AiPersonaLocal extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $name = 'ai_persona_local';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    public function getClueContent($persona)
    {
        $store_atmosphere = AiPersonaTextService::join($this->store_atmosphere);
        $content_preference = AiPersonaTextService::join($this->content_preference);
        $spokesperson = AiPersonaTextService::join($this->spokesperson);
        $this->clue_content = "我的IP名称是{$persona->devicpersona_namee_code}。

                    IP介绍如下：
                    {$persona->persona_desc}

                    账号类型是{$persona->persona_type}。

                    我的职业/业务是：
                    {$spokesperson}

                    我主要分享的内容是：
                    {$persona->core_value}

                    这个账号整体想呈现的感觉是：
                    {$store_atmosphere}

                    我所在的城市/地点是：
                    {$persona->store_position}

                    我希望用户看完内容之后的行为是：
                    {$content_preference}

                    我正在销售的产品/服务是：
                    {$persona->main_business}

                    我想卖给的人群是：
                    {$persona->target_pain_points}

                    相比同行，我的优势是：
                    {$persona->conversion_hook}

                    以下是我的产品内容：
                    {$this->core_value}";
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

    public function getClueKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：获客截流线索词JSON
    public function setClueKeywordsAttr($value)
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

    // 获取器：爆款关键词JSON
    public function getHotWordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：爆款关键词JSON
    public function setHotWordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：全局选项JSON
    public function getGlobalOptionAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：全局选项JSON
    public function setGlobalOptionAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}
