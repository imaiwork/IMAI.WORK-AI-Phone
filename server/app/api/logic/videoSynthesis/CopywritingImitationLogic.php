<?php

namespace app\api\logic\videoSynthesis;

use app\api\logic\aiPersona\BasePersonaLogic;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\service\TokenLogService;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\exception\MaterialNotReadyException;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\aiPersona\Material as MaterialModel;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\aiPersona\SynthesisConfig as AiPersonaSynthesisConfig;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceViralRecord;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use app\common\service\aiPersona\SynthesisTemplateConfigService;
use app\common\service\UploadService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 视频合成逻辑
 * 支持：1-仿写，2-纯AI生成，3-无文案
 */
class CopywritingImitationLogic extends BasePersonaLogic
{
    const EXTRACT_KEYWORDS = 'extractKeywords'; //提取关键词
    const GRAB_IMAGE = 'grabImage'; //抓取图片
    const GRAB_VIDEO = 'grabVideo'; //抓取视频
    const SHANJIAN_AI_COVER = 'shanjianAiCover'; //使用Shanjian的AI封面图
    /**
     * 视频合成主入口
     */
    public static function copywritingImitation($copywritingId)
    {
        $deviceCode = '未知设备';
        try {

            $copywritingRecord = AiPersonaSynthesisCopywriting::where('id', $copywritingId)
                ->where('use_state', 0)
                ->where('publish_media_type', 1)
                ->findOrEmpty();

            if ($copywritingRecord->isEmpty()) {
                throw new \Exception('文案记录不存在或已被处理');
            }
            $deviceCode = $copywritingRecord->device_code;
            $config = AiPersonaSynthesisConfig::where('persona_id', $copywritingRecord->persona_id)
                ->where('user_id', $copywritingRecord->user_id)
                ->findOrEmpty();

            if ($config->isEmpty()) {
                $msg = '人设' . AiPersona::formatLabel(null, (int)$copywritingRecord->persona_id) . '-未找到匹配的合成配置(需满足默认封面且纯素材库)';
                throw new \Exception($msg);
            }
            $persona = AiPersona::where('id', $config->persona_id)
                ->where('publish_mode', 1)
                ->where('status', 1)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                $msg = '-绑定的人设' . AiPersona::formatLabel(null, (int)$config->persona_id) . '不存在或发布模式不符合要求';
                throw new \Exception($msg);
            }

            Log::channel('explosionVideoSynthesis')->write('文案合成分流判断：' . json_encode([
                'copywriting_id' => (int)$copywritingRecord->id,
                'copywriting_status' => (int)$copywritingRecord->status,
                'copywriting_source' => (int)$config->copywriting_source,
                'copywriting_empty' => empty($copywritingRecord->copywriting) ? 1 : 0,
                'use_state' => (int)$copywritingRecord->use_state,
                'device_code' => (string)$copywritingRecord->device_code,
                'user_id' => (int)$copywritingRecord->user_id,
                'persona_id' => (int)$copywritingRecord->persona_id,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            // 逻辑分流
            if ($copywritingRecord->status == AiPersonaSynthesisCopywriting::STATUS_SUCCESS
                && (int)$config->copywriting_source === AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE) {
                $nextTaskType = self::getNextTaskType($copywritingRecord->device_code, $copywritingRecord->persona_id, $config);
                if ((int)$nextTaskType === 4) {
                    Log::channel('explosionVideoSynthesis')->write('24h新闻体视频强制切换AI自动生成：' . json_encode([
                        'copywriting_id' => (int)$copywritingRecord->id,
                        'device_code' => (string)$copywritingRecord->device_code,
                        'persona_id' => (int)$copywritingRecord->persona_id,
                        'task_type' => (int)$nextTaskType,
                        'origin_copywriting_source' => (int)$config->copywriting_source,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    return self::processPureAiSynthesis($copywritingRecord, $config, (int)$nextTaskType, true);
                }
                return self::processImitationSynthesis($copywritingRecord, $config, (int)$nextTaskType);
            } elseif ($copywritingRecord->status == AiPersonaSynthesisCopywriting::STATUS_FAILED
                || (int)$config->copywriting_source === AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI) {
                // 仿写失败降级：进入纯 AI 前写入 status=7 错误标记（与纯 AI 成败无关）
                if ((int)$copywritingRecord->status === AiPersonaSynthesisCopywriting::STATUS_FAILED
                    && (int)$config->copywriting_source === AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE
                ) {
                    self::createImitationFallbackErrorRecord($copywritingRecord);
                }
                return self::processPureAiSynthesis($copywritingRecord, $config);
            } else {
                $msg = '未知的文案来源配置或状态不匹配，文案来源：' . $config->copywriting_source . '|文案状态：' . $copywritingRecord->status . '|文案ID：' . $copywritingRecord->id;
                throw new \Exception($msg);
            }
        } catch (MaterialNotReadyException $e) {
            // 素材转码未就绪:必须穿透到外层 cron,让 cron 清缓存等下一轮;若在此层吞掉,cron 永远收不到信号
            throw $e;
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $msg = '设备号' . $deviceCode . '视频合成任务失败：' . $errorMsg;
            Log::channel('explosionVideoSynthesis')->write('视频合成任务失败：' . $errorMsg);
            self::setError($errorMsg);
            return false;
        }
    }

    /**
     * 处理：爆款仿写模式 (Source = 1)
     */
    private static function processImitationSynthesis($copywritingRecord, $config, ?int $forceTaskType = null)
    {
        $title = '未命名仿写任务';
        $rewrittenText = '';
        try {
        $cacheKey = 'video_synthesis_lock_' . $copywritingRecord->id;
        if (!Cache::store('redis')->set($cacheKey, 1, 600)) {
            throw new \Exception('任务正在处理中');
        }
        $rawData = $copywritingRecord->copywriting;
        $copywritingData = $rawData;
        while (is_string($copywritingData)) {
            $decoded = json_decode($copywritingData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
            $copywritingData = $decoded;
        }
        $rewrittenText = $copywritingData['rewritten_text'] ?? '';
        $title = $copywritingData['title'] ?? '未命名仿写任务';

        if (empty($rewrittenText)) throw new \Exception('仿写文案内容为空');

        $result = self::executeCoreSynthesis($copywritingRecord, $config, $rewrittenText, $title, 1, $forceTaskType);
        if ($result) {
            self::markCopywritingAsUsed((int)$copywritingRecord->id, '仿写成功');
        }
        return $result;
        } catch (MaterialNotReadyException $e) {
            // 素材转码未就绪:不落库失败任务、不标记已使用,让外层 cron 清缓存等下一轮重试
            throw $e;
        } catch (\Throwable $e) {
            self::recordFailedSynthesisTaskForCopywriting($copywritingRecord, $config, $e->getMessage(), 1, $title, $rewrittenText, $forceTaskType);
            throw $e;
        }
    }

    /**
     * 处理：纯AI生成模式 (Source = 2 / 新闻体强制AI)
     */
    private static function processPureAiSynthesis($copywritingRecord, $config, ?int $forceTaskType = null, bool $keepCopywritingUseState = false)
    {
        $title = 'AI自动生成视频';
        $rewrittenText = '';
        $taskType = null;
        try {
        $userId = $copywritingRecord->user_id;
        $personaId = $copywritingRecord->persona_id;
        $taskType = $forceTaskType ?: self::getNextTaskType($copywritingRecord->device_code, $personaId, $config);
        $device = SvDevice::where('device_code', $copywritingRecord->device_code)->findOrEmpty();
        if ($device->isEmpty()) {
            throw new \Exception('设备不存在');
        }
        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            throw new \Exception('人设不存在');
        }



        $cozeParams = self::buildPersonaCopywritingParams($device, $persona);
        switch ((int)$taskType) {
            case 1:
                $cozeParams['sn'] = 0;
                $cozeParams['number'] = 1;
                $cozeParams['length'] = 100;
                break;
            case 2:
                $cozeParams['sn'] = 0;
                $cozeParams['number'] = 5;
                $cozeParams['length'] = 80;
                break;
            case 3:
                $cozeParams['sn'] = 5;
                $cozeParams['number'] = 1;
                $cozeParams['length'] = 80;
                break;
            case 4:
                $cozeParams['sn'] = 2;
                $cozeParams['number'] = 1;
                $cozeParams['length'] = 80;
                break;
            default:
                throw new \Exception('视频类型不存在');
        }

        $copywritingLogContext = [
            'copywriting_id' => (int)$copywritingRecord->id,
            'copywriting_status' => (int)$copywritingRecord->status,
            'copywriting_source' => (int)$config->copywriting_source,
            'device_code' => (string)$copywritingRecord->device_code,
            'user_id' => (int)$userId,
            'persona_id' => (int)$personaId,
            'task_type' => (int)$taskType,
            'sn' => (int)$cozeParams['sn'],
            'number' => (int)$cozeParams['number'],
            'length' => (int)$cozeParams['length'],
            'keywords_preview' => mb_substr((string)($cozeParams['keywords'] ?? ''), 0, 500),
        ];
        Log::channel('explosionVideoSynthesis')->write('纯AI文案生成请求：' . json_encode($copywritingLogContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $aiRes = AutoDeviceSettingLogic::copywriting($cozeParams, $userId, 6);
        $rewrittenText = $aiRes['content'][0] ?? '';
        Log::channel('explosionVideoSynthesis')->write('纯AI文案生成结果：' . json_encode(array_merge($copywritingLogContext, [
            'content_preview' => mb_substr((string)$rewrittenText, 0, 500),
            'content_empty' => empty($rewrittenText) ? 1 : 0,
            'response_keys' => is_array($aiRes) ? array_keys($aiRes) : [],
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (empty($rewrittenText)) throw new \Exception('AI文案生成失败');

        $titleParams = ['sn' => 8, 'number' => 1, 'length' => 15, 'keywords' => $rewrittenText];
        Log::channel('explosionVideoSynthesis')->write('纯AI标题生成请求：' . json_encode(array_merge($copywritingLogContext, [
            'title_sn' => 8,
            'title_keywords_preview' => mb_substr((string)$rewrittenText, 0, 500),
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $titleRes = AutoDeviceSettingLogic::copywriting($titleParams, $userId, 6);
        $title = $titleRes['content'][0] ?? 'AI自动生成视频';
        Log::channel('explosionVideoSynthesis')->write('纯AI标题生成结果：' . json_encode(array_merge($copywritingLogContext, [
            'title_preview' => mb_substr((string)$title, 0, 200),
            'response_keys' => is_array($titleRes) ? array_keys($titleRes) : [],
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $result = self::executeCoreSynthesis($copywritingRecord, $config, $rewrittenText, $title, 2, $taskType);
        if ($result && !$keepCopywritingUseState) {
            self::markCopywritingAsUsed((int)$copywritingRecord->id, '纯AI成功');
        } elseif ($result) {
            Log::channel('explosionVideoSynthesis')->write('新闻体AI合成保留原文案use_state：' . json_encode([
                'copywriting_id' => (int)$copywritingRecord->id,
                'task_type' => (int)$taskType,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        return $result;
        } catch (MaterialNotReadyException $e) {
            // 素材转码未就绪:不落库失败任务、不标记已使用,让外层 cron 清缓存等下一轮重试
            throw $e;
        } catch (\Throwable $e) {
            self::recordFailedSynthesisTaskForCopywriting($copywritingRecord, $config, $e->getMessage(), 2, $title, $rewrittenText, $taskType, $keepCopywritingUseState);
            throw $e;
        }
    }

    /**
     * 构建完整人设文案生成参数，供自动合成主链路与降级链路共用。
     */
    protected static function buildPersonaCopywritingParams($device, $persona): array
    {
        $deviceCode = $device->device_code;
        $userId = $device->user_id;

        $personaLabel = AiPersona::formatLabel($persona);
        switch ((int)$persona->persona_type) {
            case 1:
                $personaInfo = AiPersonaIndividual::where('user_id', $userId)->where('persona_id', $persona->id)->findOrEmpty();
                if ($personaInfo->isEmpty()) {
                    throw new \Exception('设备号' . $deviceCode . '绑定的人设' . $personaLabel . '下个人IP人设不存在');
                }
                break;
            case 2:
                $personaInfo = AiPersonaEnterprise::where('user_id', $userId)->where('persona_id', $persona->id)->findOrEmpty();
                if ($personaInfo->isEmpty()) {
                    throw new \Exception('设备号' . $deviceCode . '绑定的人设' . $personaLabel . '下企业服务人设不存在');
                }
                break;
            case 3:
                $personaInfo = AiPersonaLocal::where('user_id', $userId)->where('persona_id', $persona->id)->findOrEmpty();
                if ($personaInfo->isEmpty()) {
                    throw new \Exception('设备号' . $deviceCode . '绑定的人设' . $personaLabel . '下本地门店人设不存在');
                }
                break;
            default:
                throw new \Exception('设备号' . $deviceCode . '绑定的人设' . $personaLabel . '类型不存在');
        }

        return [
            'keywords' => self::buildPersonaCopywritingKeywordsText($persona, $personaInfo, (int)$persona->persona_type),
        ];
    }

    protected static function buildPersonaCopywritingKeywordsText($persona, $personaInfo, int $personaType): string
    {
        $personaName = $persona->persona_name ?? '';
        $personaDesc = $persona->persona_desc ?? '';
        $mainBusiness = $persona->main_business ?? '';
        $targetPainPoints = $persona->target_pain_points ?? '';
        $conversionHook = $persona->conversion_hook ?? '';
        $locationCity = $persona->store_position ?? '';

        switch ($personaType) {
            case 1:
                $ipName = $personaName ?: ($personaInfo->nickname ?? '');
                $accountType = '个人IP';
                $whatYouDo = $personaInfo->identity ?? '';
                $mainShare = $personaInfo->core_value ?? '';
                $targetViewers = $personaInfo->target_audience ?? '';
                $tone = $personaInfo->personality_tags ?? '';
                $desiredAction = $personaInfo->monetize_paths ?? '';
                $whatYouSell = $mainBusiness;
                $targetBuyers = $targetPainPoints ?: ($personaInfo->target_audience ?? '');
                $advantage = $conversionHook ?: ($personaInfo->highlight_story ?? '');
                $productContent = $mainBusiness;
                break;
            case 2:
                $ipName = $personaName ?: ($personaInfo->brand_name ?? '');
                $accountType = '企业服务';
                $whatYouDo = $personaInfo->main_product ?? '';
                $mainShare = $personaInfo->main_product ?? '';
                $targetViewers = $personaInfo->target_customer ?? '';
                $tone = $personaInfo->brand_tone ?? '';
                $desiredAction = $personaInfo->account_goal ?? '';
                $whatYouSell = ($personaInfo->main_product ?? '') ?: $mainBusiness;
                $targetBuyers = $targetPainPoints ?: ($personaInfo->target_customer ?? '');
                $advantage = $conversionHook ?: ($personaInfo->industry_case ?? '');
                $productContent = $mainBusiness ?: ($personaInfo->main_product ?? '');
                break;
            case 3:
                $ipName = $personaName ?: ($personaInfo->store_name ?? '');
                $accountType = '本地商家';
                $whatYouDo = $personaInfo->store_name ?? '';
                $mainShare = $personaInfo->content_preference ?? '';
                $targetViewers = $personaInfo->target_customer ?? '';
                $tone = $personaInfo->store_atmosphere ?? '';
                $locationCity = $locationCity ?: ($personaInfo->store_name ?? '');
                $desiredAction = $conversionHook ?: ($personaInfo->content_preference ?? '');
                $whatYouSell = ($personaInfo->signature_feature ?? '') ?: $mainBusiness;
                $targetBuyers = $targetPainPoints ?: ($personaInfo->target_customer ?? '');
                $advantage = ($personaInfo->open_story ?? '') ?: $conversionHook;
                $productContent = $mainBusiness ?: ($personaInfo->signature_feature ?? '');
                break;
            default:
                $ipName = $personaName;
                $accountType = '';
                $whatYouDo = '';
                $mainShare = '';
                $targetViewers = '';
                $tone = '';
                $desiredAction = '';
                $whatYouSell = '';
                $targetBuyers = '';
                $advantage = '';
                $productContent = '';
        }

        return sprintf(
            "我的IP名称是%s。\n\nIP介绍如下：\n%s\n\n账号类型是%s。\n\n我的职业/业务是：\n%s\n\n我主要分享的内容是：\n%s\n\n我想给谁看的是：\n%s\n\n这个账号整体想呈现的感觉是：\n%s\n\n我所在的城市/地点是：\n%s\n\n我希望用户看完内容之后的行为是：\n%s\n\n我正在销售的产品/服务是：\n%s\n\n我想卖给的人群是：\n%s\n\n相比同行，我的优势是：\n%s\n\n以下是我的产品内容：\n%s",
            self::stringifyPersonaCopywritingField($ipName),
            self::stringifyPersonaCopywritingField($personaDesc),
            self::stringifyPersonaCopywritingField($accountType),
            self::stringifyPersonaCopywritingField($whatYouDo),
            self::stringifyPersonaCopywritingField($mainShare),
            self::stringifyPersonaCopywritingField($targetViewers),
            self::stringifyPersonaCopywritingField($tone),
            self::stringifyPersonaCopywritingField($locationCity),
            self::stringifyPersonaCopywritingField($desiredAction),
            self::stringifyPersonaCopywritingField($whatYouSell),
            self::stringifyPersonaCopywritingField($targetBuyers),
            self::stringifyPersonaCopywritingField($advantage),
            self::stringifyPersonaCopywritingField($productContent)
        );
    }

    protected static function stringifyPersonaCopywritingField($value): string
    {
        if (is_array($value)) {
            $items = array_map([self::class, 'stringifyPersonaCopywritingField'], $value);
            $items = array_filter($items, static fn($item) => $item !== '');
            return implode('、', $items);
        }

        if ($value === null) {
            return '';
        }

        return trim((string)$value);
    }

    /**
     * 文案维度合成失败时落库失败任务
     */
    private static function recordFailedSynthesisTaskForCopywriting(
        $copywritingRecord,
        $config,
        string $remark,
        int $copywritingSource,
        string $title = '',
        string $msg = '',
        ?int $taskType = null,
        bool $keepCopywritingUseState = false
    ): void {
        try {
            $device = SvDevice::where('device_code', $copywritingRecord->device_code)->findOrEmpty();
            $persona = AiPersona::where('id', $copywritingRecord->persona_id)->findOrEmpty();
            if ($device->isEmpty() || $persona->isEmpty()) {
                return;
            }
            if ($taskType === null) {
                $taskType = self::getNextTaskType($copywritingRecord->device_code, $copywritingRecord->persona_id, $config);
            }
            self::createFailedSynthesisTask($device, $persona, $taskType, $remark, [
                'config' => $config,
                'copywriting_source' => $copywritingSource,
                'title' => $title,
                'msg' => $msg,
                'is_downgrade' => (int)($copywritingRecord->status == 1),
                'log_channel' => 'explosionVideoSynthesis',
            ]);
            if (!$keepCopywritingUseState) {
                self::markCopywritingAsUsed((int)$copywritingRecord->id, '失败任务落库后');
            } else {
                Log::channel('explosionVideoSynthesis')->write('失败任务落库后保留原文案use_state：' . json_encode([
                    'copywriting_id' => (int)$copywritingRecord->id,
                    'task_type' => (int)$taskType,
                    'reason' => '新闻体强制AI',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        } catch (\Throwable $ignored) {
        }
    }

    /**
     * 统一更新文案任务使用状态，并同步对应爆款记录使用时间
     */
    private static function markCopywritingAsUsed(int $copywritingId, string $reason = ''): void
    {
        if ($copywritingId <= 0) {
            return;
        }

        $row = Db::name('ai_persona_synthesis_copywriting')
            ->where('id', $copywritingId)
            ->field('id,sv_device_viral_record_id,use_state')
            ->find();
        if (empty($row)) {
            return;
        }

        $now = time();
        $affected = 0;
        if ((int)($row['use_state'] ?? 0) === AiPersonaSynthesisCopywriting::USE_STATE_UNUSED) {
            $affected = Db::name('ai_persona_synthesis_copywriting')
                ->where('id', $copywritingId)
                ->where('use_state', AiPersonaSynthesisCopywriting::USE_STATE_UNUSED)
                ->update([
                    'use_state' => AiPersonaSynthesisCopywriting::USE_STATE_USED,
                    'update_time' => $now,
                ]);
        }

        $recordId = (int)($row['sv_device_viral_record_id'] ?? 0);
        $recordAffected = 0;
        if ($recordId > 0) {
            $recordAffected = Db::name('sv_device_viral_record')
                ->where('id', $recordId)
                ->where('use_time', 0)
                ->update([
                    'use_time' => $now,
                    'update_time' => $now,
                ]);
        }

        Log::channel('explosionVideoSynthesis')->write('copywriting_use_state更新: ' . json_encode([
            'copywriting_id' => $copywritingId,
            'viral_record_id' => $recordId,
            'reason' => $reason,
            'copywriting_affected' => $affected,
            'viral_record_affected' => $recordAffected,
            'use_time' => $now,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * 仿写失败降级进入纯 AI 前，写入 SvDeviceViralRecord status=7 错误标记。
     * 失败只记日志，不阻断后续合成。
     */
    private static function createImitationFallbackErrorRecord($copywritingRecord): void
    {
        try {
            $copywritingId = (int)($copywritingRecord->id ?? 0);
            if ($copywritingId <= 0) {
                return;
            }

            $contentPrefix = 'copywriting_imitation_degrade_error://' . $copywritingId . '/';
            $exists = SvDeviceViralRecord::where('status', SvDeviceViralRecord::STATUS_FALLBACK_ERROR)
                ->where('content', 'like', $contentPrefix . '%')
                ->findOrEmpty();
            if (!$exists->isEmpty()) {
                Log::channel('explosionVideoSynthesis')->write('仿写失败降级status=7已存在，跳过：' . json_encode([
                    'copywriting_id' => $copywritingId,
                    'existing_record_id' => (int)$exists->id,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return;
            }

            $linkedRecord = null;
            $linkedId = (int)($copywritingRecord->sv_device_viral_record_id ?? 0);
            if ($linkedId > 0) {
                $linkedRecord = SvDeviceViralRecord::where('id', $linkedId)->findOrEmpty();
                if ($linkedRecord->isEmpty()) {
                    $linkedRecord = null;
                }
            }

            $now = time();
            $taskDay = (string)($linkedRecord->day
                ?? $copywritingRecord->day
                ?? date('Y-m-d'));
            if ($taskDay === '') {
                $taskDay = date('Y-m-d');
            }
            $dayKey = str_replace('-', '', $taskDay);
            $errorContent = $contentPrefix . $dayKey . '/' . uniqid('', true);
            $errorHash = hash('sha256', $errorContent);

            $deviceCode = (string)($linkedRecord->device_code ?? $copywritingRecord->device_code ?? '');
            $personaId = (int)($linkedRecord->persona_id ?? $copywritingRecord->persona_id ?? 0);
            $publishMediaType = (int)($linkedRecord->publish_media_type
                ?? $copywritingRecord->publish_media_type
                ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO);
            if ($publishMediaType <= 0) {
                $publishMediaType = AiPersona::PUBLISH_MEDIA_TYPE_VIDEO;
            }

            // remark 仅取关联 sv_device_viral_record_id 对应记录；无记录或 remark 为空则用默认文案
            $linkedRemark = trim((string)($linkedRecord->remark ?? ''));
            $failureRemark = $linkedRemark !== '' ? $linkedRemark : '爆款仿写失败降级';

            $errorRecord = SvDeviceViralRecord::create([
                'user_id'            => (int)($linkedRecord->user_id ?? $copywritingRecord->user_id ?? 0),
                'viral_id'           => (int)($linkedRecord->viral_id ?? 0),
                'viral_account_id'   => (int)($linkedRecord->viral_account_id ?? 0),
                'auto_type'          => (int)($linkedRecord->auto_type ?? 1),
                'device_code'        => $deviceCode,
                'account'            => (string)($linkedRecord->account ?? ''),
                'nickname'           => (string)($linkedRecord->nickname ?? ''),
                'persona_id'         => $personaId,
                'keyword'            => (string)($linkedRecord->keyword ?? ''),
                'generation_types'   => $linkedRecord->generation_types ?? [],
                'publish_platform'   => (int)($linkedRecord->publish_platform ?? 4),
                'publish_media_type' => $publishMediaType,
                'video_duration'     => 0,
                'content'            => $errorContent,
                'hash'               => $errorHash,
                'copywriting'        => [],
                'copywriting_type'   => SvDeviceViralRecord::COPYWRITING_TYPE_FALLBACK_ERROR,
                'status'             => SvDeviceViralRecord::STATUS_FALLBACK_ERROR,
                'remark'             => $failureRemark,
                'day'                => $taskDay,
                'use_time'           => $now,
                'retry'              => 0,
                'create_time'        => $now,
                'update_time'        => $now,
            ]);

            Log::channel('explosionVideoSynthesis')->write('仿写失败降级写入status=7成功：' . json_encode([
                'copywriting_id' => $copywritingId,
                'linked_record_id' => $linkedId,
                'error_record_id' => (int)$errorRecord->id,
                'device_code' => $deviceCode,
                'persona_id' => $personaId,
                'remark' => $failureRemark,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $th) {
            Log::channel('explosionVideoSynthesis')->write('仿写失败降级写入status=7失败：' . json_encode([
                'copywriting_id' => (int)($copywritingRecord->id ?? 0),
                'error' => $th->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * 核心执行流程
     */
    protected static function executeCoreSynthesis($copywritingRecord, $config, $text, $title, $source, $taskType = null)
    {
        $deviceCode = $copywritingRecord->device_code;
        $personaId = $copywritingRecord->persona_id;
        $device = SvDevice::where('device_code', $deviceCode)->findOrEmpty();
        if ($device->isEmpty()) {
            throw new \Exception('设备不存在');
        }
        $persona = AiPersona::where('id', $personaId)->findOrEmpty();

        if ($persona->isEmpty()) throw new \Exception('设备或人设不存在');
        if ((int)$source === AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE
            && self::hasOnlyNewsMixcutGenerationTypes($config)
        ) {
            Log::channel('explosionVideoSynthesis')->write('爆款仿写配置仅包含新闻体，新闻体固定AI生成，跳过文案维度：' . $copywritingRecord->id);
            self::markCopywritingAsUsed((int)$copywritingRecord->id, '新闻体固定AI生成跳过仿写文案');
            return false;
        }
        if ($persona->isEmpty()) throw new \Exception('人设不存在');
        $materials = self::getMaterialsForImitation($device);

        if (!$taskType) $taskType = self::getNextTaskType($deviceCode, $personaId, $config);

        // status=1 表示关键词/仿写失败，走 Coze 纯 AI 降级生成
        $isDowngrade = (int)($copywritingRecord->status == 1);

        return self::createImitationVideoTask($device, $persona, $config, $materials, $text, $title, $taskType, $source, $isDowngrade);
    }

    /**
     * 创建任务逻辑
     */
    protected static function createImitationVideoTask($device, $persona, $config, $groupedData, $rewrittenText, $title, $taskType, $source, int $isDowngrade = 0, $materialKeywords = null, $execTime = '')
    {
        // 注:转码就绪/分辨率门禁已下沉到 ShanjianVideoTaskLogic::compositeVideoCron 下发闪剪前统一把关。
        // 这里只负责建 task(status=0,创作记录显示"生成中"),转码完成后由 cron 自动下发。
        $userId = $device->user_id;
        $deviceCode = $device->device_code;
        $card_name = $persona->persona_name ?? '';
        $card_introduced = $persona->persona_desc ?? '';
        $typeName = self::getShanjianTypeName($taskType);
        $keywordsForMaterial = $materialKeywords ?? $rewrittenText;

        if ($taskType == 1 && in_array($config->visual_material_source, [1, 2])) {
            $selectedMaterials = self::selectMaterialsForTaskTypeAndVisualMaterialSource($groupedData['videos'], $groupedData['images'], $taskType, $deviceCode, $config, $keywordsForMaterial);
        } else {
            $selectedMaterials = self::selectMaterialsForTaskType($groupedData['videos'], $groupedData['images'], $taskType, $deviceCode);
        }

        // 闪剪限制：素材总时长不能超过5分钟。图片按2s、视频按实际时长累计裁剪。
        $selectedMaterials = self::trimMaterialsByDuration($selectedMaterials);
        if (empty($selectedMaterials)) throw new \Exception('人设下无可用素材');

        Db::startTrans();
        try {
            $voiceId = '';
            $anchorId = '';
            $pic = '';
            $selectedAvatar = [];
            $scene = 'oralMixCutting';
            $extradata = [];
            switch ($taskType) {
                case 1:
                    $scene = 'virtualman';
                    $selectedAvatar = self::selectPersonaDigitalAvatarForAutoTask((int)$persona->id, (int)$userId);
                    if (!empty($selectedAvatar)) {
                        $anchorId = $selectedAvatar['third_avatar_id'] ?? '';
                        $voiceId = $selectedAvatar['third_voice_id'] ?? '';
                        $pic = $selectedAvatar['cover_url'] ?? '';
                    }
                    break;
                case 4:
                    // 新闻体混剪不需要音色，也不走 MiniMax TTS
                    $scene = 'newsMixCutting';
                    $extradata['videoDuration'] = self::getNewsMixcutDuration($config);
                    break;
                default:
                    $scene = 'oralMixCutting';
                    $voiceId = self::selectPersonaDigitalVoiceForAutoTask((int)$persona->id, (int)$userId, (int)$taskType);
                    break;
            }
            $extradata['volume'] = AiPersonaSynthesisConfig::normalizeMusicVolume($config->music_volume ?? AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT);
            $extradata['speed_ratio'] = AiPersonaSynthesisConfig::normalizeSpeechRate($config->speech_rate ?? AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT);
            if ($execTime !== '') {
                $extradata['exec_time'] = json_encode([$execTime], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            if (empty($voiceId) && $taskType != 4) throw new \Exception('未绑定有效音色');

            // MiniMax 仅数字人口播(1)/素材混剪(3)需要；新闻体(4)不走音频合成
            // 形象绑定 MiniMax 音色时同样走 TTS 中间任务（AutoVideoSynthesis 入口共用本逻辑）
            $contentForTts = trim((string)$rewrittenText);
            $needsVoiceSynthesis = in_array((int)$taskType, [1, 3], true);
            $isMinimaxVoice = $needsVoiceSynthesis
                && $voiceId !== ''
                && ShanjianVideoSettingLogic::isMinimaxVoiceId((string)$voiceId, (int)$userId);
            Log::channel('ipVideoSynthesis')->write('自动化音色判定' . json_encode([
                'device_code' => $deviceCode,
                'persona_id' => (int)$persona->id,
                'shanjian_type' => (int)$taskType,
                'voice_id' => $voiceId,
                'is_minimax' => $isMinimaxVoice ? 1 : 0,
                'has_tts_content' => $contentForTts !== '' ? 1 : 0,
                'avatar_id' => (int)($selectedAvatar['id'] ?? 0),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if ($isMinimaxVoice && $contentForTts === '') {
                $fallbackVoiceId = self::resolveNonMinimaxFallbackVoiceId(
                    (int)$persona->id,
                    (int)$userId,
                    (int)$taskType,
                    $selectedAvatar,
                    (string)$voiceId
                );
                if ($fallbackVoiceId === '' || $fallbackVoiceId === $voiceId) {
                    throw new \Exception('MiniMax音色需要文案，当前任务无文案且无人设可用非MiniMax音色');
                }
                Log::channel('ipVideoSynthesis')->write('MiniMax无文案降级非MiniMax音色' . json_encode([
                    'device_code' => $deviceCode,
                    'persona_id' => (int)$persona->id,
                    'from_voice_id' => $voiceId,
                    'to_voice_id' => $fallbackVoiceId,
                    'shanjian_type' => (int)$taskType,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $voiceId = $fallbackVoiceId;
                $isMinimaxVoice = false;
            }

            $formattedMaterials = [];
            $materialLogs = [];
            foreach ($selectedMaterials as $m) {
                if (empty($pic)) $pic = $m['thumbnail_url'];
                $isVideo = (int)($m['material_type'] ?? 0) === 1;
                $formattedMaterials[] = [
                    'type' => $isVideo ? 'video' : 'image',
                    'fileUrl' => $m['file_url'],
                    // 图片固定按2s计入（与闪剪侧校验口径一致），避免 duration=0 导致本地低估
                    'duration' => $isVideo ? (int)max(0, (float)($m['duration'] ?? 0)) : 2
                ];
                $materialLogs[] = [
                    'material_id' => $m['id'],
                    'user_id' => $userId,
                    'persona_id' => $persona->id,
                    'use_scene' => 1,
                    'create_time' => time()
                ];
            }

            // --- 修正后的 ShanjianVideoSetting Copywriting 格式 ---
            // 格式：[{"title":"标题","content":"文案内容"}]
            $settingCopywriting = '';
            $copyData = [];
            if ($source == 3) {
                $settingCopywriting = json_encode([], JSON_UNESCAPED_UNICODE);
            } else {
                $copyData = [
                    [
                        'title'   => $title,
                        'content' => $rewrittenText
                    ]
                ];
                $settingCopywriting = json_encode($copyData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $taskId = generate_unique_task_id();
            $cover_result_id = '';
            $thumb_status = 4;
            $coverKeywords = $rewrittenText;
            if ($source == 3 && $coverKeywords === '') {
                $coverKeywords = $title ?: ($card_introduced ?: $card_name);
            }
            switch ($config->video_cover_source) {
                case 2:
                    $cover_result_id = self::getAiCover($pic, $coverKeywords, $userId, $taskId);
                    if(!empty($cover_result_id)) {
                        $thumb_status = 1;
                    }else{
                        $thumb_status = 3;
                    }
                    break;
                case 3:
                    $pic = $config->pic;
                    break;
            }

            $settingName = '社媒平台-' . mb_substr($title, 0, 8) . '-' . date('YmdHis');
            foreach ($formattedMaterials as &$m) {
                $m['fileUrl'] = FileService::getFileUrl($m['fileUrl']);
            }
            unset($m);

            $music_url = self::resolveMusicUrlByConfig($config, (int)$persona->id);
            $clipId = SynthesisTemplateConfigService::pickTemplateId(
                $config->template_config ?? [],
                (int)$taskType,
                1
            );
            $visual_material_source = (int)$config->visual_material_source;
            if ($source == 3 || in_array($taskType, [3, 4], true)) {
                $visual_material_source = 3;
            }

            $pendingTask = [
                'name' => $settingName,
                'pic' => $pic,
                'task_id' => $taskId,
                'status' => 0,
                'audio_type' => 1,
                'auto_type' => 1,
                'card_name' => $card_name,
                'card_introduced' => $card_introduced,
                'device_code' => $deviceCode,
                'shanjian_type' => $taskType,
                'user_id' => $userId,
                'anchor_id' => $anchorId,
                'voice_id' => $voiceId ?: '',
                'persona_id' => $persona->id,
                'music_url' => $music_url,
                'title' => $title,
                'msg' => $rewrittenText,
                'material' => json_encode($formattedMaterials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'clip_id' => $clipId,
                'copywriting_source' => $source,
                'extra' => json_encode($extradata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'visual_material_source' => $visual_material_source,
                'video_cover_source' => $config->video_cover_source,
                'thumb_status' => $thumb_status,
                'cover_result_id' => $cover_result_id,
                'is_downgrade' => $isDowngrade,
            ];

            if ($isMinimaxVoice) {
                // 对齐手动 addType3：先建 setting + MiniMax TTS 中间任务，并同步落 status=-1 占位视频任务，
                // 供列表立即展示；TTS/ASR 完成后由 VoiceLogic 回填 audio_url 并改为 status=0。
                $pendingTask['status'] = -1;
                $requestJson = [
                    'user_id' => $userId,
                    'auto_pending_task' => $pendingTask,
                    'copywriting' => $copyData,
                    'voice' => $voiceId,
                ];
                $setting = ShanjianVideoSetting::create([
                    'name'          => $settingName,
                    'auto_type'     => 1,
                    'device_code'   => $deviceCode,
                    'user_id'       => $userId,
                    'task_id'       => generate_unique_task_id(),
                    'status'        => 1,
                    'shanjian_type' => $taskType,
                    'copywriting'   => $settingCopywriting,
                    'material'      => json_encode($formattedMaterials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'voice'         => $voiceId ?: '',
                    'video_count'   => 1,
                    'request_json'  => json_encode($requestJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'create_time'   => time(),
                    'update_time'   => time()
                ]);

                $minimaxTask = ShanjianVideoSettingLogic::createAudioTask(
                    $setting->id,
                    $voiceId,
                    [['content' => $contentForTts]],
                    (int)$userId
                );
                $minimaxTaskId = (int)($minimaxTask->id ?? 0);

                $task = ShanjianVideoTask::create(array_merge($pendingTask, [
                    'video_setting_id' => $setting->id,
                    'minimax_task_id' => $minimaxTaskId,
                    'audio_url' => '',
                    'create_time' => time(),
                    'update_time' => time(),
                ]));

                foreach ($materialLogs as &$log) {
                    $log['task_id'] = $task->id;
                }
                unset($log);
                MaterialUseLog::insertAll($materialLogs);

                Db::commit();
                Log::channel('ipVideoSynthesis')->write('自动化命中MiniMax音色，已建占位任务等待TTS' . json_encode([
                    'setting_id' => $setting->id,
                    'task_id' => $task->id,
                    'minimax_task_id' => $minimaxTaskId,
                    'voice_id' => $voiceId,
                    'device_code' => $deviceCode,
                    'persona_id' => $persona->id,
                    'shanjian_type' => $taskType,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return ['task_id' => $task->id, 'setting_id' => $setting->id, 'minimax_pending' => 1];
            }

            $setting = ShanjianVideoSetting::create([
                'name'          => $settingName,
                'auto_type'     => 1,
                'device_code'   => $deviceCode,
                'user_id'       => $userId,
                'task_id'       => generate_unique_task_id(),
                'status'        => 1,
                'shanjian_type' => $taskType,
                'copywriting'   => $settingCopywriting,
                'material'      => json_encode($formattedMaterials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'voice'         => $voiceId ?: '',
                'video_count'   => 1,
                'create_time'   => time(),
                'update_time'   => time()
            ]);

            $task = ShanjianVideoTask::create(array_merge($pendingTask, [
                'video_setting_id' => $setting->id,
                'create_time' => time(),
                'update_time' => time(),
            ]));

            foreach ($materialLogs as &$log) {
                $log['task_id'] = $task->id;
            }
            MaterialUseLog::insertAll($materialLogs);

            Db::commit();
            return ['task_id' => $task->id];
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 远程文件转存（兼容方法不存在或单文件失败场景）
     */
    public static function transcodeRemoteFileBySourceSafe(string $fileUrl, string $type, int $userId): ?string
    {
        if (!method_exists(UploadService::class, 'transcodeRemoteFileBySource')) {
            throw new \RuntimeException('UploadService::transcodeRemoteFileBySource 方法不可用');
        }
        $startAt = microtime(true);
        Log::channel('explosionVideoSynthesis')->write('transcode监控:start ' . json_encode([
            'type' => $type,
            'user_id' => $userId,
            'url' => mb_substr($fileUrl, 0, 200),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        try {
            $localFileUrl = UploadService::transcodeRemoteFileBySource($fileUrl, $type, $userId);
            Log::channel('explosionVideoSynthesis')->write('transcode监控:end ' . json_encode([
                'type' => $type,
                'user_id' => $userId,
                'elapsed_ms' => (int)((microtime(true) - $startAt) * 1000),
                'success' => !empty($localFileUrl),
                'local_url' => !empty($localFileUrl) ? mb_substr((string)$localFileUrl, 0, 200) : '',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $localFileUrl ?: null;
        } catch (\Throwable $e) {
            Log::channel('explosionVideoSynthesis')->write('transcode监控:exception ' . json_encode([
                'type' => $type,
                'user_id' => $userId,
                'elapsed_ms' => (int)((microtime(true) - $startAt) * 1000),
                'url' => mb_substr($fileUrl, 0, 200),
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return null;
        }
    }

    /**
     * 创建失败的视频合成任务记录（Setting + Task）
     */
    public static function createFailedSynthesisTask($device, $persona, int $taskType, string $remark, array $options = []): ?array
    {
        try {
            return Db::transaction(function () use ($device, $persona, $taskType, $remark, $options) {
            $userId = (int)$device->user_id;
            $deviceCode = (string)$device->device_code;
            $cardName = $persona->persona_name ?? '';
            $cardIntroduced = $persona->persona_desc ?? '';
            $typeName = self::getShanjianTypeName($taskType);
            $wechatType = (int)($options['wechat_type'] ?? 0);
            $namePrefix = $wechatType ? '朋友圈' : '社媒平台';
            $title = (string)($options['title'] ?? ($namePrefix . '-生成失败-' . $typeName));
            $msg = (string)($options['msg'] ?? '');
            $source = (int)($options['copywriting_source'] ?? 0);
            $remark = mb_substr($remark, 0, 255);

            $config = $options['config'] ?? null;
            $visualMaterialSource = (int)($options['visual_material_source'] ?? ($config->visual_material_source ?? 0));
            $videoCoverSource = (int)($options['video_cover_source'] ?? ($config->video_cover_source ?? 0));

            $settingCopywriting = '';
            if ($msg !== '' || $title !== '') {
                $settingCopywriting = json_encode([
                    ['title' => $title, 'content' => $msg],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $extradata = [];
            if (!empty($options['exec_time'])) {
                $extradata['exec_time'] = json_encode([$options['exec_time']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $settingName = $namePrefix . '-' . mb_substr($title, 0, 8) . '-' . date('YmdHis') . '-' . $typeName;
            $setting = ShanjianVideoSetting::create([
                'name' => $settingName,
                'auto_type' => 1,
                'wechat_type' => $wechatType,
                'device_code' => $deviceCode,
                'user_id' => $userId,
                'task_id' => generate_unique_task_id(),
                'status' => 5,
                'shanjian_type' => $taskType,
                'copywriting' => $settingCopywriting,
                'material' => json_encode([], JSON_UNESCAPED_UNICODE),
                'video_count' => 1,
                'success_num' => 0,
                'error_num' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            $task = ShanjianVideoTask::create([
                'name' => $settingName,
                'pic' => '',
                'task_id' => generate_unique_task_id(),
                'status' => ShanjianVideoTask::STATUS_FAILED,
                'remark' => $remark,
                'audio_type' => 1,
                'auto_type' => 1,
                'wechat_type' => $wechatType,
                'device_code' => $deviceCode,
                'shanjian_type' => $taskType,
                'user_id' => $userId,
                'video_setting_id' => $setting->id,
                'anchor_id' => '',
                'voice_id' => '',
                'persona_id' => $persona->id,
                'card_name' => $cardName,
                'card_introduced' => $cardIntroduced,
                'title' => $title,
                'msg' => $msg,
                'material' => json_encode([], JSON_UNESCAPED_UNICODE),
                'clip_id' => '',
                'music_url' => '',
                'copywriting_source' => $source,
                'extra' => json_encode($extradata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'visual_material_source' => $visualMaterialSource,
                'video_cover_source' => $videoCoverSource,
                'is_downgrade' => (int)($options['is_downgrade'] ?? 0),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return [
                'task_id' => $task->id,
                'setting_id' => $setting->id,
                'failed' => true,
            ];
            });
        } catch (\Throwable $e) {
            $channel = $options['log_channel'] ?? (!empty($options['wechat_type']) ? 'wechatVideoSynthesis' : 'ipVideoSynthesis');
            Log::channel($channel)->write('创建失败视频任务记录异常: ' . $e->getMessage());
            return null;
        }
    }

    protected static function getNextTaskType($deviceCode, $personaId, $config)
    {
        $isImitationConfig = (int)($config->copywriting_source ?? 0) === AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE;
        $generationTypes = $isImitationConfig
            ? self::getImitationGenerationTypes($config)
            : ($config->generation_types ?? []);
        if (empty($generationTypes)) {
            $taskType = 3;
        } else {
            $lastType = ShanjianVideoTask::where('device_code', $deviceCode)->where('persona_id', $personaId)->order('id', 'desc')->value('shanjian_type');
            $currentIndex = array_search($lastType, $generationTypes);
            if ($currentIndex === false) {
                $taskType = intval($generationTypes[0]);
            } else {
                $taskType = intval($generationTypes[($currentIndex + 1) % count($generationTypes)]);
            }
        }

        $resolvedTaskType = self::resolveTaskTypeByVoice($taskType, $personaId, (int)$config->user_id);
        if ($isImitationConfig && $resolvedTaskType === 4) {
            return $taskType;
        }

        return $resolvedTaskType;
    }

    protected static function hasOnlyNewsMixcutGenerationTypes($config): bool
    {
        $generationTypes = $config->generation_types ?? [];
        if (!is_array($generationTypes) || empty($generationTypes)) {
            return false;
        }

        foreach ($generationTypes as $type) {
            if ((int)$type !== 4) {
                return false;
            }
        }

        return true;
    }

    protected static function getImitationGenerationTypes($config): array
    {
        $generationTypes = $config->generation_types ?? [];
        if (!is_array($generationTypes)) {
            return [];
        }

        return array_values(array_filter($generationTypes, static function ($type) {
            return (int)$type !== 4;
        }));
    }

    protected static function getNewsMixcutDuration($config = null): int
    {
        $duration = $config->news_mixcut_duration ?? AiPersonaSynthesisConfig::NEWS_MIXCUT_DURATION_DEFAULT;
        return AiPersonaSynthesisConfig::normalizeNewsMixcutDuration($duration);
    }

    /**
     * 素材混剪(3)需闪剪音色；未配置则降级为新闻体(4)
     */
    protected static function resolveTaskTypeByVoice(int $taskType, int $personaId, int $userId): int
    {
        if ($taskType === 1) {
            $avatar = AiPersonaDigitalAvatar::availableQuery()
                ->where('ad.persona_id', $personaId)
                ->where('ad.user_id', $userId)
                ->findOrEmpty();
            if ($avatar->isEmpty()) {
                $taskType = 3;
            }
        }
        if ($taskType === 3) {
            $voice = AiPersonaDigitalVoice::availableQuery()
                ->where('ad.persona_id', $personaId)
                ->where('ad.user_id', $userId)
                ->whereIn('ad.provider', AiPersonaDigitalVoice::synthesisProviders())
                ->findOrEmpty();
            if ($voice->isEmpty()) {
                return 4;
            }
        }
        return $taskType;
    }

    /**
     * 自动合成数字人口播时从人设可用形象中随机选取。
     * 按 third_avatar_id 去重，避免 shanjian_anchor 一对多 JOIN 导致重复项偏置。
     */
    protected static function selectPersonaDigitalAvatarForAutoTask(int $personaId, int $userId): array
    {
        $avatars = AiPersonaDigitalAvatar::availableQuery()
            ->field('ad.*')
            ->where('ad.persona_id', $personaId)
            ->where('ad.user_id', $userId)
            ->order(['ad.sort' => 'desc', 'ad.id' => 'asc'])
            ->select()
            ->toArray();

        $unique = [];
        foreach ($avatars as $avatar) {
            $avatarId = (string)($avatar['third_avatar_id'] ?? '');
            if ($avatarId === '' || isset($unique[$avatarId])) {
                continue;
            }
            $unique[$avatarId] = $avatar;
        }
        $avatars = array_values($unique);

        if (empty($avatars)) {
            return [];
        }

        return $avatars[array_rand($avatars)];
    }

    /**
     * MiniMax 无文案时降级到非 MiniMax 音色：
     * 1) 数字人口播优先回落到所选形象的闪剪原音
     * 2) 再回落到人设可用非 MiniMax 音色列表
     */
    protected static function resolveNonMinimaxFallbackVoiceId(
        int $personaId,
        int $userId,
        int $taskType,
        array $selectedAvatar,
        string $currentVoiceId
    ): string {
        if ($taskType === 1) {
            $dhId = (int)($selectedAvatar['dh_id'] ?? 0);
            if ($dhId > 0) {
                $shanjian = ShanjianAnchor::where('dh_id', $dhId)
                    ->where('user_id', $userId)
                    ->where('status', 6)
                    ->whereNull('delete_time')
                    ->findOrEmpty();
                $originVoiceId = trim((string)($shanjian['voice_id'] ?? ''));
                if ($originVoiceId !== ''
                    && $originVoiceId !== $currentVoiceId
                    && !ShanjianVideoSettingLogic::isMinimaxVoiceId($originVoiceId, $userId)
                ) {
                    return $originVoiceId;
                }
            }
        }

        return self::selectPersonaDigitalVoiceForAutoTask($personaId, $userId, $taskType, true);
    }

    /**
     * 自动合成非数字人口播时按闪剪音色列表轮换，避免始终使用第一条音色。
     * @param bool $excludeMinimax 为 true 时排除 MiniMax(model_version 10/11)
     */
    protected static function selectPersonaDigitalVoiceForAutoTask(int $personaId, int $userId, int $taskType, bool $excludeMinimax = false): string
    {
        $query = AiPersonaDigitalVoice::availableQuery()
            ->where('ad.persona_id', $personaId)
            ->where('ad.user_id', $userId)
            ->whereIn('ad.provider', AiPersonaDigitalVoice::synthesisProviders())
            ->order(['ad.sort' => 'desc', 'ad.id' => 'asc']);
        if ($excludeMinimax) {
            // availableQuery 已 leftJoin human_voice hv；兼容历史 provider=shanjian 的 MiniMax 记录
            $query->whereRaw('(hv.model_version IS NULL OR hv.model_version NOT IN (10, 11))')
                ->where('ad.provider', '<>', AiPersonaDigitalVoice::PROVIDER_MINIMAX);
        }
        $voices = $query->column('ad.third_voice_id');
        $voices = array_values(array_filter(array_map('strval', $voices), static function ($voiceId) {
            return $voiceId !== '';
        }));

        if (empty($voices)) {
            return '';
        }

        $index = self::getNextAutoResourceIndex($userId, $personaId, $taskType, 'voice_id', $voices);

        return $voices[$index] ?? $voices[0];
    }

    /**
     * 根据最近一次已建自动任务使用的资源，取资源列表中的下一条。
     */
    private static function getNextAutoResourceIndex(int $userId, int $personaId, int $taskType, string $resourceField, array $resourceIds): int
    {
        $resourceIds = array_values(array_filter($resourceIds, static function ($resourceId) {
            return $resourceId !== '';
        }));
        $total = count($resourceIds);
        if ($total === 0) {
            return 0;
        }

        $lastResourceId = ShanjianVideoTask::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('auto_type', 1)
            ->where('shanjian_type', $taskType)
            ->where($resourceField, '<>', '')
            ->order('id', 'desc')
            ->value($resourceField);

        if ($lastResourceId !== null && $lastResourceId !== '') {
            $lastIndex = array_search((string)$lastResourceId, $resourceIds, true);
            if ($lastIndex !== false) {
                return ($lastIndex + 1) % $total;
            }
        }

        $usedCount = ShanjianVideoTask::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('auto_type', 1)
            ->where('shanjian_type', $taskType)
            ->where($resourceField, '<>', '')
            ->count();

        return ((int)$usedCount) % $total;
    }

    protected static function getMaterialsForImitation($device)
    {
        $all = MaterialModel::where('persona_id', $device->persona_id)
        ->where('use_status', 1)
        ->where('is_wechat', 0)
        ->where('publish_mode', 1)
        ->whereIn('material_type', [MaterialModel::MATERIAL_TYPE_VIDEO, MaterialModel::MATERIAL_TYPE_IMAGE])
        ->select()->toArray();
        $res = ['videos' => [], 'images' => []];
        foreach ($all as $item) {
            if ((int)$item['material_type'] === MaterialModel::MATERIAL_TYPE_VIDEO) {
                $res['videos'][] = $item;
            } elseif ((int)$item['material_type'] === MaterialModel::MATERIAL_TYPE_IMAGE) {
                $res['images'][] = $item;
            }
        }
        return $res;
    }

    /**
     * 系统音乐库：静态池随机
     */
    public static function resolveSystemMusicUrl(): string
    {
        return rtrim((string)config('app.app_host'), '/') . '/static/audio/music/' . random_int(1, 20) . '.mp3';
    }

    /**
     * 按合成配置选择背景音乐 URL
     * music_source: 1系统 2人设(空则系统兜底) 3不使用
     */
    public static function resolveMusicUrlByConfig($config, int $personaId): string
    {
        $source = AiPersonaSynthesisConfig::normalizeMusicSource(
            is_object($config) ? ($config->music_source ?? 1) : ($config['music_source'] ?? 1)
        );
        if ($source === AiPersonaSynthesisConfig::MUSIC_SOURCE_NONE) {
            return '';
        }
        if ($source === AiPersonaSynthesisConfig::MUSIC_SOURCE_PERSONA && $personaId > 0) {
            // 仅排除已知超过闪剪 300s 上限的音乐；NULL/0（未知时长）仍放行，等时长补全后自然收敛
            $urls = MaterialModel::where('persona_id', $personaId)
                ->where('use_status', MaterialModel::USE_STATUS_ENABLED)
                ->where('material_type', MaterialModel::MATERIAL_TYPE_MUSIC)
                ->where('file_url', '<>', '')
                ->where(function ($q) {
                    $q->whereNull('duration')->whereOr('duration', '<=', MaterialModel::MUSIC_MAX_DURATION);
                })
                ->column('file_url');
            if (!empty($urls)) {
                $picked = (string)$urls[array_rand($urls)];
                return $picked !== '' ? FileService::getFileUrl($picked) : self::resolveSystemMusicUrl();
            }
        }
        return self::resolveSystemMusicUrl();
    }

    /**
     * 是否必须从素材库取素材（shanjian_type=3/4 或 visual_material_source=2/3）
     */
    protected static function requiresLibraryMaterials(array $videoTypes, int $visualMaterialSource): bool
    {
        foreach ($videoTypes as $type) {
            if (in_array((int)$type, [3, 4], true)) {
                return true;
            }
        }
        return in_array((int)$visualMaterialSource, [2, 3], true);
    }

    /**
     * 校验素材库是否满足本次任务类型要求
     */
    protected static function assertGroupedMaterialsAvailable(array $groupedData, int $personaId, array $videoTypes, int $visualMaterialSource): void
    {
        if (self::requiresLibraryMaterials($videoTypes, $visualMaterialSource)
            && empty($groupedData['videos']) && empty($groupedData['images'])) {
            throw new \Exception('-绑定的人设' . AiPersona::formatLabel(null, $personaId) . '下没有可用的素材');
        }
    }

    protected static function selectMaterialsForTaskType($videos, $images, $taskType, $deviceCode)
    {
        $rules = [
            1 => ['v_min' => 2, 'v_max' => 3, 'i_min' => 2, 'i_max' => 3],
            3 => ['v_min' => 8, 'v_max' => 8, 'i_min' => 2, 'i_max' => 3],
            4 => ['v_min' => 5, 'v_max' => 5, 'i_min' => 2, 'i_max' => 3],
            2 => ['v_min' => 4, 'v_max' => 4, 'i_min' => 2, 'i_max' => 2],
        ];
        $rule = $rules[$taskType] ?? $rules[3];
        $vCount = rand($rule['v_min'], $rule['v_max']);
        $iCount = rand($rule['i_min'], $rule['i_max']);
        shuffle($videos);
        shuffle($images);
        $selectedVideos = array_slice($videos, 0, $vCount);
        $selectedImages = array_slice($images, 0, $iCount);

        $videoGap = $vCount - count($selectedVideos);
        $imageGap = $iCount - count($selectedImages);

        // 某一类素材不足时，用另一类已满足基础配额后的剩余素材补齐总数量。
        if ($videoGap > 0) {
            $selectedImages = array_merge($selectedImages, array_slice($images, $iCount, $videoGap));
        }
        if ($imageGap > 0) {
            $selectedVideos = array_merge($selectedVideos, array_slice($videos, $vCount, $imageGap));
        }

        $selected = self::trimMaterialsByDuration(array_merge($selectedVideos, $selectedImages));
        foreach ($selected as $s) {
            if (isset($s['id'])) {
                Cache::store('material_redis')->inc('material_' . $s['id'] . '_device_' . $deviceCode);
            }
        }
        return $selected;
    }

    /**
     * 按闪剪规则裁剪素材：委托统一门禁 ShanjianVideoSettingLogic::trimMaterialsByDuration
     */
    protected static function trimMaterialsByDuration(array $materials, float $maxTotal = 290, float $maxSingle = 59): array
    {
        return \app\api\logic\shanjian\ShanjianVideoSettingLogic::trimMaterialsByDuration($materials, $maxTotal, $maxSingle);
    }

    // private static function selectMaterialsForTaskTypeAndVisualMaterialSource($videos, $images, $taskType, $deviceCode, $visualMaterialSource) {
    //     $vCount = ($taskType == 3) ? 8 : 4; $iCount = 2;
    //     shuffle($videos); shuffle($images);
    //     $selected = array_merge(array_slice($videos, 0, $vCount), array_slice($images, 0, $iCount));
    //     foreach ($selected as $s) Cache::store('material_redis')->inc('material_' . $s['id'] . '_device_' . $deviceCode);
    //     return $selected;
    // }

    protected static function getShanjianTypeName(int $type): string
    {
        $typeNames = [1 => '数字人口播', 2 => '真人口播', 3 => '素材混剪', 4 => '新闻体'];
        return $typeNames[$type] ?? '未知类型';
    }

    /**
     * 优化后的素材选择逻辑
     * 涵盖：纯AI、AI+素材库、纯素材库三种模式
     */
    protected static function selectMaterialsForTaskTypeAndVisualMaterialSource($localVideos, $localImages, $taskType, $deviceCode, $config, $rewrittenText)
    {
        // 1. 定义基础生成规则所需的数量
        $rules = [
            1 => ['avatar' => true, 'v_min' => 2, 'v_max' => 3, 'i_min' => 2, 'i_max' => 3], // 数字人口播混剪: 1形象 + 2~3视频 + 2~3图片
            3 => ['avatar' => false, 'v_min' => 8, 'v_max' => 8, 'i_min' => 2, 'i_max' => 3], // 素材混剪: 8视频 + 2~3图片
            4 => ['avatar' => false, 'v_min' => 5, 'v_max' => 5, 'i_min' => 2, 'i_max' => 3], // 新闻体: 5视频 + 2~3图片
            2 => ['avatar' => false, 'v_min' => 4, 'v_max' => 4, 'i_min' => 2, 'i_max' => 2], // 默认/真人口播
        ];
        $rule = $rules[$taskType] ?? $rules[3];
        $needV = rand($rule['v_min'], $rule['v_max']);
        $needI = rand($rule['i_min'], $rule['i_max']);

        $finalVideos = [];
        $finalImages = [];

       // 1. 初始化默认值（处理 case 3 和 else 的逻辑）
        shuffle($localVideos);
        shuffle($localImages);
        $finalVideos = array_slice($localVideos, 0, $needV);
        $finalImages = array_slice($localImages, 0, $needI);

// 2. 仅在 taskType == 1 时根据不同模式调整素材来源
        if ($taskType == 1) {
            $sourceMode = $config->visual_material_source;

            if ($sourceMode == 1) {
                // 纯 AI 模式：直接覆盖初始化的本地素材
                list($finalVideos, $finalImages) = self::getAiMaterials($rewrittenText, $needV, $needI, $config->user_id, $config->persona_id);
            } 
            elseif ($sourceMode == 2) {
                // AI + 素材库模式：计算缺口并补充
                $vGap = $needV - count($finalVideos);
                $iGap = $needI - count($finalImages);

                if ($vGap > 0 || $iGap > 0) {
                    list($aiV, $aiI) = self::getAiMaterials($rewrittenText, max(0, $vGap), max(0, $iGap), $config->user_id, $config->persona_id);
                    $finalVideos = array_merge($finalVideos, $aiV);
                    $finalImages = array_merge($finalImages, $aiI);
                }
            }
        }


        // 2. 根据模式处理素材
       
        // 4. 格式化并记录缓存
        $selected = self::trimMaterialsByDuration(array_merge($finalVideos, $finalImages));
        foreach ($selected as $s) {
            if (isset($s['id'])) {
                Cache::store('material_redis')->inc('material_' . $s['id'] . '_device_' . $deviceCode);
            }
        }

        return $selected;
    }

    public static function getAiMaterials($text, $vCount, $iCount, $userId, $personaId)
    {
        $videos = [];
        $images = [];
        $now = time();
        $totalNeed = $vCount + $iCount;
        $existingRemoteUrls = Db::name('ai_persona_material')
            ->where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->whereNull('delete_time')
            ->where('remote_url', '<>', '')
            ->column('remote_url');
        $existingRemoteUrlMap = [];
        foreach ($existingRemoteUrls as $url) {
            $existingRemoteUrlMap[(string)$url] = true;
        }
        $currentBatchRemoteUrlMap = [];
        $startAt = microtime(true);
        $textPreview = mb_substr((string)$text, 0, 60);
        $logStage = function (string $stage, array $extra = []) use (&$videos, &$images, $startAt, $vCount, $iCount, $totalNeed, $userId, $personaId, $textPreview) {
            $payload = array_merge([
                'stage' => $stage,
                'user_id' => $userId,
                'persona_id' => $personaId,
                'need_video' => $vCount,
                'need_image' => $iCount,
                'need_total' => $totalNeed,
                'current_video' => count($videos),
                'current_image' => count($images),
                'elapsed_ms' => (int)((microtime(true) - $startAt) * 1000),
                'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'text_preview' => $textPreview,
            ], $extra);
            Log::channel('explosionVideoSynthesis')->write('getAiMaterials监控: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        };
        $maxElapsedSec = 100;
        $timeoutTriggered = false;
        // 单任务计时：每次关键词提取/素材抓取前重置，不做全流程累计超时
        $taskStartAt = microtime(true);
        $ensureNotTimeout = function (string $stage) use (&$taskStartAt, $maxElapsedSec, $logStage) {
            $elapsedSec = microtime(true) - $taskStartAt;
            if ($elapsedSec > $maxElapsedSec) {
                $logStage('timeout_guard', [
                    'timeout_stage' => $stage,
                    'task_elapsed_sec' => round($elapsedSec, 3),
                    'max_elapsed_sec' => $maxElapsedSec,
                ]);
                return false;
            }
            return true;
        };

        if (!method_exists(UploadService::class, 'transcodeRemoteFileBySource')) {
            $logStage('check_upload_service_method_missing');
            throw new \RuntimeException('UploadService::transcodeRemoteFileBySource 方法不可用');
        }

        try {
            $logStage('start');
            $request['keywords'] = $text;

            // A. 提取关键词：循环直到关键词数量至少能覆盖总需求（或达到最大循环）
            $keywordList = [];
            $loopCount = 0;
            $maxLoops = 5;
            while (count($keywordList) < $totalNeed && $loopCount < $maxLoops) {
                $taskStartAt = microtime(true);
                $keywords = self::requestUrl($request, self::EXTRACT_KEYWORDS, $userId);
                if (!$ensureNotTimeout('extract_keywords_loop')) {
                    $timeoutTriggered = true;
                    $logStage('extract_keywords_timeout_skip', ['keyword_loop' => $loopCount + 1]);
                    continue;
                }
                $newKeywords = $keywords['content'] ?? [];
                if (empty($newKeywords)) break;
                $keywordList = array_values(array_unique(array_merge($keywordList, $newKeywords)));
                $loopCount++;
            }
            $logStage('keywords_ready', [
                'keyword_count' => count($keywordList),
                'keyword_loop_count' => $loopCount,
            ]);
            $maxKeywordProcess = max(6, $totalNeed * 2);
            if (count($keywordList) > $maxKeywordProcess) {
                $keywordList = array_slice($keywordList, 0, $maxKeywordProcess);
                $logStage('keywords_trimmed', [
                    'trimmed_to' => count($keywordList),
                    'max_keyword_process' => $maxKeywordProcess,
                ]);
            }

            if (empty($keywordList)) {
                Log::channel('explosionVideoSynthesis')->write('grabMaterial关键词提取失败，文案：' . $text);
                $logStage('keywords_empty');
                return [$videos, $images];
            }
            $videoUsedKeys = $imageUsedKeys = [];
            // B. 尝试按原计划获取视频
            foreach ($keywordList as $key) {
                if (count($videos) >= $vCount) break;
                $taskStartAt = microtime(true);
                $materialtaskid = generate_unique_task_id();
                $logStage('video_fetch_request_start', ['keyword' => $key]);
                $vRes = self::requestUrl(['searchTerm' => $key, 'orientation' => 'portrait'], self::GRAB_VIDEO, $userId,$materialtaskid);
                if (!$ensureNotTimeout('video_fetch_loop')) {
                    $timeoutTriggered = true;
                    $logStage('video_fetch_keyword_timeout_skip', ['keyword' => $key]);
                    self::refundGrabTokens($materialtaskid, 'video', $userId, '视频抓取超时退费');
                    continue;
                }
                $logStage('video_fetch_request_end', [
                    'keyword' => $key,
                    'result_count' => is_array($vRes) ? count($vRes) : 0,
                ]);
                if (!empty($vRes)) {
                    $videoAdded = false;
                    foreach ($vRes as $item) {
                        if (count($videos) >= $vCount) break;
                        
                        $remoteUrl = $item['link'] ?? '';
                        $fileUrl = $remoteUrl;
                        $thumbUrl = $item['image'] ?? '';
                        if (empty($remoteUrl)) {
                            continue;
                        }
                        if (!FileService::isAllowedGrabMaterialUrl($remoteUrl, 'video')) {
                            continue;
                        }
                        if (isset($existingRemoteUrlMap[$remoteUrl]) || isset($currentBatchRemoteUrlMap[$remoteUrl])) {
                            continue;
                        }
                        
                        // 下载远程文件到本地，失败则跳过
                        if (!empty($fileUrl)) {
                            try {
                                $localFileUrl = self::transcodeRemoteFileBySourceSafe($fileUrl, 'video', $userId);
                                if ($localFileUrl) {
                                    $fileUrl = $localFileUrl;
                                } else {
                                    continue;
                                }
                            } catch (\Throwable $e) {
                                Log::channel('explosionVideoSynthesis')->write("grabMaterial视频下载失败，关键词: {$key}，URL: {$fileUrl}，错误: " . $e->getMessage());
                                continue;
                            }
                        }else{
                            continue;
                        }
                        
                        // 下载缩略图到本地
                        if (!empty($thumbUrl)) {
                            try {
                                $localThumbUrl = self::transcodeRemoteFileBySourceSafe($thumbUrl, 'image', $userId);
                                if ($localThumbUrl) {
                                    $thumbUrl = $localThumbUrl;
                                }
                            } catch (\Throwable $e) {
                                Log::channel('explosionVideoSynthesis')->write("grabMaterial缩略图下载失败，关键词: {$key}，URL: {$thumbUrl}，错误: " . $e->getMessage());
                            }
                        }else{
                            continue;
                        }
                        
                        $videos[] = [
                            'id' => 0,
                            'material_type' => 1,
                            'remote_url' => $remoteUrl,
                            'file_url' => $fileUrl,
                            'thumbnail_url' => $thumbUrl,
                            'duration' => $item['duration'] ?? 10,
                            'width' => $item['width'] ?? 0,
                            'height' => $item['height'] ?? 0,
                            'material_name' => $key,
                        ];
                        $currentBatchRemoteUrlMap[$remoteUrl] = true;
                        // 标记该关键词已使用，防止图片重复使用（可选）
                        $videoUsedKeys[] = $key;
                        $videoAdded = true;
                        break;
                    }
                    if (!$videoAdded) {
                        self::refundGrabTokens($materialtaskid, 'video', $userId, '视频抓取结果不可用退费');
                    }
                } else {
                    self::refundGrabTokens($materialtaskid, 'video', $userId, '视频抓取空结果退费');
                }
            }
            $logStage('video_fetch_done', [
                'video_used_keys_count' => count($videoUsedKeys),
            ]);

            // C. 尝试按原计划获取图片
            foreach ($keywordList as $key) {
                if (count($images) >= $iCount) break;
                $taskStartAt = microtime(true);
                $materialtaskid = generate_unique_task_id();
                $logStage('image_fetch_request_start', ['keyword' => $key]);
                $iRes = self::requestUrl(['searchTerm' => $key, 'orientation' => 'portrait'], self::GRAB_IMAGE, $userId,$materialtaskid);
                if (!$ensureNotTimeout('image_fetch_loop')) {
                    $timeoutTriggered = true;
                    $logStage('image_fetch_keyword_timeout_skip', ['keyword' => $key]);
                    self::refundGrabTokens($materialtaskid, 'image', $userId, '图片抓取超时退费');
                    continue;
                }
                $logStage('image_fetch_request_end', [
                    'keyword' => $key,
                    'result_count' => is_array($iRes) ? count($iRes) : 0,
                ]);
                if (!empty($iRes)) {
                    $imageAdded = false;
                    $imageFailReason = '图片抓取结果不可用退费';
                    foreach ($iRes as $item) {
                        if (count($images) >= $iCount) break;
                        
                        $remoteUrl = $item['link'] ?? '';
                        $fileUrl = $remoteUrl;
                        if (empty($remoteUrl)) {
                            $imageFailReason = '图片抓取远程文件为空退费';
                            continue;
                        }
                        if (!FileService::isAllowedGrabMaterialUrl($remoteUrl, 'image')) {
                            $imageFailReason = '图片抓取后缀不支持退费';
                            continue;
                        }
                        if (isset($existingRemoteUrlMap[$remoteUrl]) || isset($currentBatchRemoteUrlMap[$remoteUrl])) {
                            $imageFailReason = '图片抓取文件已存在退费';
                            continue;
                        }
                        
                        // 下载远程文件到本地，失败则跳过
                        if (!empty($fileUrl)) {
                            try {
                                $localFileUrl = self::transcodeRemoteFileBySourceSafe($fileUrl, 'image', $userId);
                                if ($localFileUrl) {
                                    $fileUrl = $localFileUrl;
                                } else {
                                    $imageFailReason = '图片抓取下载失败退费';
                                    continue;
                                }
                            } catch (\Throwable $e) {
                                Log::channel('explosionVideoSynthesis')->write("grabMaterial图片下载失败，关键词: {$key}，URL: {$fileUrl}，错误: " . $e->getMessage());
                                $imageFailReason = '图片抓取下载失败退费';
                                continue;
                            }
                        }else{
                            $imageFailReason = '图片抓取远程文件为空退费';
                            continue;
                        }
                        
                        $images[] = [
                              'id' => 0,
                              'material_type' => 2,
                              'remote_url' => $remoteUrl,
                              'file_url' => $fileUrl,
                              'thumbnail_url' => $fileUrl,
                              'duration' => 0,
                              'width' => $item['width'] ?? 0,
                              'height' => $item['height'] ?? 0,
                              'material_name' => $key,
                        ];
                        $currentBatchRemoteUrlMap[$remoteUrl] = true;
                        $imageUsedKeys[] = $key;
                        $imageAdded = true;
                        break;
                    }
                    if (!$imageAdded) {
                        self::refundGrabTokens($materialtaskid, 'image', $userId, $imageFailReason);
                    }
                } else {
                    self::refundGrabTokens($materialtaskid, 'image', $userId, '图片抓取空结果退费');
                }
            }
            $logStage('image_fetch_done', [
                'image_used_keys_count' => count($imageUsedKeys),
            ]);

            $appendMaterialBySearch = function (string $key, int $targetType, string $stageTag) use (
                $userId,
                &$videos,
                &$images,
                &$currentBatchRemoteUrlMap,
                $existingRemoteUrlMap,
                $logStage
            ) {
                $scene = $targetType === 1 ? self::GRAB_VIDEO : self::GRAB_IMAGE;
                $materialtaskid = generate_unique_task_id();
                $res = self::requestUrl(['searchTerm' => $key, 'orientation' => 'portrait'], $scene, $userId,$materialtaskid);
                if (empty($res)) {
                    self::refundGrabTokens($materialtaskid, $targetType === 1 ? 'video' : 'image', $userId, $stageTag . '空结果退费');
                    return false;
                }
                $appended = false;
                $refundReason = $stageTag . '不可用结果退费';
                foreach ($res as $item) {
                    $remoteUrl = (string)($item['link'] ?? '');
                    $fileUrl = $remoteUrl;
                    $thumbUrl = (string)($item['image'] ?? '');
                    if ($remoteUrl === '') {
                        $refundReason = $stageTag . ($targetType === 2 ? '远程文件为空退费' : '不可用结果退费');
                        continue;
                    }
                    if (!FileService::isAllowedGrabMaterialUrl($remoteUrl, $targetType === 1 ? 'video' : 'image')) {
                        $refundReason = $stageTag . '后缀不支持退费';
                        continue;
                    }
                    if (isset($existingRemoteUrlMap[$remoteUrl]) || isset($currentBatchRemoteUrlMap[$remoteUrl])) {
                        $refundReason = $stageTag . ($targetType === 2 ? '文件已存在退费' : '不可用结果退费');
                        continue;
                    }
                    try {
                        $localFileUrl = self::transcodeRemoteFileBySourceSafe($fileUrl, $targetType === 1 ? 'video' : 'image', $userId);
                        if (empty($localFileUrl)) {
                            $refundReason = $stageTag . ($targetType === 2 ? '下载失败退费' : '不可用结果退费');
                            continue;
                        }
                        $fileUrl = $localFileUrl;
                        if ($thumbUrl !== '') {
                            $localThumbUrl = self::transcodeRemoteFileBySourceSafe($thumbUrl, 'image', $userId);
                            if (!empty($localThumbUrl)) {
                                $thumbUrl = $localThumbUrl;
                            }
                        }
                    } catch (\Throwable $e) {
                        $logStage('material_append_error', [
                            'stage_tag' => $stageTag,
                            'target_type' => $targetType,
                            'keyword' => $key,
                            'error' => $e->getMessage(),
                        ]);
                        $refundReason = $stageTag . ($targetType === 2 ? '下载失败退费' : '不可用结果退费');
                        continue;
                    }

                    $itemPayload = [
                        'id' => 0,
                        'material_type' => $targetType,
                        'remote_url' => $remoteUrl,
                        'file_url' => $fileUrl,
                        'thumbnail_url' => $targetType === 1 ? ($thumbUrl ?: $fileUrl) : $fileUrl,
                        'duration' => $targetType === 1 ? ($item['duration'] ?? 10) : 0,
                        'width' => $item['width'] ?? 0,
                        'height' => $item['height'] ?? 0,
                        'material_name' => $key . '(' . $stageTag . ')',
                    ];
                    if ($targetType === 1) {
                        $videos[] = $itemPayload;
                    } else {
                        $images[] = $itemPayload;
                    }
                    $currentBatchRemoteUrlMap[$remoteUrl] = true;
                    $appended = true;
                    break;
                }
                if (!$appended) {
                    self::refundGrabTokens($materialtaskid, $targetType === 1 ? 'video' : 'image', $userId, $refundReason);
                }
                return $appended;
            };

            $runCrossTypeCompensation = function (array $keys, string $stageTag) use (
                &$videos,
                &$images,
                $vCount,
                $iCount,
                &$videoUsedKeys,
                &$imageUsedKeys,
                &$timeoutTriggered,
                $ensureNotTimeout,
                $appendMaterialBySearch,
                $logStage
            ) {
                if (count($images) < $iCount) {
                    foreach ($keys as $key) {
                        if (count($images) >= $iCount) {
                            break;
                        }
                        if (in_array($key, $videoUsedKeys, true)) {
                            continue;
                        }
                        $taskStartAt = microtime(true);
                        if ($appendMaterialBySearch($key, 1, '视频补图_' . $stageTag)) {
                            if (!$ensureNotTimeout('image_compensation_' . $stageTag)) {
                                $timeoutTriggered = true;
                                $logStage('image_compensation_timeout_skip', ['keyword' => $key, 'stage_tag' => $stageTag]);
                                continue;
                            }
                            $sourceVideo = end($videos);
                            if (!empty($sourceVideo)) {
                                $imageFileUrl = (string)($sourceVideo['thumbnail_url'] ?? '');
                                if ($imageFileUrl === '') {
                                    $imageFileUrl = (string)($sourceVideo['file_url'] ?? '');
                                }
                                if ($imageFileUrl !== '') {
                                    $images[] = [
                                        'id' => 0,
                                        'material_type' => 1,
                                        'remote_url' => (string)($sourceVideo['remote_url'] ?? ''),
                                        'file_url' => $imageFileUrl,
                                        'thumbnail_url' => $imageFileUrl,
                                        'duration' => $sourceVideo['duration'] ?? 0,
                                        'width' => (int)($sourceVideo['width'] ?? 0),
                                        'height' => (int)($sourceVideo['height'] ?? 0),
                                        'material_name' => (string)($sourceVideo['material_name'] ?? $key),
                                    ];
                                }
                            }
                            $imageUsedKeys[] = $key;
                        }
                    }
                }

                if (count($videos) < $vCount) {
                    foreach ($keys as $key) {
                        if (count($videos) >= $vCount) {
                            break;
                        }
                        if (in_array($key, $imageUsedKeys, true)) {
                            continue;
                        }
                        $taskStartAt = microtime(true);
                        if ($appendMaterialBySearch($key, 2, '图片补视频_' . $stageTag)) {
                            if (!$ensureNotTimeout('video_compensation_' . $stageTag)) {
                                $timeoutTriggered = true;
                                $logStage('video_compensation_timeout_skip', ['keyword' => $key, 'stage_tag' => $stageTag]);
                                continue;
                            }
                            $sourceImage = end($images);
                            if (!empty($sourceImage)) {
                                $videoFileUrl = (string)($sourceImage['file_url'] ?? '');
                                if ($videoFileUrl === '') {
                                    $videoFileUrl = (string)($sourceImage['thumbnail_url'] ?? '');
                                }
                                if ($videoFileUrl !== '') {
                                    $videos[] = [
                                        'id' => 0,
                                        'material_type' => 2,
                                        'remote_url' => (string)($sourceImage['remote_url'] ?? ''),
                                        'file_url' => $videoFileUrl,
                                        'thumbnail_url' => (string)($sourceImage['thumbnail_url'] ?? $videoFileUrl),
                                        'duration' => 0,
                                        'width' => (int)($sourceImage['width'] ?? 0),
                                        'height' => (int)($sourceImage['height'] ?? 0),
                                        'material_name' => (string)($sourceImage['material_name'] ?? $key),
                                    ];
                                }
                            }
                            $videoUsedKeys[] = $key;
                        }
                    }
                }

                $videoUsedKeys = array_values(array_unique($videoUsedKeys));
                $imageUsedKeys = array_values(array_unique($imageUsedKeys));
                $logStage('cross_type_compensation_done', [
                    'stage_tag' => $stageTag,
                    'video_used_keys_count' => count($videoUsedKeys),
                    'image_used_keys_count' => count($imageUsedKeys),
                    'current_total' => count($videos) + count($images),
                ]);
            };

            // 第三阶段：先跑一次互补
            $runCrossTypeCompensation($keywordList, 'phase1');

            // 第四阶段：异常兜底（最多一次），使用 iw_ai_persona.industry 作为泛关键词来源
            $fallbackCount = 0;
            $isFallbackTriggered = false;
            $currentTotal = count($videos) + count($images);
            if ($currentTotal < $totalNeed && !$timeoutTriggered && $fallbackCount < 1 && !$isFallbackTriggered) {
                $shortage = max(0, $vCount - count($videos)) + max(0, $iCount - count($images));
                $fallbackKeywordNeed = max(10, $shortage);
                $industry = (string)Db::name('ai_persona')
                    ->where('id', $personaId)
                    ->where('user_id', $userId)
                    ->whereNull('delete_time')
                    ->value('industry');
                $industry = trim($industry);

                if ($industry === '') {
                    $logStage('fallback_skipped_industry_empty', [
                        'fallback_reason' => 'material_not_enough',
                        'shortage' => $shortage,
                        'fallback_count' => $fallbackCount,
                    ]);
                } else {
                    $isFallbackTriggered = true;
                    $fallbackCount++;
                    $fallbackKeywordList = [];
                    $extractLoop = 0;
                    $maxExtractLoop = 3;
                    while (count($fallbackKeywordList) < $fallbackKeywordNeed && $extractLoop < $maxExtractLoop) {
                        $extractLoop++;
                        $taskStartAt = microtime(true);
                        $fallbackRes = self::requestUrl(['keywords' => $industry], self::EXTRACT_KEYWORDS, $userId);
                        if (!$ensureNotTimeout('fallback_extract_keywords_loop')) {
                            $timeoutTriggered = true;
                            $logStage('fallback_extract_keywords_timeout_skip', ['extract_loop' => $extractLoop]);
                            continue;
                        }
                        $fallbackPart = $fallbackRes['content'] ?? [];
                        if (empty($fallbackPart)) {
                            break;
                        }
                        $fallbackKeywordList = array_values(array_unique(array_merge($fallbackKeywordList, $fallbackPart)));
                    }
                    if (empty($fallbackKeywordList)) {
                        $fallbackKeywordList = preg_split('/[\s,，、]+/u', $industry, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    }
                    if (count($fallbackKeywordList) > $fallbackKeywordNeed) {
                        $fallbackKeywordList = array_slice($fallbackKeywordList, 0, $fallbackKeywordNeed);
                    }

                    $logStage('fallback_triggered', [
                        'fallback_reason' => 'material_not_enough',
                        'industry' => $industry,
                        'shortage' => $shortage,
                        'fallback_keyword_need' => $fallbackKeywordNeed,
                        'fallback_keyword_count' => count($fallbackKeywordList),
                        'fallback_count' => $fallbackCount,
                    ]);

                    foreach ($fallbackKeywordList as $fKey) {
                        $taskStartAt = microtime(true);
                        if (count($videos) < $vCount) {
                            if ($appendMaterialBySearch($fKey, 1, '行业兜底视频')) {
                                $videoUsedKeys[] = $fKey;
                            }
                        }
                        if (count($images) < $iCount) {
                            if ($appendMaterialBySearch($fKey, 2, '行业兜底图片')) {
                                $imageUsedKeys[] = $fKey;
                            }
                        }
                        if (!$ensureNotTimeout('fallback_fetch_loop')) {
                            $timeoutTriggered = true;
                            $logStage('fallback_fetch_timeout_skip', ['keyword' => $fKey]);
                            continue;
                        }
                        if (count($videos) >= $vCount && count($images) >= $iCount) {
                            break;
                        }
                    }

                    // 兜底后再跑一次互补
                    $runCrossTypeCompensation($fallbackKeywordList, 'fallback');
                }
            }

            $currentTotal = count($videos) + count($images);
            if ($currentTotal < $totalNeed && !$timeoutTriggered) {
                $msg = '视频总数数量' . count($videos) . '，图片总数数量' . count($images);
                $logStage('material_not_enough', [
                    'current_total' => $currentTotal,
                    'video_shortage' => max(0, $vCount - count($videos)),
                    'image_shortage' => max(0, $iCount - count($images)),
                    'message' => $msg,
                    'action' => 'log_only_continue',
                ]);
                Log::channel('explosionVideoSynthesis')->write('AI素材获取数量不够，已记录并放行：' . json_encode([
                    'user_id' => $userId,
                    'persona_id' => $personaId,
                    'need_video' => $vCount,
                    'need_image' => $iCount,
                    'current_video' => count($videos),
                    'current_image' => count($images),
                    'current_total' => $currentTotal,
                    'need_total' => $totalNeed,
                    'msg' => $msg,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            if ($timeoutTriggered) {
                $logStage('timeout_skip_fetch', ['current_total' => $currentTotal]);
            }
            $logStage('fetch_complete', ['current_total' => $currentTotal]);
        } catch (\Throwable $e) {
            $wenan = mb_substr($text ?? '', 0, 100);
            $logStage('exception', ['error' => $e->getMessage()]);
            Log::channel('explosionVideoSynthesis')->write("grabMaterial文案[{$wenan}]获取失败：" . $e->getMessage());
            throw $e;
        }

        if ($timeoutTriggered && empty($videos) && empty($images)) {
            $fallbackMaterial = Db::name('ai_persona_material')
                ->where('persona_id', $personaId)
                ->where('user_id', $userId)
                ->where('use_status', 1)
                ->where('is_wechat', 0)
                ->where('publish_mode', 1)
                ->whereNull('delete_time')
                ->orderRaw('rand()')
                ->find();
            if (!empty($fallbackMaterial)) {
                $fallback = [
                    'id' => (int)$fallbackMaterial['id'],
                    'material_type' => (int)$fallbackMaterial['material_type'],
                    'remote_url' => (string)($fallbackMaterial['remote_url'] ?? ''),
                    'file_url' => (string)($fallbackMaterial['file_url'] ?? ''),
                    'thumbnail_url' => (string)($fallbackMaterial['thumbnail_url'] ?? ''),
                    'duration' => (int)($fallbackMaterial['duration'] ?? 0),
                    'width' => (int)($fallbackMaterial['width'] ?? 0),
                    'height' => (int)($fallbackMaterial['height'] ?? 0),
                    'material_name' => (string)($fallbackMaterial['material_name'] ?? '兜底素材'),
                ];
                if ($fallback['material_type'] === 1) {
                    $videos[] = $fallback;
                } else {
                    $images[] = $fallback;
                }
                $logStage('timeout_fallback_one_material', [
                    'fallback_material_id' => $fallback['id'],
                    'fallback_material_type' => $fallback['material_type'],
                ]);
            } else {
                $logStage('timeout_fallback_empty');
            }
        }

        // 插入AI抓取的素材到数据库
        $allMaterials = array_merge($videos, $images);
        if (!empty($allMaterials)) {
            $insertData = [];
            foreach ($allMaterials as $item) {
                $insertData[] = [
                    'persona_id' => $personaId,
                    'user_id' => $userId,
                    'material_name' => $item['material_name'] ?? '',
                    'material_type' => $item['material_type'],
                    'remote_url' => $item['remote_url'] ?? '',
                    'is_wechat' => 0,
                    'file_url' => $item['file_url'],
                    'thumbnail_url' => $item['thumbnail_url'] ?? '',
                    'duration' => $item['duration'] ?? 0,
                    'width' => $item['width'] ?? 0,
                    'height' => $item['height'] ?? 0,
                    'use_status' => 1,
                    'publish_mode' => 1,
                    'grab_type' => 1,
                    'create_time' => $now,
                    'update_time' => $now,
                ];
            }
            if (!empty($insertData)) {
                $result = (new \app\common\model\aiPersona\Material())->saveAll($insertData);
                // 将新插入的id设置到返回数组中
                foreach ($result as $index => $model) {
                    $allMaterials[$index]['id'] = $model->id;
                }
                // 重新分离videos和images
                $videos = array_filter($allMaterials, fn($item) => $item['material_type'] == 1);
                $images = array_filter($allMaterials, fn($item) => $item['material_type'] == 2);
                $videos = array_values($videos);
                $images = array_values($images);
            }
        }
        $logStage('end', ['insert_count' => count($allMaterials)]);

        return [$videos, $images];
    }


    private static function requestUrl(array $request, string $scene, int $userId, string $taskId='')
    {
        $startAt = microtime(true);
        Log::channel('explosionVideoSynthesis')->write('requestUrl监控:start ' . json_encode([
            'scene' => $scene,
            'user_id' => $userId,
            'task_id' => $taskId,
            'request_keys' => array_keys($request),
            'search_term' => isset($request['searchTerm']) ? mb_substr((string)$request['searchTerm'], 0, 100) : '',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        try {

            [$tokenScene, $tokenCode] = match ($scene) {
                self::EXTRACT_KEYWORDS => ['extract_keywords', AccountLogEnum::TOKENS_DEC_EXTRACT_KEYWORDS],
                self::GRAB_IMAGE => ['grab_image', AccountLogEnum::TOKENS_DEC_GRAB_IMAGE],   
                self::GRAB_VIDEO => ['grab_video', AccountLogEnum::TOKENS_DEC_GRAB_VIDEO],
                self::SHANJIAN_AI_COVER => ['shanjian_ai_cover', AccountLogEnum::TOKENS_DEC_SHANJIAN_AI_COVER],
            }; //计费
            $unit = TokenLogService::checkToken($userId, $tokenScene); // 添加辅助参数
            $request['task_id'] = $taskId;
            $request['user_id'] = $userId;
            $request['now'] = time();
            switch ($scene) {
                case self::EXTRACT_KEYWORDS:
                     $response = \app\common\service\ToolsService::Coze()->extractKeywords($request);
                    break;
                case self::GRAB_IMAGE:
                    $response = \app\common\service\ToolsService::Grab()->image($request);
                    break;
                case self::GRAB_VIDEO:
                    $response = \app\common\service\ToolsService::Grab()->video($request);
                    break;
                case self::SHANJIAN_AI_COVER:
                    $response = \app\common\service\ToolsService::shanjian()->aiCover($request);
                    break;
                default:
            } //成功响应，需要扣费
            if (isset($response['code']) && $response['code'] == 10000) {
                $points = $unit;
                if ($points > 0) {
                    $break = true;
                    $extra = [];
                    switch ($scene) {
                        case self::EXTRACT_KEYWORDS:
                            $break = false;
                            $extra = ['扣费项目' => '根据输入内容提取短视频热点搜索关键词', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::GRAB_IMAGE:
                            $break = false;
                            $extra = ['扣费项目' =>'调用AI图片素材生成或匹配能力进行素材处理', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::GRAB_VIDEO:  
                            $break = false;
                            $extra = ['扣费项目' =>'调用AI视频素材生成或匹配能力进行素材处理', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::SHANJIAN_AI_COVER:
                            $break = false;
                            $extra = ['扣费项目' =>'使用调用AI自动生成视频封面图预扣费减少算力', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        default:
                    }
                    if ($break) {
                        return $response['data'] ?? [];
                    }

                    //token扣除
                    User::userTokensChange($userId, $points);
                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
                }
                Log::channel('explosionVideoSynthesis')->write('requestUrl监控:end ' . json_encode([
                    'scene' => $scene,
                    'user_id' => $userId,
                    'task_id' => $taskId,
                    'elapsed_ms' => (int)((microtime(true) - $startAt) * 1000),
                    'code' => $response['code'] ?? null,
                    'result_count' => is_array($response['data'] ?? null) ? count($response['data']) : 0,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return $response['data'] ?? [];
            }
            Log::channel('explosionVideoSynthesis')->write('requestUrl监控:end_non10000 ' . json_encode([
                'scene' => $scene,
                'user_id' => $userId,
                'task_id' => $taskId,
                'elapsed_ms' => (int)((microtime(true) - $startAt) * 1000),
                'code' => $response['code'] ?? null,
                'message' => $response['message'] ?? '',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $response;
        } catch (\Throwable $e) {
            Log::channel('explosionVideoSynthesis')->write('requestUrl监控:exception ' . json_encode([
                'scene' => $scene,
                'user_id' => $userId,
                'task_id' => $taskId,
                'elapsed_ms' => (int)((microtime(true) - $startAt) * 1000),
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            throw new \Exception($e->getMessage());
        }
    }


     public static function getAiCover($pic, $rewrittenText,$userId,string $taskId='')
    {
        try {
            
            if(empty($pic)){
                $pic = config('app.app_host') . '/static/images/cover/1.jpg';
            }else{
              $pic = FileService::getFileUrl($pic);
            }
            Log::channel('explosionVideoSynthesis')->write("封面图底图：" . $pic);
            $coze['sn'] = 9;
            $coze['number'] = 1;
            $coze['length'] = 1;
            $coze['keywords'] = $rewrittenText;
            $copywritingResult = AutoDeviceSettingLogic::copywriting($coze, $userId, 4);
            Log::channel('explosionVideoSynthesis')->write("获取封面图提示词" . json_encode($copywritingResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $content = $copywritingResult['content'] ?? '';
            $result = [];
            if (!empty($content)) {
                if (is_string($content)) {
                    $result = json_decode($content, true) ?: [];
                } elseif (is_array($content)) {
                    $result = $content;
                }
            }else{
               throw new \Exception('获取AI封面图文案失败');
            }
     
            $templateIds = [
                '691d283098bce90037c0c16f',
                '691d259de31e7b003134ad0b',
                '691d2133b44bf900329274ff',
                '691464bb404d7d0032ccf269',
                '68b6bcb47d86fd0030f8faa5',
                '68b18d4f1fc68f0030bb1471',
                '68b1838fabb02a00329c96f4',
                '68b181935fd04a002fff3df3',
                '68b178a88cb1b40031e33a70',
                '68b174b2abb02a00329c815c',
                '68b1722e5fd04a002fff23c2',
            ];
            $templateId = $templateIds[array_rand($templateIds)];
            $aiCoverParams = [
                "imageUrl"=> $pic,
                "templateId"=> $templateId,
                "processRules"=> $result,
            ];
         
            $response = self::requestUrl($aiCoverParams,self::SHANJIAN_AI_COVER,$userId,$taskId);
            Log::channel('explosionVideoSynthesis')->write("封面图生成成功：" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $taskId = $response['data']['taskId'] ?? '';
            if (empty($taskId) && $response['code'] != 10000) {
                $msg = $response['message'] ?? '封面图生成失败';
                throw new \Exception($msg);
            }
            return $taskId;
        } catch (\Throwable $e) {
            $msg = '封面图生成失败：' . $e->getMessage();
            Log::channel('explosionVideoSynthesis')->write($msg);
        }
        return "";
    }

    public static function refundGrabTokens($task_id,string $type,int $userId=0,string $reason='抓取失败退费'): bool
    {
        try {
            $taskId = $task_id ?? '';
            $type = $type ?? '';
            $userId = $userId ?? 0;

            if (empty($taskId)) {
                self::setError('任务ID不能为空');
                return false;
            }

            if ($type !== 'image' && $type !== 'video') {
                self::setError('类型只能是 image 或 video');
                return false;
            }
            $user = User::find($userId);
            if (!$user) {
                self::setError('用户不存在');
                return false;
            }

            $grab = \app\common\service\ToolsService::Grab();
            $middleResponse = $type === 'image'
                ? $grab->refundImage($taskId, $userId)
                : $grab->refundVideo($taskId, $userId);
            $middleCode = $middleResponse['code'] ?? null;
            $middleTaskStatus = $middleResponse['data']['task_status'] ?? null;
            $middleRefund = [
                'code' => $middleCode,
                'task_status' => $middleTaskStatus,
                'message' => $middleResponse['message'] ?? '',
            ];

            if ((int)$middleCode !== 10000) {
                Log::channel('explosionVideoSynthesis')->write('refundGrabTokens:middle_failed ' . json_encode([
                    'task_id' => $taskId,
                    'type' => $type,
                    'user_id' => $userId,
                    'reason' => $reason,
                    'middle_refund' => $middleRefund,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                self::setError($middleResponse['message'] ?? '中台退费失败');
                return false;
            }

            $typeID = $type === 'image' ? AccountLogEnum::TOKENS_DEC_GRAB_IMAGE : AccountLogEnum::TOKENS_DEC_GRAB_VIDEO;
            $decTotal = (float)UserTokensLog::where('user_id', $userId)
                ->where('change_type', $typeID)
                ->where('action', 2)
                ->where('task_id', $taskId)
                ->sum('change_amount');
            $refundTotal = (float)UserTokensLog::where('user_id', $userId)
                ->where('change_type', $typeID)
                ->where('action', 1)
                ->where('task_id', $taskId)
                ->sum('change_amount');
            $pendingRefund = round($decTotal - $refundTotal, 4);

            if ($pendingRefund <= 0) {
                Log::channel('explosionVideoSynthesis')->write('refundGrabTokens:skip ' . json_encode([
                    'task_id' => $taskId,
                    'type' => $type,
                    'user_id' => $userId,
                    'pending_refund' => $pendingRefund,
                    'reason' => '无可退金额或已退费',
                    'middle_refund' => $middleRefund,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return true;
            }

            AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $pendingRefund, $taskId, [
                '扣费项目' => $reason
            ]);
            Log::channel('explosionVideoSynthesis')->write('refundGrabTokens:success ' . json_encode([
                'task_id' => $taskId,
                'type' => $type,
                'user_id' => $userId,
                'refund_points' => $pendingRefund,
                'reason' => $reason,
                'middle_refund' => $middleRefund,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            self::$returnData = [
                'task_id' => $taskId,
                'user_id' => $user->id,
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
}
