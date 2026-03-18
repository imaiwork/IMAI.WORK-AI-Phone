<?php
namespace app\api\logic\distributionAgent;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
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
        $user = User::field('tokens')->findOrEmpty($userId);

        $levelNames = ConfigService::get('distribution_agent', 'distribution_agent_level_names', [
            ['level' => 1, 'name' => '高级代理'],
            ['level' => 2, 'name' => '中级代理'],
            ['level' => 3, 'name' => '初级代理'],
        ]);
        if (is_string($levelNames)) {
            $levelNames = json_decode($levelNames, true);
        }

        $currentLevelName = '';
        foreach ($levelNames as $level) {
            if ($level['level'] == $agent->level) {
                $currentLevelName = $level['name'];
                break;
            }
        }

        return [
            'level' => $agent->level ?? 0,
            'status' => $agent->status ?? 1,
            'become_time' => $agent->become_time ?? 0,
            'tokens' => $user->tokens ?? 0,
            'qr_code' => $agent->qr_code ? \app\common\service\FileService::getFileUrl($agent->qr_code) : '',
            'level_name' => $currentLevelName
        ];
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
        $levelNames = ConfigService::get('distribution_agent', 'distribution_agent_level_names', [
            ['level' => 1, 'name' => '高级代理'],
            ['level' => 2, 'name' => '中级代理'],
            ['level' => 3, 'name' => '初级代理'],
        ]);
        if (is_string($levelNames)) {
            $levelNames = json_decode($levelNames, true);
        }

        return $levelNames;
    }
}
