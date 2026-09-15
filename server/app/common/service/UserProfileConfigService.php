<?php

declare(strict_types=1);

namespace app\common\service;

/**
 * 用户资料填写项配置
 * 后台「用户设置-注册资料填写项」控制完善资料弹窗是否收集对应字段,未配置时默认开启
 * Class UserProfileConfigService
 * @package app\common\service
 */
class UserProfileConfigService
{
    /** 资料项-所在门店 */
    public const FIELD_STORE_NAME = 'store_name';

    /** 资料项-资质证书 */
    public const FIELD_CERTIFICATE_IMAGE = 'certificate_image';


    /**
     * @notes 获取资料项开关
     * @return array
     */
    public static function getConfig(): array
    {
        $config = ConfigService::get('user', 'profile_fields', []);
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($config)) {
            $config = [];
        }

        return [
            'store_name_open' => !empty($config['store_name_open'] ?? 1) ? 1 : 0,
            'certificate_image_open' => !empty($config['certificate_image_open'] ?? 1) ? 1 : 0,
        ];
    }


    /**
     * @notes 资料项是否开启收集
     * @param string $field
     * @return bool
     */
    public static function isOpen(string $field): bool
    {
        $config = self::getConfig();

        return match ($field) {
            self::FIELD_STORE_NAME => $config['store_name_open'] === 1,
            self::FIELD_CERTIFICATE_IMAGE => $config['certificate_image_open'] === 1,
            default => false,
        };
    }
}
