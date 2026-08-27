<?php

namespace app\common\service\hotspot;

use app\api\logic\aiPersona\AiPersonaLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\Material;
use app\common\service\aiPersona\AiPersonaTextService;
use app\common\service\FileService;

class PersonaService
{
    public static function lists(int $userId): array
    {
        $rows = AiPersona::with([
            'individual' => function ($query) {
                $query->where('delete_time', null);
            },
            'enterprise' => function ($query) {
                $query->where('delete_time', null);
            },
            'local' => function ($query) {
                $query->where('delete_time', null);
            },
        ])
            ->alias('ap')
            ->field([
                'ap.id', 'ap.user_id', 'ap.persona_name', 'ap.persona_type',
                'ap.avatar_url', 'ap.industry', 'ap.main_business', 'ap.status',
            ])
            ->where([
                ['ap.user_id', '=', $userId],
                ['ap.delete_time', '=', null],
                ['ap.status', '=', 1],
            ])
            ->order('ap.create_time desc')
            ->select()
            ->toArray();

        $out = [];
        foreach ($rows as $row) {
            $out[] = self::mapRow($row);
        }
        HotspotLog::write(sprintf('人设列表查询完成：用户=%d 数量=%d', $userId, count($out)));
        return $out;
    }

    public static function avatars(int $userId, int $personaId): array
    {
        if ($userId <= 0) {
            throw new HotspotUpstreamException('请先登录后再选择数字人形象');
        }
        if ($personaId <= 0) {
            throw new HotspotUpstreamException('人设不能为空');
        }
        if (!AiPersonaLogic::detail($personaId, $userId)) {
            throw new HotspotUpstreamException(AiPersonaLogic::getError() ?: '人设不存在');
        }

        $rows = AiPersonaDigitalAvatar::availableQuery()
            ->field(['ad.id', 'ad.avatar_name', 'ad.cover_url'])
            ->where('ad.user_id', $userId)
            ->where('ad.persona_id', $personaId)
            ->order('ad.create_time', 'desc')
            ->limit(100)
            ->select()
            ->toArray();

        $out = [];
        foreach ($rows as $row) {
            $name = trim((string)($row['avatar_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = [
                'id' => (int)($row['id'] ?? 0),
                'name' => $name,
                'img' => FileService::getFileUrl((string)($row['cover_url'] ?? '')),
            ];
        }
        HotspotLog::write(sprintf(
            '人设数字人形象查询完成：用户=%d 人设id=%d 数量=%d',
            $userId,
            $personaId,
            count($out)
        ));
        return $out;
    }

    public static function clipMaterials(int $userId, int $personaId, int $pageNo = 1, int $pageSize = 10): array
    {
        if ($userId <= 0) {
            throw new HotspotUpstreamException('请先登录后再选择混剪素材');
        }
        if ($personaId <= 0) {
            throw new HotspotUpstreamException('人设不能为空');
        }
        if (!AiPersonaLogic::detail($personaId, $userId)) {
            throw new HotspotUpstreamException(AiPersonaLogic::getError() ?: '人设不存在');
        }

        $pageNo = max(1, $pageNo);
        $pageSize = max(1, min(50, $pageSize > 0 ? $pageSize : 10));

        $rows = Material::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('publish_mode', Material::PUBLISH_MODE_MAKE_VIDEO)
            ->whereIn('material_type', [Material::MATERIAL_TYPE_VIDEO, Material::MATERIAL_TYPE_IMAGE])
            ->where('use_status', Material::USE_STATUS_ENABLED)
            ->where(function ($query) {
                $query->where('source_type', '<>', 'slice')
                    ->whereOr('slice_status', Material::SLICE_STATUS_SUCCESS);
            })
            ->field(['id', 'material_name', 'thumbnail_url', 'file_url', 'material_type', 'duration'])
            ->order('id', 'desc')
            ->page($pageNo, $pageSize)
            ->select()
            ->toArray();

        $out = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            // 空名兜底而非跳过：跳过会让分页每页条数失真（前端按返回条数判断是否到底），也导致素材展示不全
            $name = trim((string)($row['material_name'] ?? ''));
            if ($name === '') {
                $name = '素材' . $id;
            }
            $img = trim((string)($row['thumbnail_url'] ?? ''));
            if ($img === '') {
                $img = trim((string)($row['file_url'] ?? ''));
            }
            $out[] = [
                'id' => $id,
                'name' => $name,
                'img' => $img,
                'material_type' => (int)($row['material_type'] ?? 0),
                'duration' => (int)($row['duration'] ?? 0),
            ];
        }
        HotspotLog::write(sprintf(
            '人设混剪素材查询完成：用户=%d 人设id=%d 页=%d 每页=%d 数量=%d',
            $userId,
            $personaId,
            $pageNo,
            $pageSize,
            count($out)
        ));
        return $out;
    }

    public static function fromRequest(array $params): array
    {
        $persona = $params['persona'] ?? null;
        if (is_string($persona) && $persona !== '') {
            $decoded = json_decode($persona, true);
            $persona = is_array($decoded) ? $decoded : ['name' => $persona];
        }
        if (!is_array($persona)) {
            $persona = [];
        }
        foreach (['persona_id', 'personaId'] as $key) {
            if (!self::isBlankId($persona['id'] ?? null)) {
                break;
            }
            if (!self::isBlankId($params[$key] ?? null)) {
                $persona['id'] = $params[$key];
            }
        }
        if (self::isBlankId($persona['id'] ?? null) && !self::isBlankId($persona['persona_id'] ?? null)) {
            $persona['id'] = $persona['persona_id'];
        }
        return $persona;
    }

    public static function hasIdentity(array $persona): bool
    {
        return !self::isBlankId($persona['id'] ?? null) || trim((string)($persona['name'] ?? '')) !== '';
    }

    public static function resolve(int $userId, array $persona): array
    {
        $id = (int)($persona['id'] ?? 0);
        if ($id <= 0) {
            HotspotLog::write('人设解析走请求体：用户=' . $userId . ' 名称=' . (string)($persona['name'] ?? ''));
            return self::normalizePayload($persona);
        }
        if ($userId <= 0) {
            if (trim((string)($persona['name'] ?? '')) === '') {
                HotspotLog::write('人设解析失败：未登录且缺少人设名称 id=' . $id);
                throw new HotspotUpstreamException('人设不存在');
            }
            HotspotLog::write('人设解析走请求体（未登录）：名称=' . (string)($persona['name'] ?? ''));
            return self::normalizePayload($persona);
        }

        if (!AiPersonaLogic::detail($id, $userId)) {
            HotspotLog::write(sprintf(
                '人设解析失败：用户=%d 人设id=%d 原因=%s',
                $userId,
                $id,
                AiPersonaLogic::getError() ?: '人设不存在'
            ));
            throw new HotspotUpstreamException(AiPersonaLogic::getError() ?: '人设不存在');
        }

        $detail = AiPersonaLogic::getReturnData();
        if (!is_array($detail) || empty($detail['id'])) {
            HotspotLog::write(sprintf('人设解析失败：详情为空 用户=%d 人设id=%d', $userId, $id));
            throw new HotspotUpstreamException('人设不存在');
        }

        $mapped = self::mapRow($detail);
        HotspotLog::write(sprintf(
            '人设解析成功：用户=%d 人设id=%d 名称=%s',
            $userId,
            (int)$mapped['id'],
            (string)$mapped['name']
        ));
        return $mapped;
    }

    private static function mapRow(array $row): array
    {
        $type = (int)($row['persona_type'] ?? 0);
        $sub = match ($type) {
            1 => $row['individual'] ?? [],
            2 => $row['enterprise'] ?? [],
            3 => $row['local'] ?? [],
            default => [],
        };
        if (!is_array($sub)) {
            $sub = [];
        }

        $toneRaw = match ($type) {
            1 => $sub['personality_tags'] ?? '',
            2 => $sub['brand_tone'] ?? '',
            3 => $sub['store_atmosphere'] ?? '',
            default => '',
        };

        $tag = trim((string)($row['industry'] ?? ''));
        if ($tag === '') {
            $tag = match ($type) {
                1 => '个人IP',
                2 => '企业服务',
                3 => '本地商家',
                default => '',
            };
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['persona_name'] ?? ''),
            'tag' => $tag,
            'avatar' => FileService::getFileUrl((string)($row['avatar_url'] ?? '')),
            'tone' => AiPersonaTextService::join($toneRaw),
            'business' => (string)($row['main_business'] ?? ''),
        ];
    }

    private static function isBlankId(mixed $id): bool
    {
        if ($id === null || $id === false) {
            return true;
        }
        if (is_string($id) && trim($id) === '') {
            return true;
        }
        // 0/'0' 表示未绑定人设，不能当作有效标识（否则 {persona_id:0} 可绕过「人设不能为空」校验）
        if (is_numeric($id) && (int)$id <= 0) {
            return true;
        }
        return false;
    }

    private static function normalizePayload(array $persona): array
    {
        return [
            'id' => (int)($persona['id'] ?? 0),
            'name' => (string)($persona['name'] ?? ''),
            'tag' => (string)($persona['tag'] ?? ''),
            'avatar' => (string)($persona['avatar'] ?? ''),
            'tone' => AiPersonaTextService::join($persona['tone'] ?? ''),
            'business' => (string)($persona['business'] ?? ''),
        ];
    }
}
