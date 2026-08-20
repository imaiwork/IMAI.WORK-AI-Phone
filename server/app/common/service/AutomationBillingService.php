<?php

declare(strict_types=1);

namespace app\common\service;

use app\api\logic\service\TokenLogService;
use app\common\enum\AutomationEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\sv\SvDevice;
use app\common\model\user\User;
use think\facade\Log;

/**
 * 自动化任务统一请求+扣费
 * —— 避免各 Handler / Trait 复制 requestUrl 时缺场景、缺 switch、OCR scene 拼错。
 * —— 先校验实扣额并扣费,再调远端;远端失败原路退费,避免「算力不足仍执行成功」。
 */
class AutomationBillingService
{
    /**
     * 设备开启自动化时请求远端并扣费
     *
     * @return array 远端 data，失败或未开启自动化时返回 []
     * @throws \Exception 算力不足等(code 4059)
     */
    public static function requestAndCharge(
        array $request,
        string $scene,
        int $userId,
        string|int $taskId,
        string $deviceCode
    ): array {
        $autoType = (int)(SvDevice::where('device_code', $deviceCode)->value('auto_type') ?? 0);
        if ($autoType === 0) {
            return [];
        }

        Log::channel('socket')->write(
            '自动化扣费' . $scene . '----设备号--' . $deviceCode . '----任务id--' . $taskId
        );

        $requestService = ToolsService::Automation();

        [$tokenScene, $tokenCode] = match ($scene) {
            AutomationEnum::SOCIAL_MEDIA_RELEASED => ['automation_social_media_released', AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_RELEASED],
            AutomationEnum::SHUT_OFF_COMMENTS => ['automation_shut_off_comments', AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_COMMENTS],
            AutomationEnum::SHUT_OFF_OBTAIN => ['automation_shut_off_obtain', AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_OBTAIN],
            AutomationEnum::SHUT_OFF_PRIVATE_LETTER => ['automation_shut_off_private_letter', AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_PRIVATE_LETTER],
            AutomationEnum::FRIENDS_CIRCLE_COMMENTS => ['automation_friends_circle_comments', AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS],
            AutomationEnum::FRIENDS_CIRCLE_RELEASED => ['automation_friends_circle_released', AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_RELEASED],
            AutomationEnum::FRIENDS_CIRCLE_PRAISE => ['automation_friends_circle_praise', AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_PRAISE],
            AutomationEnum::WECHAT_ADD_FRIEND => ['automation_wechat_add_friend', AccountLogEnum::TOKENS_DEC_AUTOMATION_WECHAT_ADD_FRIEND],
            AutomationEnum::SOCIAL_MEDIA_OBTAIN => ['automation_social_media_obtain', AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_OBTAIN],
            AutomationEnum::SOCIAL_MEDIA_NURSING => ['automation_social_media_nursing', AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_NURSING],
            AutomationEnum::OCR_LOCAL => ['automation_ocr_local', AccountLogEnum::TOKENS_DEC_AUTOMATION_OCR_LOCAL],
            AutomationEnum::OCR_IMG => ['automation_ocr_img', AccountLogEnum::TOKENS_DEC_AUTOMATION_OCR_IMG],
            AutomationEnum::CITY_EXPOSURE => ['automation_city_exposure', AccountLogEnum::TOKENS_DEC_AUTOMATION_CITY_EXPOSURE],
            AutomationEnum::CITY_TOUCH => ['automation_city_touch', AccountLogEnum::TOKENS_DEC_AUTOMATION_CITY_TOUCH],
            AutomationEnum::GROUP_BUY => ['automation_group_buy', AccountLogEnum::TOKENS_DEC_AUTOMATION_GROUP_BUY],
            AutomationEnum::PRECISE_CLUES => ['automation_precise_clues', AccountLogEnum::TOKENS_DEC_AUTOMATION_PREISE_CLUES],
            default => throw new \InvalidArgumentException('未知自动化扣费场景: ' . $scene),
        };

        $unit = TokenLogService::checkToken($userId, $tokenScene);
        $points = (float)$unit;
        $extra = ['算力单价' => $unit, '实际消耗算力' => $unit];
        if ($scene === AutomationEnum::SOCIAL_MEDIA_NURSING) {
            $minutes = (float)($request['time_difference_minutes'] ?? 0);
            $points = $minutes * (float)$unit;
            $extra = ['执行时长（分钟）' => $minutes, '算力单价' => $unit, '实际消耗算力' => $points];
        }

        // 按「实扣额」再预检一次(养号等按分钟计费),不足则不调远端、不发设备任务
        if ($points > 0) {
            $spendable = TeamBillingService::spendableTokens($userId);
            if ($spendable < $points) {
                $msg = TeamBillingService::resolveSpender($userId) !== null
                    ? '当前团队算力不足，请联系团队主' : '用户算力不足';
                throw new \Exception($msg, 4059);
            }
        }

        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now'] = time();

        // 先扣费再调远端:杜绝远端成功后扣费失败仍「执行成功」
        $deducted = false;
        if ($points > 0) {
            User::userTokensChange($userId, $points);
            $deducted = true;
        }

        try {
            $response = match ($scene) {
                AutomationEnum::SOCIAL_MEDIA_RELEASED => $requestService->socialMediaReleased($request),
                AutomationEnum::SHUT_OFF_COMMENTS => $requestService->shutOffComments($request),
                AutomationEnum::SHUT_OFF_OBTAIN => $requestService->shutOffObtain($request),
                AutomationEnum::SHUT_OFF_PRIVATE_LETTER => $requestService->shutOffPrivateLetter($request),
                AutomationEnum::FRIENDS_CIRCLE_COMMENTS => $requestService->friendsCircleComments($request),
                AutomationEnum::FRIENDS_CIRCLE_RELEASED => $requestService->friendsCircleReleased($request),
                AutomationEnum::FRIENDS_CIRCLE_PRAISE => $requestService->friendsCirclePraise($request),
                AutomationEnum::WECHAT_ADD_FRIEND => $requestService->wechatAddFriend($request),
                AutomationEnum::SOCIAL_MEDIA_OBTAIN => $requestService->socialMediaObtain($request),
                AutomationEnum::SOCIAL_MEDIA_NURSING => $requestService->socialMediaNursing($request),
                AutomationEnum::OCR_LOCAL => $requestService->ocrLocal($request),
                AutomationEnum::OCR_IMG => $requestService->ocrImg($request),
                AutomationEnum::CITY_EXPOSURE => $requestService->cityExposure($request),
                AutomationEnum::CITY_TOUCH => $requestService->cityTouch($request),
                AutomationEnum::GROUP_BUY => $requestService->groupBuy($request),
                AutomationEnum::PRECISE_CLUES => $requestService->preciseClues($request),
                default => [],
            };

            if (!isset($response['code']) || (int)$response['code'] !== 10000) {
                if ($deducted) {
                    User::userTokensChange($userId, $points, 'inc');
                }
                return [];
            }

            if ($deducted) {
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, (string)$taskId, $extra);
            }

            return $response['data'] ?? [];
        } catch (\Throwable $e) {
            if ($deducted) {
                try {
                    User::userTokensChange($userId, $points, 'inc');
                } catch (\Throwable $refundEx) {
                    Log::channel('socket')->error(
                        '自动化扣费退回失败 scene=' . $scene
                        . ' user=' . $userId
                        . ' points=' . $points
                        . ' err=' . $refundEx->getMessage()
                    );
                }
            }
            throw $e;
        }
    }
}
