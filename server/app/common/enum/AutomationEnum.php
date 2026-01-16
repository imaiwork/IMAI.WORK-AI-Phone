<?php

declare(strict_types=1);

namespace app\common\enum;

/**
 * 自动化功能枚举
 */
class AutomationEnum
{
    // 自动化功能场景常量
    const SOCIAL_MEDIA_RELEASED = 'automation_social_media_released';        // 社媒平台发布
    const SHUT_OFF_COMMENTS = 'automation_shut_off_comments';                // 截流评论
    const SHUT_OFF_OBTAIN = 'automation_shut_off_obtain';                    // 截流私信
    const SHUT_OFF_PRIVATE_LETTER = 'automation_shut_off_private_letter';    // 截流触达
    const FRIENDS_CIRCLE_COMMENTS = 'automation_friends_circle_comments';    // 朋友圈评论
    const FRIENDS_CIRCLE_RELEASED = 'automation_friends_circle_released';    // 朋友圈发布
    const FRIENDS_CIRCLE_PRAISE = 'automation_friends_circle_praise';        // 朋友圈点赞
    const WECHAT_ADD_FRIEND = 'automation_wechat_add_friend';                // 自动加微
    const SOCIAL_MEDIA_OBTAIN = 'automation_social_media_obtain';             // 社媒平台私信接管
    const SOCIAL_MEDIA_NURSING = 'automation_social_media_nursing';           // 社媒平台自动养号
    const OCR_LOCAL = 'automation_orc_local';                                 // 获客视频号OCR
    const OCR_IMG = 'automation_orc_img';                                    // 获客本地OCR

    /**
     * 获取所有自动化功能场景
     * @return array
     */
    public static function getAllScenarios(): array
    {
        return [
            self::SOCIAL_MEDIA_RELEASED => '社媒平台发布',
            self::SHUT_OFF_COMMENTS => '截流评论',
            self::SHUT_OFF_OBTAIN => '截流私信',
            self::SHUT_OFF_PRIVATE_LETTER => '截流触达',
            self::FRIENDS_CIRCLE_COMMENTS => '朋友圈评论',
            self::FRIENDS_CIRCLE_RELEASED => '朋友圈发布',
            self::FRIENDS_CIRCLE_PRAISE => '朋友圈点赞',
            self::WECHAT_ADD_FRIEND => '自动加微',
            self::SOCIAL_MEDIA_OBTAIN => '社媒平台私信接管',
            self::SOCIAL_MEDIA_NURSING => '社媒平台自动养号',
            self::OCR_LOCAL => '获客视频号OCR',
            self::OCR_IMG => '获客本地OCR',
        ];
    }

    /**
     * 获取场景描述
     * @param string $scenario
     * @return string
     */
    public static function getScenarioDesc(string $scenario): string
    {
        $scenarios = self::getAllScenarios();
        return $scenarios[$scenario] ?? '未知场景';
    }

    /**
     * 检查是否为有效的自动化场景
     * @param string $scenario
     * @return bool
     */
    public static function isValidScenario(string $scenario): bool
    {
        return array_key_exists($scenario, self::getAllScenarios());
    }
}