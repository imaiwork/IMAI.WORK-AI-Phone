<?php
namespace app\api\logic\distributionAgent;

use app\common\enum\CardCodeEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\cardcode\CardCode;
use app\common\model\cardcode\CardCodeRecord;
use app\common\model\cardcode\CardPackage;
use app\common\model\user\User;
use think\facade\Db;

/**
 * 分销代理制卡逻辑
 * Class DistributionAgentCardLogic
 * @package app\api\logic\distributionAgent
 */
class DistributionAgentCardLogic extends BaseLogic
{
    /**
     * @notes 生成卡密
     * @param int $userId
     * @param array $params
     * @throws \Exception
     * @return bool
     */
    public static function generate(int $userId, array $params)
    {
        $packageId = $params['package_id'];
        $count = (int) $params['count'];
        $cardNum = (int) $params['card_num'];

        // 验证当前用户是否是代理
        $agent = \app\common\model\distribution\DistributionAgent::where('user_id', $userId)->findOrEmpty();
        if ($agent->isEmpty()) {
            self::setError('非代理用户，无法生成卡密');
            return false;
        }

        // 验证当前用户代理资格是否正常
        if ($agent->status == 0 || $agent->level == 0) {
            self::setError('代理资格被冻结，无法生成卡密');
            return false;
        }

        // 验证套餐状态
        $package = CardPackage::findOrEmpty($packageId);
        if ($package->isEmpty() || $package->status == 0) {
            self::setError('套餐不存在或不可用');
            return false;
        }

        // 计算预计消耗 token
        $tokensPerCard = $package->tokens;
        $totalTokens = $tokensPerCard * $count * $cardNum;

        Db::startTrans();
        try {
            // 验证用户算力是否充足
            $user = User::find($userId);
            if ($user->tokens < $totalTokens) {
                throw new \Exception('算力余额不足');
            }

            // 扣减用户算力
            $user->tokens -= $totalTokens;
            $user->save();

            // 记录账户日志
            AccountLogLogic::add(
                $user->id,
                AccountLogEnum::TOKENS_DEC_DISTRIBUTION_CARD,
                AccountLogEnum::DEC,
                $totalTokens,
                1,
                '',
                '分销代理制卡: ' . $package->name . ' x' . $count . '张',
                ['套餐包含算力' => $package->tokens, '卡密数量' => $count, '单张卡密可使用次数' => $cardNum, '消耗算力' => $totalTokens]
            );

            // 生成卡密
            $cards = [];
            for ($i = 0; $i < $count; $i++) {
                $cards[] = [
                    'sn' => card_sn(CardCode::class, 'sn', 'K', 10, 1),
                    'type' => CardCodeEnum::TYPE_DISTRIBUTION_TOKENS,
                    'balance' => $tokensPerCard,
                    'card_num' => $cardNum,
                    'used_num' => 0,
                    'valid_start_time' => time(),
                    'valid_end_time' => 0,
                    'remark' => '代理制卡',
                    'user_id' => $userId,
                    'package_id' => $packageId,
                    'relation_id' => '',
                    'create_time' => time(),
                ];
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

    /**
     * @notes 删除未使用的卡密
     * @param int $userId
     * @param array $params
     * @return bool
     */
    public static function delete(int $userId, array $params)
    {
        $recordId = $params['id'];
        $card = clone CardCode::where('id', $recordId)
            ->where('user_id', $userId)
            ->findOrEmpty();

        if ($card->isEmpty()) {
            self::setError('卡密不存在或无权限');
            return false;
        }

        $remainingUses = $card->card_num - $card->used_num;
        if ($remainingUses <= 0) {
            self::setError('卡密次数已全部使用完毕，没有可用退款算力');
            return false;
        }

        Db::startTrans();
        try {
            $refundTokens = $remainingUses * $card->balance;

            // Soft delete
            $card->delete();

            // Refund
            $user = clone User::find($userId);
            $user->tokens += $refundTokens;
            $user->save();

            AccountLogLogic::add(
                $userId,
                AccountLogEnum::TOKENS_INC_DISTRIBUTION_CARD_REFUND,
                AccountLogEnum::INC,
                $refundTokens,
                1,
                '',
                '分销代理卡密删除退回算力'
            );

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError('删除失败');
            return false;
        }
    }
}
