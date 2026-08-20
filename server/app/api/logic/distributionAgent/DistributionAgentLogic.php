<?php
namespace app\api\logic\distributionAgent;

use app\adminapi\logic\setting\DistributionAgentConfigLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\logic\RechargeStatsLogic;
use app\common\model\distribution\DistributionAgent;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\wechat\WeChatMnpService;
use think\facade\Db;

class DistributionAgentLogic extends BaseLogic
{
    public static function info(int $userId)
    {
        $agent = DistributionAgent::where('user_id', $userId)->findOrEmpty();
        $user = User::field('tokens, recharge_stats_reset_time')->findOrEmpty($userId);

        // 团队充值业绩：所有层级下线的已支付充值订单合计，按自己的业绩清零水位线过滤，
        // 与后台用户详情的「下级累计充值金额」同口径
        $descendantIds = DistributionAgent::getDescendantIds($userId);
        $teamRecharge = RechargeStatsLogic::getTotal(
            $descendantIds,
            (int)($user->recharge_stats_reset_time ?? 0)
        );

        return [
            'level' => $agent->level ?? 0,
            'status' => $agent->status ?? 1,
            'become_time' => $agent->become_time ?? 0,
            'tokens' => $user->tokens ?? 0,
            'qr_code' => $agent->qr_code ? \app\common\service\FileService::getFileUrl($agent->qr_code) : '',
            'level_name' => DistributionAgentConfigLogic::getLevelName((int)($agent->level ?? 0)),
            'team_user_count' => count($descendantIds),
            'team_recharge_amount' => $teamRecharge['amount'],
            'team_recharge_count' => $teamRecharge['order_count'],
        ];
    }

    /**
     * @notes 某个下级的概要：基本信息 + 充值业绩，用于充值流水明细页头部
     * @param int $userId 下级用户
     * @param int $viewerUserId 查看者（上级），业绩按其清零水位线统计
     * @return array
     */
    public static function subSummary(int $userId, int $viewerUserId): array
    {
        $user = User::field('id, nickname, avatar, mobile, tokens')->findOrEmpty($userId);
        $agent = DistributionAgent::where('user_id', $userId)->findOrEmpty();
        $selfRecharge = RechargeStatsLogic::getTotal(
            [$userId],
            RechargeStatsLogic::getSubStatsSinceTime($viewerUserId, $userId)
        );

        return [
            'user_id' => $userId,
            'nickname' => $user['nickname'] ?? '',
            'avatar' => FileService::getFileUrl($user['avatar'] ?? ''),
            'mobile' => $user['mobile'] ?? '',
            'tokens' => $user['tokens'] ?? 0,
            'level' => (int)($agent->level ?? 0),
            'level_name' => DistributionAgentConfigLogic::getLevelName((int)($agent->level ?? 0)),
            'self_recharge_amount' => $selfRecharge['amount'],
            'self_recharge_count' => $selfRecharge['order_count'],
        ];
    }

    /**
     * @notes 校验当前用户能否查看目标用户的数据，返回实际要查询的用户 ID
     *   代理端最多看到「下级的下级」两层，所以沿 parent_id 上溯 $maxDepth 层能追到自己才放行。
     *   展开下级列表只允许传直属下级（$maxDepth = 1），保证最深只能展开到孙级。
     * @param int $currentUserId
     * @param int $targetUserId 0 或等于自己表示看自己
     * @param int $maxDepth 允许的最大层级差
     * @return int
     * @throws \Exception
     */
    public static function checkViewableUserId(int $currentUserId, int $targetUserId, int $maxDepth = 2): int
    {
        if ($targetUserId <= 0 || $targetUserId === $currentUserId) {
            return $currentUserId;
        }

        $cursor = $targetUserId;
        for ($depth = 0; $depth < $maxDepth; $depth++) {
            $parentId = (int)DistributionAgent::where('user_id', $cursor)->value('parent_id');
            if ($parentId <= 0) {
                break;
            }
            if ($parentId === $currentUserId) {
                return $targetUserId;
            }
            $cursor = $parentId;
        }

        throw new \Exception('该用户不在您的下级中，无法查看');
    }

