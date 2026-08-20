<?php


namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvSetting;

use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceViral;
use app\common\model\sv\SvDeviceViralAccount;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\sv\SvDeviceTaskExistenceService;

use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 精品复刻逻辑
 * Class ViralRewriterLogic     
 * @package app\api\logic\aiPersona
 */
class ViralRewriterLogic extends BasePersonaLogic
{
    public static function autoViralRewriterTaskCron(SvDevice $device)
    {
        $result = self::emptyCreateResult();
        print_r("\n{$device->device_code}自动化爆款复刻任务生成\n");
        \think\facade\Log::channel('auto')->write($device->device_code . '自动化爆款复刻任务生成', 'create');
        try {

            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                //throw new \Exception( $device->device_code . 'IP人设不存在:' . \think\facade\Db::getLastSql());
                \think\facade\Log::channel('auto')->write($device->device_code . 'IP人设不存在:' . \think\facade\Db::getLastSql(), 'create');
                $result['skipped_not_configured']++;
                $result['errors'][] = 'IP人设不存在';
                return $result;
            }

            // if((int)$persona->persona_type === 3){
            //     \think\facade\Log::channel('auto')->write($device->device_code . '本地人设不需要自动化爆款复刻任务', 'create');
            //     return false;
            // }

            $xhsImageTextEligible = self::isXhsImageTextViralEligible($persona);

            $where = [];
            $where[] = ['persona_id', '=', $device->persona_id];
            //$where[] = ['copywriting_source', '=', AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE];

            $item = AiPersonaSynthesisConfig::where($where)->order('id desc')->findOrEmpty();

            if ($item->isEmpty()) {
                \think\facade\Log::channel('auto')->write($device->device_code . '自动化爆款复刻配置不存在' . ($item->isEmpty() ? \think\facade\Db::getLastSql() :  $item->id), 'create');
                //return false;
                $item = AiPersonaSynthesisConfig::create(
                    \app\api\logic\aiPersona\SynthesisConfigLogic::buildDefaultConfig(
                        (int) $persona['id'],
                        (int) $persona['user_id'],
                        (int) $persona['persona_type'],
                        (int) ($persona['publish_mode'] ?? 1)
                    )
                );
                \think\facade\Log::channel('auto')->write($device->device_code . '自动化爆款复刻配置创建成功' . json_encode($item->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'create');
                $item = AiPersonaSynthesisConfig::where($where)->order('id desc')->findOrEmpty();
            }

            // 抖音视频仿写仍要求自动合成 + 仿写模式；小红书图文可独立强制创建
            $imitateEligible = (int)$persona->publish_mode === 1
                && (int)$item->copywriting_source === AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE;

            if (!$imitateEligible && !$xhsImageTextEligible) {
                if ((int)$persona->publish_mode === 2) {
                    \think\facade\Log::channel('auto')->write($device->device_code . '成品库直发模式，跳过爆款复刻（含小红书图文仿写）', 'create');
                    $result['errors'][] = '成品库直发模式不创建爆款仿写任务';
                } elseif ((int)$persona->publish_mode !== 1) {
                    \think\facade\Log::channel('auto')->write($device->device_code . 'IP人设不是自动合成视频模式，且小红书未配置图文强制仿写', 'create');
                    $result['errors'][] = 'IP人设不是自动合成视频模式';
                } else {
                    \think\facade\Log::channel('auto')->write($device->device_code . '自动化爆款复刻配置不是仿写模式，且小红书未配置图文强制仿写', 'create');
                    $result['errors'][] = '自动化爆款复刻配置不是仿写模式';
                }
                $result['skipped_not_configured']++;
                return $result;
            }

            $rule = null;
            if ($persona->persona_type == 1) {
                $rule = $persona->individual;
            } elseif ($persona->persona_type == 2) {
                $rule = $persona->enterprise;
            } elseif ($persona->persona_type == 3) {
                $rule = $persona->local;
            }

            $item->device_code = $device->device_code;
            $item->persona_type = $persona->persona_type;
            $item->rule = $rule;
            $item->persona = $persona;
            $item->imitate_eligible = $imitateEligible;
            $item->xhs_image_text_eligible = $xhsImageTextEligible;
            //print_r($item->toArray());die;

            $result = self::createViralRewriterTask($item);
            \think\facade\Log::channel('auto')->write($device->device_code . '自动化爆款复刻任务生成结果：' . json_encode($result, JSON_UNESCAPED_UNICODE), 'create');
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'create');
            $result['failed']++;
            $result['errors'][] = $th->getMessage();
            return $result;
        }
    }

    private static function emptyCreateResult(): array
    {
        return [
            'created' => 0,
            'created_from_record_pool' => 0,
            'skipped_existing' => 0,
            'skipped_not_configured' => 0,
            'skipped_no_account' => 0,
            'failed' => 0,
            'errors' => [],
        ];
    }

    /**
     * 小红书内容发布为图文，且存在小红书发布时段时，可强制创建图文爆款仿写（不依赖 AI 合成文案来源）
     * 成品库直发（publish_mode=2）除外，不创建小红书图文仿写
     */
    private static function isXhsImageTextViralEligible(AiPersona $persona): bool
    {
        // 成品库直发不创建小红书图文仿写
        if ((int)$persona->publish_mode === 2) {
            return false;
        }

        $xhsConfig = AiPersona::getPlatformContentPublishConfig(
            $persona['content_publish_config'] ?? [],
            DeviceEnum::ACCOUNT_TYPE_XHS
        );
        return (int)$xhsConfig['publish_media_type'] === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT
            && self::getEnabledContentPublishScheduleCount($persona, DeviceEnum::ACCOUNT_TYPE_XHS) > 0;
    }

    private static function buildViralRewriterSourceTasks(AiPersonaSynthesisConfig $item, mixed $accounts, array &$result): array
    {
        $persona = $item->persona instanceof AiPersona
            ? $item->persona
            : AiPersona::where('id', (int)$item->persona_id)->findOrEmpty();
        if ($persona->isEmpty()) {
            $result['skipped_not_configured']++;
            $result['errors'][] = 'IP人设不存在';
            return [];
        }

        $imitateEligible = !empty($item->imitate_eligible);
        $xhsImageTextEligible = !empty($item->xhs_image_text_eligible);
        // 兼容旧调用：未挂资格标记时保持原行为（抖音 + 条件小红书图文）
        if (!isset($item->imitate_eligible) && !isset($item->xhs_image_text_eligible)) {
            $imitateEligible = true;
            $xhsImageTextEligible = self::isXhsImageTextViralEligible($persona);
        }

        $accountMap = [];
        foreach ($accounts as $account) {
            $accountMap[(int)$account->type] = $account;
        }

        $tasks = [];

        if ($imitateEligible) {
            if (self::getEnabledContentPublishScheduleCount($persona) <= 0) {
                $result['skipped_not_configured']++;
                $result['errors'][] = '内容发布时间段为空';
            } else {
                $videoPlatforms = [
                    DeviceEnum::ACCOUNT_TYPE_DY,
                    //DeviceEnum::ACCOUNT_TYPE_SPH,
                    //DeviceEnum::ACCOUNT_TYPE_KS,
                ];
                // 小红书内容发布为视频时，也可作为视频来源
                $xhsConfig = AiPersona::getPlatformContentPublishConfig(
                    $persona['content_publish_config'] ?? [],
                    DeviceEnum::ACCOUNT_TYPE_XHS
                );
                if ((int)($xhsConfig['publish_media_type'] ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO)
                    === AiPersona::PUBLISH_MEDIA_TYPE_VIDEO
                ) {
                    $videoPlatforms[] = DeviceEnum::ACCOUNT_TYPE_XHS;
                }

                $addedVideo = false;
                foreach ($videoPlatforms as $platformType) {
                    $account = $accountMap[$platformType] ?? null;
                    if (!$account) {
                        continue;
                    }
                    // 小红书图文资格已单独建任务时，避免同一账号重复建视频任务
                    if ($platformType === DeviceEnum::ACCOUNT_TYPE_XHS && $xhsImageTextEligible) {
                        continue;
                    }
                    $tasks[] = [
                        'account' => $account,
                        'publish_media_type' => AiPersona::PUBLISH_MEDIA_TYPE_VIDEO,
                    ];
                    $addedVideo = true;
                }
                if (!$addedVideo) {
                    $result['skipped_no_account']++;
                    $result['errors'][] = '设备未绑定可用于爆款视频的平台账号';
                }
            }
        }

        if ($xhsImageTextEligible) {
            $xhsAccount = $accountMap[DeviceEnum::ACCOUNT_TYPE_XHS] ?? null;
            if ($xhsAccount) {
                $tasks[] = [
                    'account' => $xhsAccount,
                    'publish_media_type' => AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT,
                ];
            } else {
                $result['skipped_no_account']++;
                $result['errors'][] = '设备未绑定小红书账号，无法作为爆款图文来源';
            }
        }

        return $tasks;
    }

    private static function getEnabledContentPublishScheduleCount(AiPersona $persona, int $platform = 0): int
    {
        $count = 0;
        foreach (self::getAutoSchedule($persona, DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH) as $schedule) {
            if ($platform <= 0 || self::scheduleContainsPlatform($schedule->platform, $platform)) {
                $count++;
            }
        }

        return $count;
    }

    private static function scheduleContainsPlatform(mixed $platforms, int $platform): bool
    {
        if ($platform <= 0) {
            return false;
        }
        if (is_string($platforms)) {
            $decoded = json_decode($platforms, true);
            $platforms = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($platforms)) {
            return false;
        }
        foreach ($platforms as $item) {
            if (is_object($item)) {
                $item = (array)$item;
            }
            if (is_array($item) && (int)($item['account_type'] ?? 0) === $platform) {
                return true;
            }
        }

        return false;
    }

    private static function createViralRewriterTask(AiPersonaSynthesisConfig $item): array
    {
        $result = self::emptyCreateResult();

        $accounts = SvAccount::field('id,account,type,nickname,avatar')
            ->whereIn('type', [
                //DeviceEnum::ACCOUNT_TYPE_SPH,
                DeviceEnum::ACCOUNT_TYPE_XHS,
                DeviceEnum::ACCOUNT_TYPE_DY,
                //DeviceEnum::ACCOUNT_TYPE_KS,
            ])
            ->where('user_id', $item->user_id)
            ->where('device_code', $item->device_code)
            ->select();
        if ($accounts->isEmpty()) {
            //throw new \Exception('该设备没有绑定账号' . $item->device_code);
            \think\facade\Log::channel('auto')->write($item->device_code . '该设备没有绑定账号', 'create');
            $result['skipped_no_account']++;
            $result['errors'][] = '该设备没有绑定账号';
            return $result;
        }
        Db::startTrans();
        try {
            $date = date('Y-m-d', time());
            $searchDates = self::getViralRewriterSearchDates($date);
            $sourceTasks = self::buildViralRewriterSourceTasks($item, $accounts, $result);
            if (empty($sourceTasks)) {
                \think\facade\Log::channel('auto')->write($item->device_code . ' viral source task is empty: ' . json_encode($result, JSON_UNESCAPED_UNICODE), 'create');
                Db::commit();
                return $result;
            }
            $sourceAccounts = array_map(static function ($sourceTask) {
                return $sourceTask['account'];
            }, $sourceTasks);
            $accountSlots = self::buildViralRewriterAccountSlots($sourceAccounts, $date);
            if (empty($accountSlots)) {
                \think\facade\Log::channel('auto')->write($item->device_code . '爆款复刻默认时间段配置异常', 'create');
                $result['skipped_not_configured']++;
                $result['errors'][] = '爆款复刻默认时间段配置异常';
                Db::commit();
                return $result;
            }

            $accountSlotMap = [];
            foreach ($accountSlots as $accountSlot) {
                $accountSlotMap[(int)$accountSlot['account']->type] = $accountSlot;
            }

            foreach ($sourceTasks as $sourceTask) {
                $account = $sourceTask['account'];
                $publishMediaType = (int)$sourceTask['publish_media_type'];
                $accountSlot = $accountSlotMap[(int)$account->type] ?? null;
                if (!$accountSlot) {
                    $result['skipped_not_configured']++;
                    $result['errors'][] = '爆款来源账号无可用任务时段: ' . $account->account;
                    continue;
                }

                // $setting = SvSetting::where('user_id', $item->user_id)->where('account', $account->account)->findOrEmpty();
                // if ($setting->isEmpty()) {
                //     continue;
                // }
                $exec_time = $accountSlot['exec_time'];
                $startTime = $accountSlot['start_time'];
                $endTime = $accountSlot['end_time'];
                    // if ($endTime < time()) {
                    //     \think\facade\Log::channel('auto')->write($item->device_code . '该账号类型[' . $account->type . ']时间[' . $exec_time . ']已过期', 'create');
                    //     continue;
                    // }

                    // 仅日历任务（SvDeviceTask）存在才跳过
                    if (self::calendarViralTaskExists($item, $account, $date, $startTime, $endTime)) {
                        $result['skipped_existing']++;
                        \think\facade\Log::channel('auto')->write(
                            $item->device_code . "爆款复刻日历任务已存在，跳过：账号={$account->account}, 时间={$exec_time}, 日期={$date}",
                            'create'
                        );
                        continue;
                    }

                    $keywords = $item->rule->hot_words ?? [];
                    if (empty($keywords)) {
                        $tokenScene = 'get_hot_words';
                        $tokenCode = \app\common\enum\user\AccountLogEnum::TOKENS_DEC_COZE_HOT_WORDS;
                        $unit = \app\api\logic\service\TokenLogService::checkToken($item->user_id, $tokenScene); // 添加辅助参数

                        $response = \app\common\service\ToolsService::Coze()->getHotWords([
                            'keywords' => $item->rule->getClueContent($item->persona),
                        ]);
                        if ((int)$response['code'] !== 10000) {
                            \think\facade\Log::channel('auto')->write($item->device_code . '获取热门关键词失败:' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'create');
                            $result['failed']++;
                            $result['errors'][] = '获取热门关键词失败';
                            continue;
                        }

                        $points = $unit;
                        $keywords = $response['data']['content'] ?? [];
                        if ($points > 0) {
                            $extra = ['生成关键词数' => count($keywords), '算力单价' => $unit, '实际消耗算力' => $points, '描述' => '根据输入内容提取短视频热点搜索关键词-24h'];
                            $taskId = generate_unique_task_id();
                            //token扣除
                            \app\common\model\user\User::userTokensChange($item->user_id, $points);
                            //记录日志
                            \app\common\logic\AccountLogLogic::recordUserTokensLog(true, $item->user_id, $tokenCode, $points, $taskId, $extra);
                        }
                        

                        switch ((int)$item->persona_type) {
                            case 1:
                                \app\common\model\aiPersona\AiPersonaIndividual::update([
                                    'hot_words' => $keywords,
                                    'update_time' => time(),
                                ], ['persona_id' => $item->persona_id]);
                                break;
                            case 2:
                                \app\common\model\aiPersona\AiPersonaEnterprise::update([
                                    'hot_words' => $keywords,
                                    'update_time' => time(),
                                ], ['persona_id' => $item->persona_id]);
                                break;
                            case 3:
                                \app\common\model\aiPersona\AiPersonaLocal::update([
                                    'hot_words' => $keywords,
                                    'update_time' => time(),
                                ], ['persona_id' => $item->persona_id]);
                                break;
                        }
                    }

                    $task_name = "自动化爆款复刻任务" . date('mdHis', time());
                    $trackingConfig = self::getViralTrackingConfig($item);

                    $task = SvDeviceViral::create([
                        'user_id' => $item->user_id,
                        'task_name' => $task_name,
                        'auto_type' => 1,
                        'accounts' => json_encode($accounts->toArray(), JSON_UNESCAPED_UNICODE),
                        'status' => 0,
                        'persona_id' => $item->persona_id,
                        'generation_types' => $item->generation_types ?? [],
                        'publish_platform' => (int)$account->type,
                        'publish_media_type' => $publishMediaType,
                        'keywords' => $keywords,
                        'custom_date' => json_encode($searchDates, JSON_UNESCAPED_UNICODE),
                        'time_config' => json_encode([$exec_time], JSON_UNESCAPED_UNICODE),
                        'create_time' => time(),
                    ]);


                    $row = SvDeviceViralAccount::create([
                        'viral_id' => $task->id,
                        'user_id' => $item->user_id,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'publish_platform' => (int)$account->type,
                        'publish_media_type' => $publishMediaType,
                        'duration' => $trackingConfig['duration'],//视频时长0不限 1 1分钟内 2 1到5分钟 3 5分钟以上
                        'publish_day' => $trackingConfig['publish_day'],//发布时间0不限 1一天内 2一周内 3半年内
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'device_code' => $item->device_code,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'keywords' => $keywords,
                        'status' => 0,
                        'persona_id' => $item->persona_id,
                        'create_time' => time(),
                    ]);
                    \app\common\model\sv\SvDeviceTask::create([
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::TASK_TYPE_VIRAL_REWRITER,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => $task_name,
                        'time_config' => json_encode([$exec_time], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => DeviceEnum::TASK_STATUS_WAIT,
                        'remark' => '',
                        'sub_task_id' => $task->id,
                        'sub_data_id' => $row->id,
                        'persona_id' => $item->persona_id,
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_VIRAL_REWRITER,
                        'source' => DeviceEnum::TASK_SOURCE_VIRAL_REWRITER,
                        'create_time' => time(),
                    ]);
                    $result['created']++;
                    \think\facade\Log::channel('auto')->write(
                        $item->device_code . "爆款复刻任务创建：账号={$account->account}, 时间={$exec_time}, 日期={$date}",
                        'create'
                    );
            }

            $item->update_time = time();
            $item->save();
            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化爆款复刻任务生成' . $item->device_code . ' ' . $th->__toString(), 'create');
            Db::rollback();
            $item->update_time = time();
            $item->save();
            $result['failed']++;
            $result['errors'][] = $th->getMessage();
            return $result;
        }
    }

    /**
     * 路径 A：日历任务槽位是否已存在（唯一用于 skipped_existing）。
     */
    private static function calendarViralTaskExists(
        AiPersonaSynthesisConfig $item,
        SvAccount $account,
        string $date,
        int $startTime,
        int $endTime
    ): bool {
        return SvDeviceTaskExistenceService::dailyAutoTaskExists(
            (int)$item->user_id,
            (string)$item->device_code,
            (int)$item->persona_id,
            DeviceEnum::AUTO_TASK_SCENE_VIRAL_REWRITER,
            (int)$account->type,
            $date,
            $startTime,
            $endTime
        );
    }

    private static function getViralRewriterSearchDates(string $date): array
    {
        return [
            $date,
            date('Y-m-d', strtotime($date . ' +1 day')),
        ];
    }

    private static function getViralTrackingConfig(AiPersonaSynthesisConfig $item): array
    {
        $persona = $item->persona instanceof AiPersona
            ? $item->persona
            : AiPersona::where('id', (int)$item->persona_id)->findOrEmpty();

        return [
            'tracking_mode' => AiPersona::normalizeTrackingMode($persona->tracking_mode ?? AiPersona::TRACKING_MODE_AUTO),
            'duration' => AiPersona::normalizeTrackingDuration($persona->duration ?? AiPersona::TRACKING_DURATION_DEFAULT),
            'publish_day' => AiPersona::normalizeTrackingFilterValue($persona->publish_day ?? 0),
            'tracking_account_config' => AiPersona::normalizeTrackingAccountConfig($persona->tracking_account_config ?? []),
        ];
    }

    private static function buildViralRewriterAccountSlots(mixed $accounts, string $date): array
    {
        $schedule = self::getDefaultViralRewriterSchedule();
        $startTime = strtotime($date . ' ' . $schedule['start_time'] . ':00');
        $endTime = strtotime($date . ' ' . $schedule['end_time'] . ':00');
        if (!$startTime || !$endTime || $endTime <= $startTime) {
            return [];
        }

        $accountList = [];
        foreach ($accounts as $account) {
            $accountList[] = $account;
        }
        if (empty($accountList)) {
            return [];
        }

        $orderMap = self::getViralRewriterPlatformOrder($schedule);
        usort($accountList, static function ($a, $b) use ($orderMap) {
            $aOrder = $orderMap[(int)$a->type] ?? (1000 + (int)$a->type);
            $bOrder = $orderMap[(int)$b->type] ?? (1000 + (int)$b->type);
            if ($aOrder === $bOrder) {
                return (int)$a->id <=> (int)$b->id;
            }
            return $aOrder <=> $bOrder;
        });

        $platformTypes = [];
        foreach ($accountList as $account) {
            $type = (int)$account->type;
            if (!in_array($type, $platformTypes, true)) {
                $platformTypes[] = $type;
            }
        }
        $platformCount = count($platformTypes);
        if ($platformCount <= 0) {
            return [];
        }

        $interval = ($endTime - $startTime) / $platformCount;
        $platformSlots = [];
        foreach ($platformTypes as $index => $type) {
            $slotStart = (int)round($startTime + $interval * $index);
            $slotEnd = $index === $platformCount - 1
                ? $endTime
                : (int)round($startTime + $interval * ($index + 1));
            $platformSlots[$type] = [
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'exec_time' => date('H:i', $slotStart) . '-' . date('H:i', $slotEnd),
            ];
        }

        $slots = [];
        foreach ($accountList as $account) {
            $slot = $platformSlots[(int)$account->type] ?? null;
            if (!$slot) {
                continue;
            }
            $slots[] = [
                'account' => $account,
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'exec_time' => $slot['exec_time'],
            ];
        }

        return $slots;
    }

    private static function getDefaultViralRewriterSchedule(): array
    {
        foreach (DeviceEnum::getDefaultScheduleScene() as $schedule) {
            if ((int)($schedule['scene'] ?? 0) === DeviceEnum::AUTO_TASK_SCENE_VIRAL_REWRITER) {
                return $schedule;
            }
        }

        return [
            'start_time' => '00:00',
            'end_time' => '03:00',
            'platform' => [],
        ];
    }

    private static function getViralRewriterPlatformOrder(array $schedule): array
    {
        $orderMap = [];
        $platforms = $schedule['platform'] ?? [];
        if (!is_array($platforms)) {
            return $orderMap;
        }
        foreach ($platforms as $index => $platform) {
            if (!is_array($platform) || empty($platform['account_type'])) {
                continue;
            }
            $orderMap[(int)$platform['account_type']] = (int)($platform['order'] ?? ($index + 1));
        }

        return $orderMap;
    }
}
