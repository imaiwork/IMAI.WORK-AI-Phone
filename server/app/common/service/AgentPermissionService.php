<?php

namespace app\common\service;

use app\common\model\user\User;
use app\common\model\user\UserLevel;

/**
 * 智能体会员权限服务
 */
class AgentPermissionService
{
    public const PERMISSIONS_UNLIMITED = 0;
    public const PERMISSIONS_MEMBER = 1;
    public const DEFAULT_LEVEL_ID = -1;

    public static function getPermissionsText(int $permissions): string
    {
        $map = [
            self::PERMISSIONS_UNLIMITED => '全部可用',
            self::PERMISSIONS_MEMBER => '仅会员等级可用',
        ];

        return $map[$permissions] ?? '未知权限';
    }

    public static function preparePermissionData(array $params): array
    {
        $permissions = isset($params['permissions'])
            ? intval($params['permissions'])
            : self::PERMISSIONS_UNLIMITED;

        if (!in_array($permissions, [self::PERMISSIONS_UNLIMITED, self::PERMISSIONS_MEMBER], true)) {
            throw new \Exception('权限类型错误');
        }

        $levelIds = self::levelIdsToArray(
            $params['member_level_ids'] ?? $params['level_ids'] ?? $params['user_level_ids'] ?? []
        );
        if ($permissions === self::PERMISSIONS_UNLIMITED) {
            $levelIds = [];
        }

        if ($permissions === self::PERMISSIONS_MEMBER && empty($levelIds)) {
            throw new \Exception('请选择可用的会员等级');
        }

        if (!empty($levelIds)) {
            $existsIds = UserLevel::whereIn('id', $levelIds)->column('id');
            if (count($existsIds) !== count($levelIds)) {
                throw new \Exception('会员等级不存在或已删除');
            }
        }

        return [
            'permissions' => $permissions,
            'member_level_ids' => implode(',', $levelIds),
        ];
    }

    public static function levelIdsToArray($levelIds): array
    {
        if (is_string($levelIds)) {
            $levelIds = trim($levelIds);
            if ($levelIds === '') {
                return [];
            }
            $decoded = json_decode($levelIds, true);
            $levelIds = json_last_error() === JSON_ERROR_NONE && is_array($decoded)
                ? $decoded
                : explode(',', $levelIds);
        }

        if (!is_array($levelIds)) {
            return [];
        }

        $levelIds = array_map('intval', $levelIds);
        $levelIds = array_filter($levelIds, fn($id) => $id > 0);

        return array_values(array_unique($levelIds));
    }

    public static function getUserLevelId(int $userId): int
    {
        if ($userId <= 0) {
            return self::DEFAULT_LEVEL_ID;
        }

        $levelId = User::where('id', $userId)->value('level_id');
        return $levelId === null ? self::DEFAULT_LEVEL_ID : intval($levelId);
    }

    public static function canAccess($agent, int $userId): bool
    {
        $permissions = intval(self::readValue($agent, 'permissions', self::PERMISSIONS_UNLIMITED));
        if ($permissions === self::PERMISSIONS_UNLIMITED) {
            return true;
        }

        $userLevelId = self::getUserLevelId($userId);
        if ($userLevelId <= 0) {
            return false;
        }

        $levelIds = self::levelIdsToArray(self::readValue($agent, 'member_level_ids', ''));
        return in_array($userLevelId, $levelIds, true);
    }

    public static function assertCanAccess($agent, int $userId, string $name = '智能体'): void
    {
        if (!self::canAccess($agent, $userId)) {
            throw new \Exception('当前会员等级暂无权限使用该' . $name);
        }
    }

    public static function applyPermissionWhere($query, int $userId, string $permissionsField = 'permissions', string $levelIdsField = 'member_level_ids')
    {
        return self::applyPermissionWhereByLevelId($query, self::getUserLevelId($userId), $permissionsField, $levelIdsField);
    }

    public static function applyPermissionWhereByLevelId($query, int $levelId, string $permissionsField = 'permissions', string $levelIdsField = 'member_level_ids')
    {
        $levelId = intval($levelId);

        return $query->where(function ($query) use ($permissionsField, $levelIdsField, $levelId) {
            $query->where($permissionsField, self::PERMISSIONS_UNLIMITED)
                ->whereOr(function ($query) use ($permissionsField, $levelIdsField, $levelId) {
                    $query->where($permissionsField, self::PERMISSIONS_MEMBER);
                    if ($levelId > 0) {
                        $query->whereRaw("FIND_IN_SET({$levelId}, {$levelIdsField})");
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                });
        });
    }

    private static function readValue($data, string $key, $default = null)
    {
        if (is_array($data)) {
            return $data[$key] ?? $default;
        }

        if ($data instanceof \ArrayAccess && isset($data[$key])) {
            return $data[$key];
        }

        if (is_object($data) && isset($data->{$key})) {
            return $data->{$key};
        }

        return $default;
    }
}
