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
use think\facade\Event;
use think\facade\Log;

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

            $quota = self::quotaPayload($params);
            $quotaChanged = false;
            foreach (array_unique(array_column(MemberService::ENTITIES, 'quota_field')) as $field) {
                if ((int)$level->getData($field) !== $quota[$field]) {
                    $quotaChanged = true;
                    break;
                }
            }

            $level->save(array_merge([
                'level_name' => $levelName,
                'sort' => $sort,
                'update_time' => time(),
            ], $quota));

            Db::commit();
            if ($quotaChanged) {
                // HttpEnd 在响应发送后执行，PHP-FPM 下无需等待全量用户冻结/解冻才关闭弹窗。
                // 只在实体配额变化时对齐，名称、排序等修改不扫描用户。
                $levelId = (int)$level->id;
                Event::listen('HttpEnd', static function () use ($levelId) {
                    self::applyQuotaToLevelUsers($levelId);
                });
            }
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 把等级配额应用到挂在该等级下的用户（超出的冻结、放开的解冻）。
     * 失败只记日志不抛：等级本身已经保存成功，对齐可由到期/续费兜底。
     */
    private static function applyQuotaToLevelUsers(int $levelId): void
    {
        if ($levelId <= 0) {
            return;
        }
        $handled = 0;
        try {
            // 有效额度取自订阅记录；无有效订阅的用户使用默认等级，user.level_id 可能仍是 -1 或旧等级。
            $isDefault = (int)UserLevel::where('id', $levelId)->value('is_default') === 1;
            $memberUsers = MemberUser::alias('m')
                ->join('user_level l', 'l.id = m.level_id')
                ->whereNull('l.delete_time')
                ->where('m.level_id', '>', 0)
                ->where('m.status', MemberUser::STATUS_ACTIVE)
                ->where(function ($query) {
                    $query->where('m.end_time', '<=', 0)->whereOr('m.end_time', '>=', time());
                })
                ->where('m.level_id', $isDefault ? '<>' : '=', $levelId)
                ->field('m.user_id')
                ->buildSql();
            $users = $isDefault
                ? User::whereNotIn('id', Db::raw($memberUsers))
                : User::whereIn('id', Db::raw($memberUsers));
            $users
                ->field('id')
                ->chunk(200, function ($users) use (&$handled) {
                    foreach ($users as $user) {
                        MemberService::applyQuota((int)$user->id);
                        $handled++;
                    }
                });
        } catch (\Throwable $e) {
            Log::channel('member')->write('等级配额对齐失败:' . json_encode([
                'level_id' => $levelId,
                'handled'  => $handled,
                'error'    => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return;
        }
        Log::channel('member')->write('等级配额对齐完成:' . json_encode([
            'level_id' => $levelId,
            'users'    => $handled,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
