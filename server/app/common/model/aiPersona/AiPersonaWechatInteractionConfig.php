<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;

class AiPersonaWechatInteractionConfig extends BaseModel
{
    // 加群触发模式
    const GROUP_TRIGGER_MODE_AI = 1;       // AI意图识别
    const GROUP_TRIGGER_MODE_KEYWORD = 2;  // 自定义关键词

    public static function getTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            1 => [
                1 => [
                    '22:10-22:30'
                ]
            ],
            2 => [
                1 => [
                    '11:30-12:00',
                    '18:15-18:30'
                ],
            ],
            3 => [
                1 => [
                    '12:15-12:30'
                ]
            ],
        ];

        return $maps[$personaType][$accountType] ?? [];
    }

    public function setExecTimeAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getExecTimeAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setRobotParamsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getRobotParamsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setCommentSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCommentSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setSalesWechatAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getSalesWechatAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setGroupTriggerKeywordsAttr($value)
    {
        return is_array($value) ? json_encode(self::normalizeGroupTriggerKeywords($value), JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getGroupTriggerKeywordsAttr($value)
    {
        $keywords = $value ? json_decode($value, true) : [];
        return is_array($keywords) ? self::normalizeGroupTriggerKeywords($keywords) : [];
    }

    public static function normalizeGroupTriggerKeywords(array $keywords): array
    {
        return array_values(array_unique(array_filter(array_map(function ($word) {
            return trim((string)$word);
        }, $keywords), 'strlen')));
    }

    /**
     * 自动加群内置默认触发关键词（仅作为配置初始值，用户可增删）
     * @return array
     */
    public static function getDefaultGroupTriggerKeywords(): array
    {
        $words = ['拉我', '加我', '拉一下', '加一下', '带带我', '上车', '带飞', '进群', '群号', '二维码', '链接', '发我', '发一份', '给个', '分享下', '看看', '瞅瞅', '学习学习', '参考参考', '了解了解', '行吗', '可以吗', '能吗', '方便吗', '拉一把', '捎上', '拽进去', '放我进去', '排个队', '跟一个', '滴滴', '群号发我', '邀请链接', '申请入群', '通过一下', '让我进', '资料发我', '文件发我', '模板给个', '案例看看', '笔记分享下', '课件给份', '方案参考下', '教程发下', '攻略给个', '安装包', '提取码', '密码', '软件', '工具', '方法', '经验', '指点', '引荐', '介绍', '推荐', '告知', '解答', '确认', '允许', '批准', '通过', '给个机会', '围观', '参观', '观摩', '旁听', '体验', '试用', '福利', '优惠', '折扣', '码', '券', '组队', '拼单', '搭子', '拉我进去', '拉进群', '拉我一下', '加我一下', '拉个群', '捎我一下', '带我一个', '我也要', '我也进', '我也看看', '我也学习下', '方便拉吗', '能拉吗', '给个码', '扫我', '我加你', '你拉我', '发我链接', '链接发下', '文件发下', '发个资料', '给份资料', '有没有资料', '有群吗', '有资料吗', '能不能拉', '可以拉吗', '让我进去', '通过下', '同意下', '放行', '开门', '等等我', '慢点拉'];
        return self::normalizeGroupTriggerKeywords($words);
    }



    // public function setAddFriendScriptAttr($value)
    // {
    //     return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    // }

    // public function getAddFriendScriptAttr($value)
    // {
    //     return $value ? json_decode($value, true) : [];
    // }


    public static function getCommentRobotPrompt()
    {
        return "你是一个像朋友一样随手评论朋友圈的小伙伴，说话自然、轻松，不刻意夸张，也不会像客服一样正式。\r\n你的评论要像：\r\n* 刷到朋友动态随口说一句\r\n* 带点生活感\r\n* 有时候只是简单回应一下\r\n而不是像在写文案。\r\n\r\n回复目标\r\n根据朋友圈内容，用自然口语评论 1 条。\r\n整体感觉要像：\r\n微信里真实朋友会说的话。\r\n不要像：\r\n机器人在努力活跃气氛。\r\n\r\n回复规则\r\n1 评论长度\r\n控制在 1-2句 为主，最多 3句\r\n优先短句，比如：\r\n* “看着就很好吃呀。”\r\n* “这地方风景不错诶。”\r\n* “哈哈我也遇到过这种事。”\r\n不要写成长段话。\r\n\r\n2 语气要求\r\n语气要像聊天：\r\n可以用：\r\n* 哈哈\r\n* 哇\r\n* 诶\r\n* 好像\r\n* 有点\r\n* 还挺\r\n可以带一个简单 emoji，比如：\r\n😊 😄 😂 🥰 🍜 🌿\r\n但不要每句话都很夸张。\r\n\r\n3 避免“刻意夸人”\r\n不要出现这种评论：\r\n❌\r\n“哇！这也太好看了吧！真的绝了！”\r\n更像真人的方式：\r\n✅\r\n“这张拍得挺好看的诶。”\r\n\r\n4 不要强行提问\r\n真实朋友圈评论：\r\n很多时候 只是回应一下，不一定要问问题。\r\n例如：\r\n✔\r\n“看起来好舒服的一天。”\r\n✔\r\n“这个颜色还挺好看的。”\r\n只有在真的自然时再问一句：\r\n“这家店在哪呀？”\r\n\r\n5 不要像客服\r\n避免这种表达：\r\n❌\r\n“感谢分享”“非常棒”“看起来很不错”\r\n更像朋友的表达：\r\n✔\r\n“感觉挺不错的诶。”\r\n\r\n6 情绪匹配\r\n简单识别情绪即可：\r\n开心类\r\n→ 轻松回应\r\n吐槽类\r\n→ 共情 + 一点点幽默\r\n日常类\r\n→ 随口一句生活感评论\r\n不需要过度分析。\r\n\r\n输出格式\r\n只输出 1条评论\r\n不要解释。\r\n\r\n示例\r\n朋友圈：\r\n今天第一次做提拉米苏成功了🎂\r\n评论：\r\n看着就很好吃呀，我都有点馋了😄\r\n\r\n朋友圈：\r\n又加班到11点\r\n评论：\r\n太真实了…最近好像大家都在加班😂\r\n\r\n朋友圈：\r\n周末去海边走了走\r\n评论：\r\n这天气去海边应该挺舒服的🌊\r\n\r\n核心原则\r\n像朋友一样随口说一句。\r\n自然 > 热情\r\n真实 > 完美\r\n像人 > 像文案";
    }
}
