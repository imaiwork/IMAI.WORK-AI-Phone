<?php

namespace app\api\logic\team;

use app\common\enum\CardCodeEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\cardcode\CardCode;
use app\common\model\cardcode\CardPackage;
use app\common\model\user\User;
use app\common\model\user\UserLevel;
use think\facade\Db;

/**
 * 团队制卡逻辑
 * - 算力卡(type=5)：从团队主算力预扣
 * - 会员兑换码(type=6)：不扣算力，写入会员等级与天数
 * Class TeamCardLogic
 * @package app\api\logic\team
 */
class TeamCardLogic extends BaseLogic
{
    /**
     * @notes 可选会员等级(生成会员兑换码用)
     */
    public static function memberLevels(int $userId): array
    {
        $owner = User::findOrEmpty($userId);
        if ($owner->isEmpty() || (int)$owner->team_role !== 2) {
            return [];
        }
        return UserLevel::where('status', 1)
            ->field('id,level_name')
            ->order('id asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 团队主生成卡密(算力卡 / 会员兑换码)
     * @return bool
     */
    public static function generate(int $userId, array $params)
    {
        $type = (int)($params['type'] ?? CardCodeEnum::TYPE_DISTRIBUTION_TOKENS);
        if (!in_array($type, [CardCodeEnum::TYPE_DISTRIBUTION_TOKENS, CardCodeEnum::TYPE_MEMBER], true)) {
            self::setError('不支持的卡密类型');
            return false;
        }

        $tokens = (float)($params['tokens'] ?? 0);
        $memberLevelId = (int)($params['member_level_id'] ?? 0);
        $memberDays = (int)($params['member_days'] ?? 0);
        $count = (int)($params['count'] ?? 0);
        $ruleType = (int)($params['rule_type'] ?? 1) === 2 ? 2 : 1;
        $validStart = (int)($params['valid_start_time'] ?? 0);
        $validEnd = (int)($params['valid_end_time'] ?? 0);
        $remark = trim((string)($params['remark'] ?? ''));

        if ($count < 1 || $count > 500) {
            self::setError('单次生成数量为1-500张');
            return false;
        }
        if ($validStart <= 0 || $validEnd <= 0) {
            self::setError('请选择生效时间');
            return false;
        }
        if ($validEnd <= $validStart) {
            self::setError('结束时间需晚于开始时间');
            return false;
        }

        if ($type === CardCodeEnum::TYPE_DISTRIBUTION_TOKENS) {
            if ($tokens <= 0) {
                self::setError('请输入算力值');
                return false;
            }
        } else {
            if ($memberLevelId <= 0) {
                self::setError('请选择会员等级');
                return false;
            }
            if ($memberDays < 1 || $memberDays > 3650) {
                self::setError('会员天数为1-3650天');
                return false;
            }
            $level = UserLevel::where('id', $memberLevelId)->where('status', 1)->findOrEmpty();
            if ($level->isEmpty()) {
                self::setError('会员等级不存在或已停用');
                return false;
            }
        }

        $owner = User::findOrEmpty($userId);
        if ($owner->isEmpty() || $owner->team_role != 2) {
            self::setError('只有团队主可以生成卡密');
            return false;
        }
        try {
            \app\api\logic\TeamLogic::assertOemActive((int)$owner->team_id);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }

        $teamId = (int)$owner->team_id;
        $defaultRemark = $type === CardCodeEnum::TYPE_MEMBER ? '企业会员兑换码' : '企业制卡';

        Db::startTrans();
        try {
            if ($type === CardCodeEnum::TYPE_DISTRIBUTION_TOKENS) {
                $totalTokens = $tokens * $count;
                $owner = User::find($userId);
                if ($owner->tokens < $totalTokens) {
                    throw new \Exception('算力余额不足,本次制卡需 ' . $totalTokens . ' 算力');
                }
                $owner->tokens -= $totalTokens;
                $owner->save();

                AccountLogLogic::add(
                    $owner->id,
                    AccountLogEnum::TOKENS_DEC_DISTRIBUTION_CARD,
                    AccountLogEnum::DEC,
                    $totalTokens,
                    1,
                    '',
                    '企业制卡 x' . $count . '张',
                    ['算力值' => $tokens, '卡密数量' => $count, '消耗算力' => $totalTokens]
                );
            }

            $cards = [];
            for ($i = 0; $i < $count; $i++) {
                $row = [
                    'sn' => card_sn(CardCode::class, 'sn', 'K', 10, $ruleType),
                    'type' => $type,
                    'balance' => $type === CardCodeEnum::TYPE_DISTRIBUTION_TOKENS ? $tokens : 0,
                    'member_level_id' => $type === CardCodeEnum::TYPE_MEMBER ? $memberLevelId : 0,
                    'member_days' => $type === CardCodeEnum::TYPE_MEMBER ? $memberDays : 0,
                    'card_num' => 1,
                    'used_num' => 0,
                    'valid_start_time' => $validStart,
                    'valid_end_time' => $validEnd,
                    'rule_type' => $ruleType,
                    'remark' => $remark !== '' ? $remark : $defaultRemark,
                    'user_id' => $userId,
                    'team_id' => $teamId,
                    'package_id' => 0,
                    'relation_id' => '',
                    'create_time' => time(),
                ];
                $cards[] = $row;
            }
            (new CardCode())->insertAll($cards);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function packageSave(int $userId, array $params)
    {
        $owner = User::findOrEmpty($userId);
        if ($owner->isEmpty() || $owner->team_role != 2) {
            self::setError('只有团队主可以管理套餐');
            return false;
        }
        try {
            \app\api\logic\TeamLogic::assertOemActive((int)$owner->team_id);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }

        $tid = (int)$owner->team_id;
        $data = [
            'name' => trim((string)($params['name'] ?? '')),
            'tokens' => (int)($params['tokens'] ?? 0),
            'expire_time' => (int)($params['expire_time'] ?? 0), // 0=永久
            'status' => 1,
        ];
        if ($data['name'] === '') {
            self::setError('请输入套餐名称');
            return false;
        }
        if ($data['tokens'] <= 0) {
            self::setError('请输入套餐算力');
            return false;
        }
        $id = (int)($params['id'] ?? 0);
        if ($id > 0) {
            $exists = CardPackage::where('id', $id)->where('team_id', $tid)->findOrEmpty();
            if ($exists->isEmpty()) {
                self::setError('套餐不存在');
                return false;
            }
            CardPackage::update($data, ['id' => $id, 'team_id' => $tid]);
        } else {
            $data['team_id'] = $tid;
            $data['sort'] = 0;
            CardPackage::create($data);
        }
        return true;
    }

    /**
     * @notes 团队主删除自有套餐
     * @return bool
     */
    public static function packageDelete(int $userId, int $id)
    {
        $owner = User::findOrEmpty($userId);
        if ($owner->isEmpty() || $owner->team_role != 2) {
            self::setError('只有团队主可以管理套餐');
            return false;
        }
        $pkg = CardPackage::where('id', $id)->where('team_id', $owner->team_id)->findOrEmpty();
        if ($pkg->isEmpty()) {
            self::setError('套餐不存在');
            return false;
        }
        CardPackage::destroy(['id' => $id, 'team_id' => (int)$owner->team_id]);
        return true;
    }

    /**
     * @notes 转移未使用卡密给本团队成员(仅改持有人 user_id，不涉及算力)
     * @return bool
     */
    public static function transfer(int $userId, array $params)
    {
        $cardId = (int)($params['id'] ?? 0);
        $toUserId = (int)($params['to_user_id'] ?? 0);
        if ($cardId <= 0 || $toUserId <= 0) {
            self::setError('参数错误');
            return false;
        }

        $owner = User::findOrEmpty($userId);
        if ($owner->isEmpty() || (int)$owner->team_role !== 2) {
            self::setError('只有团队主可以转移卡密');
            return false;
        }
        $teamId = (int)$owner->team_id;
        if ($teamId <= 0) {
            self::setError('当前不在企业空间');
            return false;
        }
        try {
            \app\api\logic\TeamLogic::assertOemActive($teamId);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }

        // 可转给正式成员,或 OEM 站点归属用户(品牌管理「站点用户」)
        if (!\app\api\logic\TeamLogic::isCardTransferTarget($teamId, $toUserId)) {
            self::setError('目标用户不是本团队成员或站点用户');
            return false;
        }

        $card = CardCode::where('id', $cardId)
            ->where('team_id', $teamId)
            ->whereIn('type', [CardCodeEnum::TYPE_DISTRIBUTION_TOKENS, CardCodeEnum::TYPE_MEMBER])
            ->findOrEmpty();
        if ($card->isEmpty()) {
            self::setError('卡密不存在或无权限');
            return false;
        }
        if ((int)$card->used_num >= (int)$card->card_num) {
            self::setError('仅未使用的卡密可转移');
            return false;
        }
        if ((int)$card->user_id === $toUserId) {
            self::setError('该卡密已属于所选成员');
            return false;
        }

        $card->user_id = $toUserId;
        $card->save();
        return true;
    }

    /**
     * @notes 解散企业时清算未使用卡密
     * - 算力卡：按剩余次数汇总退回给团队主(含已转移到成员/站点用户名下的未兑卡)
     * - 会员兑换码：仅作废，无算力退回
     * - 已兑完的卡密保留历史记录
     * @param User $owner 团队主(会累加 tokens 并 save)
     * @return string 退回算力(两位小数)
     */
    public static function refundUnusedOnDisband(int $teamId, User $owner): string
    {
        if ($teamId <= 0 || $owner->isEmpty()) {
            return '0.00';
        }

        $cards = CardCode::where('team_id', $teamId)
            ->whereIn('type', [CardCodeEnum::TYPE_DISTRIBUTION_TOKENS, CardCodeEnum::TYPE_MEMBER])
            ->select();

        $refundTotal = '0';
        $tokenCardCount = 0;
        $idsToDelete = [];
        foreach ($cards as $card) {
            $remaining = (int)$card->card_num - (int)$card->used_num;
            if ($remaining <= 0) {
                continue;
            }
            $idsToDelete[] = (int)$card->id;
            if ((int)$card->type !== CardCodeEnum::TYPE_DISTRIBUTION_TOKENS) {
                continue;
            }
            $part = bcmul((string)$remaining, (string)$card->balance, 2);
            if (bccomp($part, '0', 2) <= 0) {
                continue;
            }
            $refundTotal = bcadd($refundTotal, $part, 2);
            $tokenCardCount++;
        }

        if ($idsToDelete) {
            CardCode::destroy($idsToDelete);
        }

        // 套餐随站点一并作废,避免解散后残留
        $pkgIds = CardPackage::where('team_id', $teamId)->column('id');
        if ($pkgIds) {
            CardPackage::destroy($pkgIds);
        }

        if (bccomp($refundTotal, '0', 2) > 0) {
            $owner->tokens = bcadd((string)$owner->tokens, $refundTotal, 2);
            $owner->save();
            AccountLogLogic::add(
                (int)$owner->id,
                AccountLogEnum::TOKENS_INC_DISTRIBUTION_CARD_REFUND,
                AccountLogEnum::INC,
                (float)$refundTotal,
                1,
                '',
                '解散企业退回未使用卡密算力',
                ['未使用算力卡' => $tokenCardCount, '退回算力' => $refundTotal],
                $teamId
            );
        }

        return bcadd($refundTotal, '0', 2);
    }

    /**
     * @notes 删除未使用卡密
     * - 算力卡：按剩余次数退回算力给团队主
     * - 会员兑换码：直接删除，无算力退回
     * @return bool
     */
    public static function delete(int $userId, array $params)
    {
        $owner = User::findOrEmpty($userId);
        if ($owner->isEmpty() || (int)$owner->team_role !== 2) {
            self::setError('只有团队主可以删除卡密');
            return false;
        }
        $teamId = (int)$owner->team_id;

        $card = CardCode::where('id', (int)$params['id'])
            ->where('team_id', $teamId)
            ->whereIn('type', [CardCodeEnum::TYPE_DISTRIBUTION_TOKENS, CardCodeEnum::TYPE_MEMBER])
            ->findOrEmpty();

        if ($card->isEmpty()) {
            self::setError('卡密不存在或无权限');
            return false;
        }

        $remainingUses = $card->card_num - $card->used_num;
        if ($remainingUses <= 0) {
            self::setError('卡密已使用完毕，无法删除');
            return false;
        }

        $isTokenCard = (int)$card->type === CardCodeEnum::TYPE_DISTRIBUTION_TOKENS;

        Db::startTrans();
        try {
            $refundTokens = $isTokenCard ? ($remainingUses * (float)$card->balance) : 0;
            $card->delete();

            if ($refundTokens > 0) {
                $user = User::find($userId);
                $user->tokens += $refundTokens;
                $user->save();

                AccountLogLogic::add(
                    $userId,
                    AccountLogEnum::TOKENS_INC_DISTRIBUTION_CARD_REFUND,
                    AccountLogEnum::INC,
                    $refundTokens,
                    1,
                    '',
                    '团队卡密删除退回算力'
                );
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError('删除失败');
            return false;
        }
    }
}
