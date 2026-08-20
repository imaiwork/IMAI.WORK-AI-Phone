<?php

namespace app\adminapi\logic\geo;

use app\api\logic\geo\GeoAuthLogic;
use app\common\logic\BaseLogic;
use app\common\model\geo\GeoMedia;
use app\common\service\geo\GeoPhonePublishService;
use think\facade\Db;

/**
 * GEO 媒体库管理。
 * price 不开放编辑(代发已下线,恒 0);provider_code 受 uk_active_provider 唯一索引约束,
 * 保存前先查重给友好提示,避免直接抛数据库错误。
 */
class GeoMediaLogic extends BaseLogic
{
    /** 允许后台读写的字段白名单(price/软删/生成列除外) */
    protected const FIELDS = [
        'name', 'type', 'category', 'region', 'pc_weight', 'mobile_weight',
        'success_rate', 'publish_speed', 'include_status', 'allow_url',
        'can_geo_rank', 'remark', 'status', 'provider_code', 'content_form',
        'platform_code', 'sort',
    ];

    public const TYPES = [
        'media_v'  => '授权直发·内容平台',
        'blog'     => '授权直发·博客',
        'ai_phone' => 'AI手机发布',
        'b2b'      => 'B2B平台',
        'media'    => '自媒体',
        'portal'   => '门户网站',
    ];

    /** 表单下拉选项 */
    public static function options(): array
    {
        $auth = [];
        foreach (GeoAuthLogic::PLATFORMS as $code => $meta) {
            $auth[] = [
                'value' => $code,
                'label' => (string)($meta['label'] ?? $code),
                'forms' => (array)($meta['forms'] ?? []),
            ];
        }
        $phone = [];
        foreach (GeoPhonePublishService::PLATFORMS as $code => $meta) {
            $phone[] = [
                'value' => $code,
                'label' => (string)($meta['label'] ?? $code),
                'forms' => (array)($meta['forms'] ?? []),
            ];
        }
        $types = [];
        foreach (self::TYPES as $value => $label) {
            $types[] = ['value' => $value, 'label' => $label];
        }
        return [
            'types' => $types,
            'auth_platforms' => $auth,
            'phone_platforms' => $phone,
            'content_forms' => [
                ['value' => 'article', 'label' => '图文'],
                ['value' => 'video', 'label' => '视频'],
                ['value' => 'article,video', 'label' => '图文+视频'],
            ],
        ];
    }

    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            $data = self::pick($params);
            self::checkProviderCode((string)($data['provider_code'] ?? ''), 0);
            $data['create_time'] = time();
            $data['update_time'] = time();
            $media = GeoMedia::create($data);
            self::$returnData = $media->toArray();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            $media = GeoMedia::where('id', (int)$params['id'])->findOrEmpty();
            if ($media->isEmpty()) {
                throw new \Exception('该媒体不存在');
            }
            $data = self::pick($params);
            if (array_key_exists('provider_code', $data)) {
                self::checkProviderCode((string)$data['provider_code'], (int)$media->id);
            }
            $data['update_time'] = time();
            $media->save($data);
            self::$returnData = $media->toArray();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function status(array $params): bool
    {
        try {
            $media = GeoMedia::where('id', (int)$params['id'])->findOrEmpty();
            if ($media->isEmpty()) {
                throw new \Exception('该媒体不存在');
            }
            $media->save(['status' => (int)$params['status'] ? 1 : 0, 'update_time' => time()]);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete(array $params): bool
    {
        try {
            $media = GeoMedia::where('id', (int)$params['id'])->findOrEmpty();
            if ($media->isEmpty()) {
                throw new \Exception('该媒体不存在');
            }
            $media->delete();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    protected static function pick(array $params): array
    {
        $data = [];
        foreach (self::FIELDS as $f) {
            if (array_key_exists($f, $params)) {
                $data[$f] = $params[$f];
            }
        }
        foreach (['pc_weight', 'mobile_weight', 'success_rate', 'sort', 'allow_url', 'can_geo_rank', 'status'] as $int) {
            if (array_key_exists($int, $data)) {
                $data[$int] = (int)$data[$int];
            }
        }
        return $data;
    }

    /** 软删行不占码(uk_active_provider 生成列已排除),这里与索引口径一致只查活跃行 */
    protected static function checkProviderCode(string $code, int $exceptId): void
    {
        if ($code === '') {
            return;
        }
        $exist = GeoMedia::where('provider_code', $code)
            ->where('id', '<>', $exceptId)
            ->findOrEmpty();
        if (!$exist->isEmpty()) {
            throw new \Exception("渠道标识 {$code} 已被媒体「{$exist->name}」占用");
        }
    }
}
