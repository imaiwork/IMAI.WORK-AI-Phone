<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialGroup;
use app\common\model\file\File;
use app\model\VideoSlice;
use think\facade\Db;

class MaterialGroupLogic extends ApiLogic
{
    public static function owns(int $uid, int $personaId, int $groupId, ?int $keepOriginal = null): bool
    {
        if ($groupId <= 0 || $uid <= 0 || $personaId <= 0) {
            return false;
        }
        $query = MaterialGroup::where('id', $groupId)
            ->where('user_id', $uid)
            ->where('persona_id', $personaId);
        if ($keepOriginal !== null) {
            $query->where('keep_original', self::normalizeKeepOriginal($keepOriginal));
        }
        return !$query->findOrEmpty()->isEmpty();
    }

    /**
     * 剪辑原片（视频/图片）才允许挂分组。成品直发、音乐一律 0。
     * 分组不存在或不属于该人设时返回 false。
     */
    public static function resolveForMaterial(int $uid, int $personaId, int $groupId, int $publishMode, int $materialType, int $keepOriginal = Material::KEEP_ORIGINAL_NO): int|false
    {
        if ($publishMode !== Material::PUBLISH_MODE_MAKE_VIDEO
            || !in_array($materialType, [Material::MATERIAL_TYPE_VIDEO, Material::MATERIAL_TYPE_IMAGE], true)
        ) {
            return 0;
        }
        if ($groupId <= 0) {
            return 0;
        }
        if (!self::owns($uid, $personaId, $groupId, $keepOriginal)) {
            self::setError('分组不存在');
            return false;
        }
        return $groupId;
    }

    public static function add(array $params): bool
    {
        try {
            $personaId = (int)$params['persona_id'];
            $name = self::normalizeName((string)($params['name'] ?? ''));
            if ($name === '') {
                self::setError('请填写分组名称');
                return false;
            }
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $personaId)->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }
            $keepOriginal = self::normalizeKeepOriginal($params['keep_original'] ?? Material::KEEP_ORIGINAL_NO);
            if (self::nameTaken($personaId, $name, 0, $keepOriginal)) {
                self::setError('已有同名分组');
                return false;
            }
            $now = time();
            $group = MaterialGroup::create([
                'user_id' => self::$uid,
                'persona_id' => $personaId,
                'name' => $name,
                'keep_original' => $keepOriginal,
                'sort' => 0,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            self::$returnData = $group->toArray();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function update(array $params): bool
    {
        try {
            $group = self::findOwned((int)$params['id']);
            if (!$group) {
                return false;
            }
            $name = self::normalizeName((string)($params['name'] ?? ''));
            if ($name === '') {
                self::setError('请填写分组名称');
                return false;
            }
            if (self::nameTaken((int)$group->persona_id, $name, (int)$group->id, (int)$group->keep_original)) {
                self::setError('已有同名分组');
                return false;
            }
            $group->save([
                'name' => $name,
                'update_time' => time(),
            ]);
            self::$returnData = $group->refresh()->toArray();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        Db::startTrans();
        try {
            $group = self::findOwned($id);
            if (!$group) {
                Db::rollback();
                return false;
            }
            $personaId = (int)$group->persona_id;
            $now = time();
            Material::where('user_id', self::$uid)
                ->where('persona_id', $personaId)
                ->where('group_id', $id)
                ->update(['group_id' => 0, 'update_time' => $now]);
            VideoSlice::where('user_id', self::$uid)
                ->where('persona_id', $personaId)
                ->where('group_id', $id)
                ->update(['group_id' => 0]);
            File::where('source_id', self::$uid)
                ->where('persona_id', $personaId)
                ->where('group_id', $id)
                ->update(['group_id' => 0]);
            $group->delete();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function countBase(int $uid, int $personaId, array $params)
    {
        $query = Material::where('user_id', $uid)
            ->where('persona_id', $personaId)
            ->where('publish_mode', Material::PUBLISH_MODE_MAKE_VIDEO)
            ->whereIn('material_type', [Material::MATERIAL_TYPE_VIDEO, Material::MATERIAL_TYPE_IMAGE]);
        if (array_key_exists('keep_original', $params) && $params['keep_original'] !== '' && $params['keep_original'] !== null) {
            $query->where('keep_original', (int)$params['keep_original']);
        }
        $sourceType = trim((string)($params['source_type'] ?? ''));
        if ($sourceType !== '') {
            $query->where('source_type', $sourceType);
        }
        return Material::applyDefaultVisible($query);
    }

    private static function findOwned(int $id): MaterialGroup|false
    {
        $group = MaterialGroup::where('id', $id)
            ->where('user_id', self::$uid)
            ->findOrEmpty();
        if ($group->isEmpty()) {
            self::setError('分组不存在');
            return false;
        }
        return $group;
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);
        if (mb_strlen($name) > 30) {
            $name = mb_substr($name, 0, 30);
        }
        return $name;
    }

    private static function normalizeKeepOriginal(mixed $value): int
    {
        return (int)$value === Material::KEEP_ORIGINAL_YES
            ? Material::KEEP_ORIGINAL_YES
            : Material::KEEP_ORIGINAL_NO;
    }

    private static function nameTaken(int $personaId, string $name, int $exceptId, int $keepOriginal): bool
    {
        $query = MaterialGroup::where('user_id', self::$uid)
            ->where('persona_id', $personaId)
            ->where('keep_original', self::normalizeKeepOriginal($keepOriginal))
            ->where('name', $name);
        if ($exceptId > 0) {
            $query->where('id', '<>', $exceptId);
        }
        return !$query->findOrEmpty()->isEmpty();
    }
}