    public static function setLevel(int $parentId, array $params)
    {
        $agent = DistributionAgent::where('user_id', $params['user_id'])->findOrEmpty();
        if ($agent->isEmpty()) {
            self::setError("用户不存在");
            return false;
        }

        $parent = DistributionAgent::where('user_id', $parentId)->findOrEmpty();
        if ($parent->isEmpty()) {
            self::setError("上级不存在");
            return false;
        }

        if ($parent->level >= $params['level'] && $params['level'] != 0) {
            self::setError("权限不足");
            return false;
        }

        $agent->level = $params['level'];
        if ($params['level'] > 0) {
            $agent->status = 1;
            if ($agent->become_time == 0) {
                $agent->become_time = time();
            }
        }
        $agent->update_time = time();
        return $agent->save();
    }

    public static function removeSub(int $parentId, array $params)
    {
        $agent = DistributionAgent::where('user_id', $params['user_id'])->where('parent_id', $parentId)->findOrEmpty();
        if ($agent->isEmpty()) {
            self::setError("下级用户不存在");
            return false;
        }
        $agent->level = 0;
        $agent->status = 0;

        return $agent->save();
    }

    public static function giftTokens(array $params)
    {
        $currentUserId = request()->userId;
        $targetUserId = $params['user_id'];
        $amount = (int) $params['tokens'];

        Db::startTrans();
        try {
            $currentUser = User::find($currentUserId);
            $targetUser = User::find($targetUserId);

            // Deduct from agent
            $currentUser->tokens = bcsub($currentUser->tokens, $amount, 2);
            $currentUser->save();
            AccountLogLogic::add(
                $currentUser->id,
                AccountLogEnum::TOKENS_DEC_DISTRIBUTION_TRANSFER,
                AccountLogEnum::DEC,
                $amount,
                1,
                '',
                '转赠给下级用户: ' . $targetUser->sn
            );

            // Add to downline
            $targetUser->tokens = bcadd($targetUser->tokens, $amount, 2);
            $targetUser->save();
            AccountLogLogic::add(
                $targetUser->id,
                AccountLogEnum::TOKENS_INC_DISTRIBUTION_TRANSFER,
                AccountLogEnum::INC,
                $amount,
                1,
                '',
                '上级代理转赠'
            );

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function setQrCode(int $userId, string $qrCode)
    {
        $agent = DistributionAgent::where('user_id', $userId)->findOrEmpty();
        if ($agent->isEmpty()) {
            self::setError("用户不存在");
            return false;
        }
        $agent->qr_code = FileService::setFileUrl($qrCode);
        $agent->update_time = time();
        return $agent->save();
    }

    public static function getSuperiorQrCode(int $userId)
    {
        $agent = DistributionAgent::where('user_id', $userId)->findOrEmpty();

        if (!$agent->isEmpty()) {
            if ($agent->parent_id == 0) {
                $qrCode = ConfigService::get('website', 'customer_service');
                if (is_string($qrCode)) {
                    $qrCode = json_decode($qrCode, true);
                }
                return ['qr_code' => FileService::getFileUrl($qrCode['wx_image'])];
            } else {
                $parentAgent = DistributionAgent::where('user_id', $agent->parent_id)->findOrEmpty();
                if (!$parentAgent->isEmpty() && !empty($parentAgent->qr_code)) {
                    return ['qr_code' => FileService::getFileUrl($parentAgent->qr_code)];
                }
            }
        }
        return ['qr_code' => ''];
    }

    public static function getBindMnpCode(array $params, int $userId)
    {
        try {
            $user = User::where('id', $userId)->findOrEmpty();
            $wechatMnpService = new WeChatMnpService();

            $path = public_path() . 'uploads/images/mnpqrcode/' . date('Ymd') . '/' . md5(time() . $user->sn) . '.png';

            if (!is_dir(dirname($path))) {
                umask(0);
                mkdir(dirname($path), 0777, true);
            }

            $params['sn'] = $user->sn;

            if (!file_exists($path)) {
                $wechatMnpService->getMnpCodeUrl($params['path'], 430, $path, $params);
            }
            self::$returnData = ['url' => FileService::getFileUrl(str_replace(public_path(), '', $path)), 'sn' => $user->sn];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getBindMnpUrl(array $params, int $userId)
    {
        try {
            $user = User::where('id', $userId)->findOrEmpty();
            $wechatMnpService = new WeChatMnpService();
            $params['sn'] = $user->sn;
            $result = $wechatMnpService->urlLink($params['path'], "sn={$params['sn']}");
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                self::$returnData = ['url' => $result['url_link'], 'sn' => $user->sn];
                return true;
            }
            throw new \Exception('获取小程序链接失败');
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getAgentConfig()
    {
        return DistributionAgentConfigLogic::getConfig();
    }
}
