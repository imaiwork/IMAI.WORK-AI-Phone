<?php

namespace app\adminapi\logic\user;

use app\common\logic\BaseLogic;
use app\common\model\member\MemberUser;
use app\common\model\user\User;
use app\common\model\user\UserLevel;
use app\common\service\MemberService;

class MemberUserLogic extends BaseLogic
{
    public static function grant(array $p): bool
    {
        try {
            $userId  = (int)($p['user_id'] ?? 0);
            $levelId = (int)($p['level_id'] ?? 0);
            $days    = (int)($p['days'] ?? 0);
            $remark  = (string)($p['remark'] ?? '后台手动开通');
            if ($userId <= 0 || $levelId <= 0) {
                throw new \Exception('参数错误:user_id / level_id 必填');
            }

            $level = UserLevel::findOrEmpty($levelId);
            if ($level->isEmpty()) {
                throw new \Exception('会员等级不存在');
            }

            // 默认等级（普通用户）无到期时间，按取消会员处理
            if ((int)$level->is_default === 1) {
                $member = MemberUser::where('user_id', $userId)->findOrEmpty();
                if (!$member->isEmpty() && (int)$member->status === MemberUser::STATUS_ACTIVE) {
                    MemberService::expireAndFreeze($member);
                } else {
                    User::where('id', $userId)->update([
                        'level_id' => $levelId,
                        'update_time' => time(),
                    ]);
                }
                return true;
            }

            if ($days <= 0) {
                throw new \Exception('参数错误:days 必填');
            }
            MemberService::grant($userId, $levelId, $days, MemberUser::SOURCE_ADMIN, $remark);
            MemberService::thawWithinQuota($userId);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function cancel(int $userId): bool
    {
        try {
            $m = MemberUser::where('user_id', $userId)->findOrEmpty();
            if ($m->isEmpty()) {
                throw new \Exception('该用户没有会员记录');
            }
            MemberService::expireAndFreeze($m);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
