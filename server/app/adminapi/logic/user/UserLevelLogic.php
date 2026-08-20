<?php

namespace app\adminapi\logic\user;

use app\common\logic\BaseLogic;
use app\common\model\coze\CozeAgent;
use app\common\model\kb\KbRobot;
use app\common\model\member\MemberUser;
use app\common\model\user\User;
use app\common\model\user\UserLevel;
use app\common\service\MemberService;
use Exception;
use think\facade\Db;

/**
 * 用户等级逻辑层（含订阅配额）
 */
class UserLevelLogic extends BaseLogic
{
    public static function normalizeParams(array $params): array
    {
        if (empty($params['level_name']) && !empty($params['name'])) {
            $params['level_name'] = $params['name'];
        }
        return $params;
    }

    public static function formatDetail(array $data): array
    {
        if (!empty($data)) {
            $data['name'] = $data['level_name'] ?? '';
        }
        return $data;
    }

    private static function quotaPayload(array $params): array
    {
        $allowed = $params['allowed_models'] ?? null;
        return [
            'grant_tokens' => (float)($params['grant_tokens'] ?? 0),
            'grant_cycle' => (int)($params['grant_cycle'] ?? 0),
            'max_robots' => (int)($params['max_robots'] ?? 0),
            'max_knowledges' => (int)($params['max_knowledges'] ?? 0),
            'max_personas' => (int)($params['max_personas'] ?? 0),
            'max_mobiles' => (int)($params['max_mobiles'] ?? 0),
            'max_digital_humans' => (int)($params['max_digital_humans'] ?? 0),
            'max_voices' => (int)($params['max_voices'] ?? 0),
            // 只存模型 id,名称由接口返回时按本地 models 表查询
            'allowed_models' => MemberService::parseAllowedModelIds(is_array($allowed) ? $allowed : []),
            'status' => (int)($params['status'] ?? 1),
        ];
    }

    public static function detail(int $id): array
    {
        $level = UserLevel::find($id);
        return $level ? self::formatDetail($level->toArray()) : [];
    }

    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            $params = self::normalizeParams($params);
            $sort = intval($params['sort'] ?? 0);
            $levelName = trim($params['level_name'] ?? '');
            if ($levelName === '') {
                throw new Exception('请填写等级名称');
            }

            $now = time();
            UserLevel::create(array_merge([
                'level_name' => $levelName,
                'sort' => $sort,
                'create_time' => $now,
                'update_time' => $now,
            ], self::quotaPayload($params)));

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            $params = self::normalizeParams($params);
            $level = UserLevel::find($params['id']);
            if (!$level) {
                throw new Exception('用户等级不存在');
            }

            $sort = intval($params['sort'] ?? $level->sort);
         
            $levelName = trim($params['level_name'] ?? $level->level_name);
            if ($levelName === '') {
                throw new Exception('请填写等级名称');
            }

            $level->save(array_merge([
                'level_name' => $levelName,
                'sort' => $sort,
                'update_time' => time(),
            ], self::quotaPayload($params)));

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete($id): bool
    {
        Db::startTrans();
        try {
            $ids = is_array($id) ? $id : [$id];

            foreach ($ids as $item) {
                $item = intval($item);
                $level = UserLevel::find($item);
                if (!$level) {
                    throw new Exception('用户等级不存在');
                }
                if ((int)$level->is_default === 1) {
                    throw new Exception('系统默认等级不可删除');
                }
                if (User::where('level_id', $item)->count()) {
                    throw new Exception('该会员等级已绑定用户，不能删除');
                }
                if (MemberUser::where('level_id', $item)->count()) {
                    throw new Exception('该等级已有订阅用户，不能删除');
                }
                if (KbRobot::whereRaw("FIND_IN_SET({$item}, member_level_ids)")->count()
                    || CozeAgent::whereRaw("FIND_IN_SET({$item}, member_level_ids)")->count()) {
                    throw new Exception('该会员等级已被智能体权限使用，不能删除');
                }
                $level->delete();
            }

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }
}
