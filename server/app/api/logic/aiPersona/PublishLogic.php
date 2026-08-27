<?php


namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\model\wechat\AiWechatCircleTask;

use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;

use app\common\model\sv\SvVideoTask;
use app\common\model\sv\SvDeviceViralRecord;

use app\common\model\sv\SvDevice;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaCopywritingLibraryUseLog;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\aiPersona\SynthesisConfig as AiPersonaSynthesisConfig;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\aiPersona\IdRoundRobinPicker;
use app\common\service\auto\AutoTaskSceneConfigService;
use app\common\service\auto\AutoTaskSceneScheduleSyncService;
use app\common\service\sv\SvDeviceTaskExistenceService;
use think\facade\Cache;
use think\facade\Db;
use app\common\service\FileService;
use app\api\logic\sv\ToolsLogic;

/**
 * � 发布任务逻辑
 * Class PublishLogic    
 * @package app\api\logic\aiPersona
 */
class PublishLogic extends BasePersonaLogic
{
    private const SHANJIAN_PERSONA_PUBLISH_LOCK_TTL = 600;
    private const SHANJIAN_PERSONA_PUBLISH_STALE_SECONDS = 1800;
    private const SHANJIAN_PUBLISH_DEADLOCK_MAX_RETRIES = 3;

    private static function getContentPublishKeywords(AiPersona $persona, string $defaultKeywords, int $platform = AiPersona::PUBLISH_PLATFORM_XHS): string
    {
        $config = AiPersona::getPlatformContentPublishConfig($persona['content_publish_config'], $platform);
        if ((int)$config['generate_mode'] === AiPersona::CONTENT_GENERATE_MODE_AI && (int)$config['generate_basis'] === 2) {
            return $config['custom_direction'];
        }
        return $defaultKeywords;
    }

    private static function isCustomPublishContent(AiPersona $persona, int $platform = AiPersona::PUBLISH_PLATFORM_XHS): bool
    {
        $config = AiPersona::getPlatformContentPublishConfig($persona['content_publish_config'], $platform);
        return (int)$config['generate_mode'] === AiPersona::CONTENT_GENERATE_MODE_CUSTOM;
    }

    private static function getPublishContentByConfig(
        AiPersona $persona,
        string $defaultKeywords,
        string $taskId,
        int $userId,
        int $platform = AiPersona::PUBLISH_PLATFORM_XHS,
        bool $ignoreSynthesisConfig = false
    ): array {
        $config = AiPersona::getPlatformContentPublishConfig($persona['content_publish_config'], $platform);
        $logContext = [
            '场景' => '内容发布文案解析',
            'user_id' => $userId,
            'persona_id' => (int)$persona->id,
            'platform' => $platform,
            'task_id' => $taskId,
            '忽略合成规则文案库' => $ignoreSynthesisConfig ? 1 : 0,
            '内容发布配置' => [
                'generate_mode' => (int)$config['generate_mode'],
                'generate_mode_text' => self::contentGenerateModeText((int)$config['generate_mode']),
                'publish_copywriting_source' => (int)($config['publish_copywriting_source'] ?? 0),
                'generate_basis' => (int)($config['generate_basis'] ?? 1),
                'custom_direction' => (string)($config['custom_direction'] ?? ''),
                'library_use_mode' => (int)($config['library_use_mode'] ?? 0),
                'library_use_mode_text' => self::libraryUseModeText((int)($config['library_use_mode'] ?? 0)),
                'library_reuse_mode' => (int)($config['library_reuse_mode'] ?? 0),
                'library_reuse_mode_text' => self::libraryReuseModeText((int)($config['library_reuse_mode'] ?? 0)),
                'custom_copywriting' => $config['custom_copywriting'] ?? [],
            ],
        ];
        \think\facade\Log::channel('auto')->write(
            '发布文案配置：' . json_encode($logContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'create'
        );

        if ((int)$config['generate_mode'] === AiPersona::CONTENT_GENERATE_MODE_LIBRARY) {
            \think\facade\Log::channel('auto')->write(
                "发布文案选用：素材库引用 persona_id={$persona->id} platform={$platform} task_id={$taskId}",
                'create'
            );
            $libraryResult = self::getPublishContentFromLibrary($persona, $userId, $config, $platform);
            if (empty($libraryResult['data']['library_empty'])) {
                self::logPublishContentResult($logContext, '素材库命中', $libraryResult);
                return $libraryResult;
            }
            \think\facade\Log::channel('auto')->write(
                "发布文案库为空，回退AI生成 persona_id={$persona->id} platform={$platform} task_id={$taskId} msg="
                . (string)($libraryResult['data']['library_message'] ?? ''),
                'create'
            );
            $aiResult = self::getPublishContentByAi($persona, $defaultKeywords, $taskId, $userId, $platform);
            self::logPublishContentResult($logContext, '素材库空回退AI', $aiResult);
            return $aiResult;
        }

        if (!$ignoreSynthesisConfig) {
            $synthesisConfig = AiPersonaSynthesisConfig::where('user_id', $userId)
                ->where('persona_id', $persona->id)
                ->findOrEmpty();
            if (!$synthesisConfig->isEmpty()
                && (int)$synthesisConfig->copywriting_source === AiPersonaSynthesisConfig::COPYWRITING_SOURCE_LIBRARY
            ) {
                \think\facade\Log::channel('auto')->write(
                    "发布文案选用：AI合成规则文案库 persona_id={$persona->id} platform={$platform} task_id={$taskId} copywriting_source="
                    . (int)$synthesisConfig->copywriting_source,
                    'create'
                );
                $libraryResult = self::getPublishContentFromLibrary($persona, $userId, $synthesisConfig, $platform);
                if (empty($libraryResult['data']['library_empty'])) {
                    self::logPublishContentResult($logContext, '合成规则文案库命中', $libraryResult);
                    return $libraryResult;
                }
                \think\facade\Log::channel('auto')->write(
                    "合成规则文案库为空，回退AI生成 persona_id={$persona->id} platform={$platform} task_id={$taskId}",
                    'create'
                );
                $aiResult = self::getPublishContentByAi($persona, $defaultKeywords, $taskId, $userId, $platform);
                self::logPublishContentResult($logContext, '合成规则文案库空回退AI', $aiResult);
                return $aiResult;
            }
        } else {
            \think\facade\Log::channel('auto')->write(
                "发布文案跳过合成规则文案库回落 persona_id={$persona->id} platform={$platform} task_id={$taskId}",
                'create'
            );
        }

        if ((int)$config['generate_mode'] === AiPersona::CONTENT_GENERATE_MODE_CUSTOM) {
            \think\facade\Log::channel('auto')->write(
                "发布文案选用：自定义文案 persona_id={$persona->id} platform={$platform} task_id={$taskId}",
                'create'
            );
            $copywriting = $config['custom_copywriting'];
            $customResult = [
                'code' => 10000,
                'data' => [
                    'title' => $copywriting['title'],
                    'content' => $copywriting['content'],
                    'tag' => implode(',', $copywriting['topic_tags']),
                ],
            ];
            self::logPublishContentResult($logContext, '自定义文案', $customResult);
            return $customResult;
        }

        \think\facade\Log::channel('auto')->write(
            "发布文案选用：AI自动生成 persona_id={$persona->id} platform={$platform} task_id={$taskId}",
            'create'
        );
        $aiResult = self::getPublishContentByAi($persona, $defaultKeywords, $taskId, $userId, $platform);
        self::logPublishContentResult($logContext, 'AI自动生成', $aiResult);
        return $aiResult;
    }

    private static function contentGenerateModeText(int $mode): string
    {
        return match ($mode) {
            AiPersona::CONTENT_GENERATE_MODE_AI => '自动生成',
            AiPersona::CONTENT_GENERATE_MODE_CUSTOM => '自定义文案',
            AiPersona::CONTENT_GENERATE_MODE_LIBRARY => '素材库引用',
            default => '未知(' . $mode . ')',
        };
    }

    private static function libraryUseModeText(int $mode): string
    {
        return match ($mode) {
            AiPersona::CONTENT_LIBRARY_USE_MODE_RANDOM => '随机使用',
            AiPersona::CONTENT_LIBRARY_USE_MODE_SEQUENCE => '顺序使用',
            default => '未知(' . $mode . ')',
        };
    }

    private static function libraryReuseModeText(int $mode): string
    {
        return match ($mode) {
            AiPersona::CONTENT_LIBRARY_REUSE_MODE_ONCE => '每条只用一次',
            AiPersona::CONTENT_LIBRARY_REUSE_MODE_REPEAT => '可重复使用',
            default => '未知(' . $mode . ')',
        };
    }

    private static function logPublishContentResult(array $logContext, string $sourceText, array $response): void
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        \think\facade\Log::channel('auto')->write(
            '发布文案最终结果：' . json_encode([
                '场景' => $logContext['场景'] ?? '内容发布文案解析',
                'user_id' => $logContext['user_id'] ?? 0,
                'persona_id' => $logContext['persona_id'] ?? 0,
                'platform' => $logContext['platform'] ?? 0,
                'task_id' => $logContext['task_id'] ?? '',
                '选用来源' => $sourceText,
                'code' => (int)($response['code'] ?? 0),
                'library_item_id' => (int)($data['library_item_id'] ?? 0),
                'from_ai' => (int)($data['from_ai'] ?? 0),
                'library_empty' => (int)($data['library_empty'] ?? 0),
                '文案' => [
                    'title' => (string)($data['title'] ?? ''),
                    'content' => (string)($data['content'] ?? ''),
                    'tag' => (string)($data['tag'] ?? ''),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'create'
        );
    }

    /**
     * 按内容发布配置解析发布文案（可供爆款图文等外部链路调用）
     *
     * @param bool $ignoreSynthesisConfig true 时不回落 AI 合成规则文案库，只认 content_publish_config
     */
    public static function resolveContentPublishCopywriting(
        AiPersona $persona,
        string $defaultKeywords,
        string $taskId,
        int $userId,
        int $platform = AiPersona::PUBLISH_PLATFORM_XHS,
        bool $ignoreSynthesisConfig = false
    ): array {
        return self::getPublishContentByConfig(
            $persona,
            $defaultKeywords,
            $taskId,
            $userId,
            $platform,
            $ignoreSynthesisConfig
        );
    }

    private static function getPublishContentByAi(
        AiPersona $persona,
        string $defaultKeywords,
        string $taskId,
        int $userId,
        int $platform
    ): array {
        // 先校验算力，避免余额不足仍调用 AI；成功后再按同单价扣费
        $unit = \app\api\logic\service\TokenLogService::checkToken($userId, 'coze_publish_content_generated');

        $response = \app\common\service\ToolsService::Sv()->getPublishContent([
            'keywords' => self::getContentPublishKeywords($persona, $defaultKeywords, $platform),
            'task_id' => $taskId,
            'source' => 'shanjian2',
            'user_id' => $userId,
        ]);

        // 仅 AI 生成成功时扣费；文案库命中不走这里
        if ((int)($response['code'] ?? 0) === 10000) {
            self::execPublishContentDeduction($userId, $taskId, (float)$unit);
            $response['data'] = is_array($response['data'] ?? null) ? $response['data'] : [];
            $response['data']['from_ai'] = 1;
        }

        return $response;
    }

    /**
     * 扣减「COZE发布内容生成」算力（与闪剪/Sora 等发布链路同价）
     */
    private static function execPublishContentDeduction(int $userId, string $taskId, float $points): void
    {
        if ($points <= 0) {
            return;
        }
        $tokenCode = \app\common\enum\user\AccountLogEnum::TOKENS_DEC_COZE_PUBLISH_CONTENT_GENERATED;
        $extra = ['算力单价' => $points . '算力/条', '实际消耗算力' => $points, '场景' => '发布文案AI生成'];
        \app\common\model\user\User::userTokensChange($userId, $points);
        \app\common\logic\AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
    }

    private static function getPublishContentFromLibrary(AiPersona $persona, int $userId, mixed $config, int $platform): array
    {
        $library = CopywritingLibraryLogic::consumePublishCopywriting($userId, (int)$persona->id, $config, $platform);
        if (empty($library)) {
            return [
                'code' => 10000,
                'data' => [
                    'title' => '',
                    'content' => '',
                    'tag' => '',
                    'library_empty' => 1,
                    'library_message' => '发布文案库暂无可用文案',
                ],
            ];
        }

        return [
            'code' => 10000,
            'data' => [
                'title' => $library['title'] ?? '',
                'content' => $library['content'] ?? '',
                'tag' => $library['topic'] ?? '',
                'library_item_id' => (int)$library['id'],
            ],
        ];
    }

    private static function getCirclePublishContentByConfig(AiPersona $persona, string $defaultKeywords, int $userId): string
    {
        $config = AiPersona::getPlatformContentPublishConfig($persona['content_publish_config'], AiPersona::PUBLISH_PLATFORM_XHS);
        if ((int)$config['generate_mode'] === 2) {
            return $config['custom_copywriting']['content'];
        }

        $coze['keywords'] = self::getContentPublishKeywords($persona, $defaultKeywords, AiPersona::PUBLISH_PLATFORM_XHS);
        $coze['sn'] = 9;
        $coze['number'] = 1;
        $coze['length'] = 15;
        $copywritingResult = AutoDeviceSettingLogic::copywriting($coze, $userId, 6);
        $content = $copywritingResult['content'] ?? '';
        if (is_array($content)) {
            return (string)($content[0] ?? '');
        }
        return (string)$content;
    }

    public static function materialPersonaPublishCron(SvDevice $device)
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        \think\facade\Log::channel('auto')->write($device->device_code . '根据指定素材生成24h视频发布任务', 'create');
        try {
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                //throw new \Exception('设备' . $device->device_code . '没有绑定角色');
                \think\facade\Log::channel('auto')->write($device->device_code . '没有绑定角色', 'create');
                return $result;
            }
            if ((int)$persona->publish_mode !== 2) {
                \think\facade\Log::channel('auto')->write($device->device_code . '人设角色' . $persona->id . '不是指定素材发布模式', 'create');
                return $result;
            }

            //print_r($persona->toArray());die;
            $rule = null;
            if ($persona->persona_type == 1) {
                $rule = $persona->individual;
            } elseif ($persona->persona_type == 2) {
                $rule = $persona->enterprise;
            } elseif ($persona->persona_type == 3) {
                $rule = $persona->local;
            }
            $xhsPublishConfig = AiPersona::getPlatformContentPublishConfig($persona['content_publish_config'], AiPersona::PUBLISH_PLATFORM_XHS);
            $materialType = (int)$xhsPublishConfig['publish_media_type'] === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT ? 2 : 1;
            $materialTypeText = $materialType === 2 ? '图片' : '视频';
            $productConfig = self::getProductLibraryConfig((int)$device->persona_id);
            $materialResult = self::getAvailablePublishMaterials($device, $materialType, $productConfig);
            $videos = $materialResult['materials'];
            \think\facade\Log::channel('auto')->write(\think\facade\Db::getLastSql(), 'create');

            if (!empty($videos)) {
                $device->persona = $persona;
                $result = SvDeviceTaskExistenceService::mergeSlotResult(
                    $result,
                    self::runCreatePublishByMaterial($videos, $device, $rule, $materialType, $productConfig)
                );
            } else {
                \think\facade\Log::channel('auto')->write($device->device_code . '待使用指定' . $materialTypeText . '素材为空: ' . $device->persona_id, 'create');
            }

            if ($materialType === 2 && self::hasContentPublishMaterialType($persona, 1)) {
                $videoMaterialResult = self::getAvailablePublishMaterials($device, 1, $productConfig);
                if (!empty($videoMaterialResult['materials'])) {
                    $device->persona = $persona;
                    $result = SvDeviceTaskExistenceService::mergeSlotResult(
                        $result,
                        self::runCreatePublishByMaterial($videoMaterialResult['materials'], $device, $rule, 1, $productConfig)
                    );
                } else {
                    \think\facade\Log::channel('auto')->write($device->device_code . ' 待使用指定视频素材为空：' . $device->persona_id, 'create');
                }
            }

            return $result;
        } catch (\Throwable $th) {
            //throw $th;
            \think\facade\Log::channel('auto')->write('任务处理异常: ' . $th->__toString(), 'create');
            return $result;
        }
    }

    private static function hasContentPublishMaterialType(AiPersona $persona, int $materialType): bool
    {
        $schedules = self::getAutoSchedule($persona, 5);
        foreach ($schedules as $schedule) {
            foreach (self::normalizeShanjianPublishPlatforms($schedule->platform) as $platform) {
                if (self::getPlatformPublishMaterialType($persona, (int)$platform['account_type']) === $materialType) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function getPlatformPublishMaterialType(AiPersona $persona, int $platform): int
    {
        $config = AiPersona::getPlatformContentPublishConfig($persona['content_publish_config'], $platform);
        if ($platform === AiPersona::PUBLISH_PLATFORM_XHS
            && (int)$config['publish_media_type'] === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT
        ) {
            return 2;
        }

        return 1;
    }

    private static function getProductLibraryConfig(int $personaId): array
    {
        $config = AiPersonaSynthesisConfig::where('persona_id', $personaId)->order('id', 'desc')->findOrEmpty();
        $useMode = AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM;
        $reuseMode = AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE;
        if (!$config->isEmpty()) {
            $useMode = in_array((int)$config['product_use_mode'], [1, 2], true)
                ? (int)$config['product_use_mode']
                : AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM;
            $reuseMode = in_array((int)$config['product_reuse_mode'], [1, 2], true)
                ? (int)$config['product_reuse_mode']
                : AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE;
        }

        return [
            'product_use_mode' => $useMode,
            'product_reuse_mode' => $reuseMode,
        ];
    }

    private static function getUsedProductMaterialIds(int $userId, int $personaId): array
    {
        return MaterialUseLog::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('publish_mode', 2)
            ->where('use_scene', MaterialUseLog::USE_SCENE_CONTENT_PUBLISH)
            ->whereIn('use_status', [MaterialUseLog::USE_STATUS_USING, MaterialUseLog::USE_STATUS_SUCCESS])
            ->column('material_id');
    }

    private static function recordAndRemoveUsedMaterials(
        SvDevice $device,
        array $materialIds,
        int $productReuseMode,
        int $useScene = MaterialUseLog::USE_SCENE_CONTENT_PUBLISH,
        int $isWechat = 0,
        int $productUseMode = AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM
    ): void {
        $materialIds = array_values(array_unique(array_filter(
            array_map('intval', $materialIds),
            static fn (int $id): bool => $id > 0
        )));
        if (empty($materialIds)) {
            return;
        }

        $now = time();
        $materialUseLog = [];
        foreach ($materialIds as $materialId) {
            $materialUseLog[] = [
                'material_id' => $materialId,
                'user_id' => $device->user_id,
                'persona_id' => $device->persona_id,
                'is_wechat' => $isWechat,
                'task_id' => 0,
                'publish_mode' => Material::PUBLISH_MODE_DIRECT_SEND,
                'use_scene' => $useScene,
                'use_status' => MaterialUseLog::USE_STATUS_SUCCESS,
                'create_time' => $now,
                'update_time' => $now,
            ];
        }

        if (!empty($materialUseLog)) {
            MaterialUseLog::insertAll($materialUseLog);
        }

        // 顺序按 id 轮询可一直循环，不删素材；仅随机 + 只用一次才软删除
        $removed = false;
        if (
            $productUseMode === AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM
            && $productReuseMode === AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE
        ) {
            Material::destroy($materialIds);
            $removed = true;
        }

        \think\facade\Log::channel('auto')->write('指定素材使用后处理: ' . json_encode([
            'device_code' => $device->device_code,
            'persona_id' => $device->persona_id,
            'material_ids' => $materialIds,
            'product_use_mode' => $productUseMode,
            'product_reuse_mode' => $productReuseMode,
            'removed' => $removed,
        ], JSON_UNESCAPED_UNICODE), 'create');
    }

    private static function getAvailablePublishMaterials(SvDevice $device, int $materialType, array $productConfig = []): array
    {
        if (empty($productConfig)) {
            $productConfig = self::getProductLibraryConfig((int)$device->persona_id);
        }
        $useMode = (int)($productConfig['product_use_mode'] ?? AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM);
        $reuseMode = (int)($productConfig['product_reuse_mode'] ?? AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE);

        $query = Material::where('material_type', $materialType)
            ->where('user_id', $device->user_id)
            ->where('persona_id', $device->persona_id)
            ->where('use_status', 1)
            ->where('publish_mode', 2)
            ->where('is_wechat', 0);

        if ($useMode === AiPersonaSynthesisConfig::PRODUCT_USE_MODE_SEQUENCE) {
            // 顺序使用：按 id 升序固定队列，与随机规则无关；真正取用时按「上次 id 的下一条」轮询
            $query->order(['id' => 'asc']);
        } else {
            if ($reuseMode === AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE) {
                $usedIds = self::getUsedProductMaterialIds((int)$device->user_id, (int)$device->persona_id);
                if (!empty($usedIds)) {
                    $query->whereNotIn('id', array_unique(array_map('intval', $usedIds)));
                }
            }
            $query->order(['id' => 'desc']);
        }

        $materials = $query->select();
        $result = [];
        foreach ($materials as $item) {
            // 仅随机 + 可重复：同设备短时间使用超过2次后跳过
            if (
                $useMode === AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM
                && $reuseMode === AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_REPEAT
            ) {
                $rediskey = 'material_' . $item['id'] . '_device_' . $device->device_code;
                $deviceBindNum = \think\facade\Cache::store('redis')->get($rediskey);
                if (empty($deviceBindNum)) {
                    $deviceBindNum = 0;
                }
                if ($deviceBindNum > 2) {
                    continue;
                }
            }
            $result[] = $item;
        }

        return [
            'materials' => $result,
        ];
    }

    /**
     * 成品库内容发布：最近一次使用的素材 id（用作顺序轮询游标）
     * 按 material_type 隔离，避免视频/图片互相打断轮询进度。
     */
    private static function getLastUsedProductMaterialId(int $userId, int $personaId, int $materialType = 0): int
    {
        $query = MaterialUseLog::alias('l')
            ->where('l.user_id', $userId)
            ->where('l.persona_id', $personaId)
            ->where('l.publish_mode', Material::PUBLISH_MODE_DIRECT_SEND)
            ->where('l.use_scene', MaterialUseLog::USE_SCENE_CONTENT_PUBLISH)
            ->whereIn('l.use_status', [MaterialUseLog::USE_STATUS_USING, MaterialUseLog::USE_STATUS_SUCCESS]);

        if ($materialType > 0) {
            $materialTable = (new Material())->getTable();
            $query->join($materialTable . ' m', 'm.id = l.material_id')
                ->where('m.material_type', $materialType)
                ->where('m.persona_id', $personaId)
                ->where('m.user_id', $userId);
        }

        return (int)$query->order('l.id', 'desc')->value('l.material_id');
    }

    private static function pickMaterialsByUseMode(array $medias, int $needCount, int $useMode, int $lastUsedId = 0): array
    {
        $mediaCount = count($medias);
        if ($needCount <= 0 || $mediaCount <= 0) {
            return [];
        }

        if ($useMode === AiPersonaSynthesisConfig::PRODUCT_USE_MODE_SEQUENCE) {
            // 按 id 轮询：从「上次使用 id」的下一条开始取，到末尾后从头继续
            return IdRoundRobinPicker::pick(
                $medias,
                $lastUsedId,
                min($needCount, $mediaCount)
            );
        }

        if ($mediaCount <= $needCount) {
            return array_values($medias);
        }

        $index = array_rand($medias, $needCount);
        if (!is_array($index)) {
            $index = [$index];
        }
        return array_values(array_map(function ($key) use ($medias) {
            return $medias[$key];
        }, $index));
    }

    private static function runCreatePublishByMaterial(mixed $medias, SvDevice $device, mixed $rule, int $materialType = 1, array $productConfig = []): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        try {
            if (empty($productConfig)) {
                $productConfig = self::getProductLibraryConfig((int)$device->persona_id);
            }
            $useMode = (int)($productConfig['product_use_mode'] ?? AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM);
            $reuseMode = (int)($productConfig['product_reuse_mode'] ?? AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE);
            $schedules = self::getAutoSchedule($device->persona, 5);
            if ($medias instanceof \think\Collection) {
                $medias = $medias->toArray();
            }
            if (!is_array($medias)) {
                $medias = [];
            }
            $medias = array_values($medias);

            $scheduleCount = $schedules->count();
            $mediaCount = count($medias);

            if ($scheduleCount <= 0) {
                \think\facade\Log::channel('auto')->write($device->device_code . ' 内容发布时间段为空，跳过24h视频发布任务 persona_id=' . $device->persona_id . ' scene=5', 'create');
                return $result;
            }

            if ($mediaCount <= 0) {
                \think\facade\Log::channel('auto')->write($device->device_code . ' 可用视频素材为空，跳过24h视频发布任务 persona_id=' . $device->persona_id, 'create');
                return $result;
            }

            // 只给「内容发布规则需要该素材类型」的时段分配素材：
            // 同一时段多平台仍共用一条；小红书配置图文时走图片池，不会被纯视频时段占掉。
            $matchedSchedules = [];
            foreach ($schedules as $schedule) {
                $platforms = self::normalizeShanjianPublishPlatforms($schedule->platform);
                if (empty($platforms)) {
                    continue;
                }
                foreach ($platforms as $platform) {
                    if (self::getPlatformPublishMaterialType($device->persona, (int)$platform['account_type']) === $materialType) {
                        $matchedSchedules[] = $schedule;
                        break;
                    }
                }
            }
            $needCount = count($matchedSchedules);
            if ($needCount <= 0) {
                \think\facade\Log::channel('auto')->write(
                    $device->device_code . ' 无时段需要该素材类型，跳过发布 material_type=' . $materialType . ' persona_id=' . $device->persona_id,
                    'create'
                );
                return $result;
            }

            $date = date('Y-m-d', time());
            if ($useMode === AiPersonaSynthesisConfig::PRODUCT_USE_MODE_SEQUENCE) {
                $lastUsedId = self::getLastUsedProductMaterialId(
                    (int)$device->user_id,
                    (int)$device->persona_id,
                    $materialType
                );
                $medias = self::pickMaterialsByUseMode($medias, $needCount, $useMode, $lastUsedId);
            } elseif ($mediaCount > $needCount) {
                $medias = self::pickMaterialsByUseMode($medias, $needCount, $useMode);
            }


            foreach ($matchedSchedules as $sjKey => $schedule) {
                $window = self::resolveScheduleWindow($schedule);
                $st = strtotime($date . ' ' . $window['start_time'] . ':00');
                $et = strtotime($date . ' ' . $window['end_time'] . ':00');

                $platforms = self::normalizeShanjianPublishPlatforms($window['platform']);
                if (empty($platforms) || $st === false || $et === false || $et <= $st) {
                    \think\facade\Log::channel('auto')->write($device->device_code . ' 内容发布时间段平台配置为空，跳过该时段 persona_id=' . $device->persona_id . ' schedule_id=' . ($schedule->id ?? 0), 'create');
                    continue;
                }
                $interval = ($et - $st) / count($platforms);
                $sort = array_column($platforms, 'order');
                array_multisort($sort, SORT_ASC, $platforms);
                $execTime = $window['time_range'];
                if(!isset($medias[$sjKey])){
                    continue;
                }
                $media = $medias[$sjKey];
                $slotCreated = 0;
                foreach ($platforms as $index => $platform) {
                    if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                        DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
                        (int)($platform['account_type'] ?? 0),
                        (string)$device->device_code,
                        '视频发布任务'
                    )) {
                        continue;
                    }
                    $startTime = $st + $index * $interval;
                    $endTime = $startTime + $interval;
                    $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $device->user_id)->where('device_code', $device->device_code)->findOrEmpty();
                    if ($account->isEmpty()) {
                        continue;
                    }
                    if (self::getPlatformPublishMaterialType($device->persona, (int)$account['type']) !== $materialType) {
                        continue;
                    }
                    $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                    if ($endTime < time()) {
                        //\think\facade\Log::channel('auto')->write($device->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']已过期', 'create');
                        //continue;
                    }

                    if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                        (int)$device->user_id,
                        (string)$device->device_code,
                        (int)$device->persona_id,
                        DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
                        (int)$account['type'],
                        $startTime,
                        $endTime,
                        '素材发布任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $response = [
                        'code' => 10000,
                    ];
                    $status = 0;
                    $remark = '';
                    $task_id = generate_unique_task_id();
                    $publishTime = $startTime;
                    $libraryItemId = 0;
                    $response = self::getPublishContentByConfig(
                        $device->persona,
                        $rule->getClueContent($device->persona),
                        $task_id,
                        $device->user_id,
                        (int)$account['type']
                    );

                    if ((int)$response['code'] === 10000) {

                        $title = $response['data']['title'] ?? '';
                        $content = $response['data']['content'] ?? '';
                        $tag = $response['data']['tag'] ?? '';
                        $libraryItemId = (int)($response['data']['library_item_id'] ?? 0);
                        if (!empty($response['data']['library_empty'])) {
                            $status = 2;
                            $remark = $response['data']['library_message'] ?? '发布文案库暂无可用文案';
                        }
                        $mediaId = (int)(is_array($media) ? ($media['id'] ?? 0) : ($media->id ?? 0));
                        $mediaFileUrl = (string)(is_array($media) ? ($media['file_url'] ?? '') : ($media->file_url ?? ''));
                        $mediaThumbnailUrl = (string)(is_array($media) ? ($media['thumbnail_url'] ?? '') : ($media->thumbnail_url ?? ''));
                        $mediaVideoSettingId = (int)(is_array($media) ? ($media['video_setting_id'] ?? 0) : ($media->video_setting_id ?? 0));
                        $mediaType = $materialType === 2 ? 2 : 1;
                        $publishTaskName = $mediaType === 2 ? '自动化图文发布任务' : '自动化视频发布任务';
                        $material_url = $mediaFileUrl != '' ? FileService::getFileUrl($mediaFileUrl) : '';
                        $pic = $mediaType === 2 ? $material_url : FileService::getFileUrl($mediaThumbnailUrl);

                        usleep(500000);
                        try {
                            $exist = SvPublishSettingDetail::where('user_id', $device->user_id)
                                ->where('device_code', $device->device_code)
                                ->where('auto_type', 1)
                                ->where('account_type', $account['type'])
                                ->where('task_type',  99)
                                ->where('persona_id', $device->persona_id)
                                ->where('publish_time', date('Y-m-d H:i:s', $publishTime))
                                ->findOrEmpty();
                            if (!$exist->isEmpty()) {
                                continue;
                            }

                            $setting = SvPublishSetting::create([
                                'user_id' => $device->user_id,
                                'task_type' => 99,
                                'name' => $publishTaskName . date('YmdHsi', time()),
                                'accounts' => json_encode([$account], JSON_UNESCAPED_UNICODE),
                                'auto_type' => 1,
                                'video_setting_id' => 0,
                                'matrix_media_setting_id' => 0,
                                'video_ids' => json_encode([$mediaId], JSON_UNESCAPED_UNICODE),
                                'scene' => 2,
                                'type' => 0,
                                'media_type' => $mediaType,
                                'publish_start' => date('Y-m-d', $publishTime),
                                'publish_end' => date('Y-m-d', $publishTime),
                                'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                                'data_type' => 0,
                                'date_type' => 1,
                                'publish_frep' => 1,
                                'persona_id' => $device->persona_id,
                                'status' => 3,
                                'create_time' => time()
                            ]);

                            $paccount =  SvPublishSettingAccount::create([
                                'publish_id' => $setting->id,
                                'task_type' => 99,
                                'user_id' => $device->user_id,
                                'name' => $publishTaskName . date('YmdHsi', time()),
                                'account' => $account['account'],
                                'account_type' => $account['type'],
                                'nickname' => $account['nickname'],
                                'avatar' => $account['avatar'],
                                'auto_type' => 1,
                                'device_code' => $device->device_code,
                                'media_type' => $mediaType,
                                'video_setting_id' => 0,
                                'video_ids' => json_encode([$mediaId], JSON_UNESCAPED_UNICODE),
                                'matrix_media_setting_id' => 0,
                                'scene' => 2,
                                'status' => 2,
                                'task_status' => 2,
                                'publish_start' => date('Y-m-d', $publishTime),
                                'publish_end' => date('Y-m-d', $publishTime),
                                'next_publish_time' => date('Y-m-d H:i:s', $publishTime),
                                'count' => 1,
                                'published_count' => 0,
                                'persona_id' => $device->persona_id,
                                'data_type' => 0,
                                'create_time' => time()
                            ]);

                            $detail = SvPublishSettingDetail::create([
                                'publish_id' => $setting->id,
                                'publish_account_id' => $paccount->id,
                                'task_type' => 99,
                                'video_task_id' => $mediaType === 2 ? 0 : $mediaId,
                                'video_setting_id' => $mediaVideoSettingId,
                                'user_id' => $device->user_id,
                                'account' => $account['account'],
                                'account_type' => $account['type'],
                                'nickname' => $account['nickname'],
                                'avatar' => $account['avatar'],
                                'auto_type' => 1,
                                'device_code' => $device->device_code,
                                'matrix_media_setting_id' => 0,
                                'material_id' => $mediaId,
                                'material_url' => $material_url,
                                'material_title' => $title,
                                'material_subtitle' => $content,
                                'material_type' => $mediaType,
                                'material_tag' => $tag,
                                'pic' => $pic,
                                'poi' => '',
                                'data_type' => 0,
                                'task_id' => $task_id,
                                'sub_task_id' => time() . ($index + 100),
                                'scene' => 2,
                                'platform' => $account['type'],
                                'status' => $status,
                                'remark' => $remark,
                                'persona_id' => $device->persona_id,
                                'publish_time' => date('Y-m-d H:i:s', $publishTime),
                                'create_time' => time()
                            ]);
                            //$detail->refresh();

                            if ($libraryItemId > 0) {
                                CopywritingLibraryLogic::recordUse(
                                    $libraryItemId,
                                    AiPersonaCopywritingLibraryUseLog::SCENE_PUBLISH,
                                    [
                                        'related_publish_detail_id' => (int)$detail->id,
                                        'related_video_task_id' => $mediaType === 2 ? 0 : $mediaId,
                                        'platform' => (int)$account['type'],
                                        'task_id' => $task_id,
                                    ]
                                );
                            }

                            \app\common\model\sv\SvDeviceTask::create([
                                'user_id' => $device->user_id,
                                'device_code' => $device->device_code,
                                'task_type' => DeviceEnum::AUTO_TYPE_PUBLISH,
                                'account' => $account['account'],
                                'account_type' => $account['type'],
                                'nickname' => $account['nickname'],
                                'avatar' => $account['avatar'],
                                'auto_type' => 1,
                                'task_name' => $publishTaskName . date('YmdHsi', time()),
                                'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'day' => date('Y-m-d', $publishTime),
                                'status' => $status === 2 ? 3 : 0,
                                'remark' => $remark,
                                'sub_task_id' => $paccount->id,
                                'sub_data_id' => $detail->id,
                                'persona_id' => $device->persona_id,
                                'task_scene' => DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
                                'source' => DeviceEnum::TASK_SOURCE_PUBLISH,
                                'create_time' => time(),
                            ]);
                            $result['created']++;
                            $slotCreated++;
                        } catch (\Throwable $th) {
                            \think\facade\Log::channel('auto')->write('24小时视频发布任务异常：' . $th->__toString(), 'create');
                            continue;
                        }
                    } else {

                        \think\facade\Log::channel('auto')->write('24小时视频文案异常：' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'create');
                    }
                }

                if ($slotCreated > 0) {
                    $mediaId = (int)(is_array($media) ? ($media['id'] ?? 0) : ($media->id ?? 0));
                    if (
                        $useMode === AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM
                        && $reuseMode === AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_REPEAT
                    ) {
                        $rediskey = 'material_' . $mediaId . '_device_' . $device->device_code;
                        \think\facade\Cache::store('redis')->inc($rediskey);
                    }
                    self::recordAndRemoveUsedMaterials($device, [$mediaId], $reuseMode, MaterialUseLog::USE_SCENE_CONTENT_PUBLISH, 0, $useMode);
                }
            }

            return $result;
        } catch (\Throwable $th) {

            //$handler->del($RUNNING_KEY);
            \think\facade\Log::channel('auto')->write('根据素材生成24h视频发布任务异常：' . $th->__toString(), 'create');
            return $result;
        }
    }



    /**
     * 朋友圈指定素材视频发布任务
     *
     * @param SvDevice $device
     */
    public static function materialCirclePersonaPublishCron(SvDevice $device)
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        $msg = "微信朋友圈指定素材发布" . $device->device_code . '根据指定素材生成24h视频发布任务';
        \think\facade\Log::channel('auto')->write($msg, 'create');
        try {
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                //throw new \Exception('设备' . $device->device_code . '没有绑定角色');
                $msg = "微信朋友圈指定素材发布" . $device->device_code . '没有绑定角色';
                \think\facade\Log::channel('auto')->write($msg, 'create');
                return $result;
            }
            
            if ($persona->wechat_publish_mode != 2) {
                $msg = "微信朋友圈指定素材发布" . $device->device_code . '人设角色' . $persona->persona_id . '不是朋友圈发布模式';
                \think\facade\Log::channel('auto')->write($msg, 'create');
                return $result;
            }
            //print_r($persona->toArray());die;
            $rule = null;
            if ($persona->persona_type == 1) {
                $rule = $persona->individual;
            } elseif ($persona->persona_type == 2) {
                $rule = $persona->enterprise;
            } elseif ($persona->persona_type == 3) {
                $rule = $persona->local;
            }

            // attachment_type：1=图片 2=短视频；优先与上次不同类型，缺料则回退
            $attachment_type = (int)(AiWechatCircleTask::where('device_code', $device->device_code)
                ->where('persona_id', $device->persona_id)
                ->order('id', 'desc')
                ->value('attachment_type') ?? 1);
            $resolved = self::resolveCirclePublishMaterials($device, $attachment_type);
            $material_type = (int)$resolved['material_type'];
            $materials = $resolved['materials'];

            if (empty($materials)) {
                $msg = "微信朋友圈指定素材发布" . $device->device_code
                    . '人设角色' . $persona->id . '视频与图片均无可用微信直发素材';
                \think\facade\Log::channel('auto')->write($msg, 'create');
                return $result;
            }

            if (!empty($resolved['fallback'])) {
                $preferType = self::resolveCircleMaterialPreference($attachment_type);
                $msg = "微信朋友圈指定素材发布" . $device->device_code
                    . '人设角色' . $persona->id
                    . '优先类型[' . self::circleMaterialTypeText($preferType) . ']无可用素材，'
                    . '回退为[' . self::circleMaterialTypeText($material_type) . ']共'
                    . count($materials) . '条';
                \think\facade\Log::channel('auto')->write($msg, 'create');
            }

            $result = SvDeviceTaskExistenceService::mergeSlotResult(
                $result,
                self::runCircleCreatePublishByMaterial($materials, $device, $persona, $rule, $material_type)
            );

            return $result;
        } catch (\Throwable $th) {
            //throw $th;
            \think\facade\Log::channel('auto')->write('任务处理异常: ' . $th->__toString(), 'create');
            return $result;
        }
    }

    /**
     * 根据上一条朋友圈附件类型，计算本轮优先素材类型（图/视频交替）
     * attachment_type：1=图片 2=短视频 3=长视频；material_type：1=视频 2=图片
     */
    private static function resolveCircleMaterialPreference(int $lastAttachmentType): int
    {
        return in_array($lastAttachmentType, [2, 3], true)
            ? Material::MATERIAL_TYPE_IMAGE
            : Material::MATERIAL_TYPE_VIDEO;
    }

    private static function circleMaterialTypeText(int $materialType): string
    {
        return $materialType === Material::MATERIAL_TYPE_VIDEO ? '视频' : '图片';
    }

    /**
     * 查询朋友圈直发可用素材
     *
     * @return array<int, array>
     */
    private static function queryCircleDirectMaterials(SvDevice $device, int $materialType): array
    {
        $materials = Material::where('material_type', $materialType)
            ->field('id,file_url')
            ->where('user_id', $device->user_id)
            ->where('persona_id', $device->persona_id)
            ->where('use_status', Material::USE_STATUS_ENABLED)
            ->where('publish_mode', Material::PUBLISH_MODE_DIRECT_SEND)
            ->where('is_wechat', 1)
            ->select()
            ->toArray();
        \think\facade\Log::channel('auto')->write(\think\facade\Db::getLastSql(), 'create');
        return $materials;
    }

    /**
     * 解析朋友圈发布素材：优先交替类型，缺料回退另一类型
     *
     * @return array{material_type:int,materials:array,fallback:bool}
     */
    private static function resolveCirclePublishMaterials(SvDevice $device, int $lastAttachmentType): array
    {
        $preferType = self::resolveCircleMaterialPreference($lastAttachmentType);
        $fallbackType = $preferType === Material::MATERIAL_TYPE_VIDEO
            ? Material::MATERIAL_TYPE_IMAGE
            : Material::MATERIAL_TYPE_VIDEO;

        $materials = self::queryCircleDirectMaterials($device, $preferType);
        if (!empty($materials)) {
            return [
                'material_type' => $preferType,
                'materials' => $materials,
                'fallback' => false,
            ];
        }

        $fallbackMaterials = self::queryCircleDirectMaterials($device, $fallbackType);
        if (!empty($fallbackMaterials)) {
            return [
                'material_type' => $fallbackType,
                'materials' => $fallbackMaterials,
                'fallback' => true,
            ];
        }

        return [
            'material_type' => $preferType,
            'materials' => [],
            'fallback' => false,
        ];
    }

    private static function runCircleCreatePublishByMaterial(mixed $medias, SvDevice $device, AiPersona $persona, mixed $rule, int $material_type): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();

        try {
            $attachment_type = $material_type == 1 ? 2 : 1;
            $schedules = self::getAutoSchedule($persona, 7);
            if ($medias instanceof \think\Collection) {
                $medias = $medias->toArray();
            }
            if (!is_array($medias)) {
                $medias = [];
            }
            $medias = array_values($medias);
            $scheduleCount = $schedules->count();
            $mediaCount = count($medias);

            $date = date('Y-m-d', time());
            \think\facade\Log::channel('auto')->write('发布时段：' . json_encode($schedules->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'create');
            \think\facade\Log::channel('auto')->write('附件类型：' . $attachment_type, 'create');
            \think\facade\Log::channel('auto')->write(json_encode($medias, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'create');
            if ($scheduleCount <= 0) {
                \think\facade\Log::channel('auto')->write($device->device_code . ' 朋友圈发布时间段为空，跳过素材发布任务 persona_id=' . $device->persona_id . ' scene=7', 'create');
                return $result;
            }
            if ($mediaCount <= 0) {
                \think\facade\Log::channel('auto')->write($device->device_code . ' 朋友圈可用素材为空，跳过素材发布任务 persona_id=' . $device->persona_id, 'create');
                return $result;
            }
            if ($attachment_type == 2) {
                if ($mediaCount > $scheduleCount) {
                    $index = array_rand($medias, $scheduleCount);
                    if(!is_array($index)){
                        $index = [$index];
                    }
                    $medias = array_values(array_map(function ($key) use ($medias) {
                        return $medias[$key];
                    }, $index));
                }
            }
            //print_r($medias);die;
            \think\facade\Log::channel('auto')->write('附件类型：' . $attachment_type, 'create');
            \think\facade\Log::channel('auto')->write(json_encode($medias, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'create');
            foreach ($schedules as $mediaIndex => $schedule) {
                $window = self::resolveScheduleWindow($schedule);
                $st = strtotime($date . ' ' . $window['start_time'] . ':00');
                $et = strtotime($date . ' ' . $window['end_time'] . ':00');

                $platforms = $window['platform'];
                if (is_string($platforms)) {
                    $platforms = json_decode($platforms, true) ?: [];
                }
                if (!is_array($platforms)) {
                    $platforms = [];
                }
                $platforms = array_values(array_filter(array_map(static function ($platform) {
                    return is_object($platform) ? (array)$platform : $platform;
                }, $platforms), static function ($platform) {
                    return is_array($platform) && !empty($platform['account_type']);
                }));
                if (empty($platforms) || $st === false || $et === false || $et <= $st) {
                    \think\facade\Log::channel('auto')->write($device->device_code . ' 朋友圈发布时间段平台配置为空，跳过该时段 persona_id=' . $device->persona_id . ' schedule_id=' . ($schedule->id ?? 0), 'create');
                    continue;
                }
                $interval = ($et - $st) / count($platforms);
                $sort = array_column($platforms, 'order');
                array_multisort($sort, SORT_ASC, $platforms);
                $execTime = $window['time_range'];
                $media = [];
                if ($attachment_type == 2) {
                    if(!isset($medias[$mediaIndex])){
                        continue;
                    }
                    $media = $medias[$mediaIndex];
                } else {
                    
                    if ($mediaCount > 0) {
                        $count = min(rand(1, 9), $mediaCount);
                        $randomKeys = array_rand($medias, $count);
                        if (!is_array($randomKeys)) {
                            $randomKeys = [$randomKeys];
                        }
                        $image = [];
                        foreach ($randomKeys as $key) {
                            $image[] = $medias[$key];
                        }
                        $media[] = $image;
                    }
                    $media = isset($media[0]) ? $media[0] : $media;
                    \think\facade\Log::channel('auto')->write(json_encode($media, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'create');
                }
                
                $material_ids = [];
                foreach ($platforms as $index => $platform) {
                    if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                        DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH,
                        (int)($platform['account_type'] ?? 0),
                        (string)$device->device_code,
                        '朋友圈发布任务'
                    )) {
                        continue;
                    }
                    $startTime = $st + $index * $interval;
                    $endTime = $startTime + $interval;
                    $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $device->user_id)->where('device_code', $device->device_code)->findOrEmpty();
                    if ($account->isEmpty()) {
                        continue;
                    }
                    $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                    if ($endTime < time()) {
                        //\think\facade\Log::channel('auto')->write($device->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']已过期', 'create');
                        //continue;
                    }

                    if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                        (int)$device->user_id,
                        (string)$device->device_code,
                        (int)$device->persona_id,
                        DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH,
                        (int)$account['type'],
                        $startTime,
                        $endTime,
                        '朋友圈素材发布任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $publishTime = $startTime;
                    $response = [
                        'code' => 10000,
                    ];
                    $status = 0;
                    $remark = '';
                    $content = self::getCirclePublishContentByConfig(
                        $persona,
                        VideoSynthesis::buildCozeKeywords($device->device_code, $device->user_id, $device->persona_id, $device->persona_type),
                        $device->user_id
                    );
                    $attachment_content = [];
                    if (!empty($content) || self::isCustomPublishContent($persona)) {
                        $title = $response['data']['title'] ?? '';

                        if ($attachment_type == 1) {
                            $material_url = [];
                            \think\facade\Log::channel('auto')->write('素材地址：' . json_encode($media, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'create');
                            foreach ($media as $v) {
                                $material_url[] = $v['file_url'] != '' ? FileService::getFileUrl($v['file_url']) : '';
                                $material_ids[] = $v['id'];
                            }
                            $media = [];
                            $attachment_content = $material_url;
                        } elseif ($attachment_type == 2) {
                            $material_url = $media['file_url'] != '' ? FileService::getFileUrl($media['file_url']) : '';
                            $material_ids[] = $media['id'];
                            $attachment_content = [$material_url];
                        }
                        usleep(500000);
                        try {
                            $exist = AiWechatCircleTask::where('user_id', $device->user_id)
                                ->where('device_code', $device->device_code)
                                ->where('auto_type', 1)
                                ->where('task_type',  1)
                                ->where('wechat_id', $account['account'])
                                ->where('persona_id', $device->persona_id)
                                ->where('send_time', date('Y-m-d H:i:s', $publishTime))
                                ->findOrEmpty();
                            if (!$exist->isEmpty()) {
                                continue;
                            }
                            $taskConfig = AiWechatCircleTaskConfig::create([
                                'user_id' => $device->user_id,
                                'task_name' => '自动化朋友圈发布任务' . date('YmdHi', time()),
                                'content' => $content,
                                'attachment_type' => $attachment_type,
                                'attachment_content' => $attachment_content,
                                'wechat_ids' => [$account['account']],
                                'auto_type' => 1,
                                'status' => $status === 2 ? 3 : 1,
                                'date' => date('Y-m-d'),
                                'persona_id' => $device->persona_id,
                                'time_config' => $execTime,
                                'create_time' => time(),
                                'update_time' => time(),
                            ]);

                            $circleTask = AiWechatCircleTask::create([
                                'user_id' => $device->user_id,
                                'task_name' => '自动化朋友圈发布任务' . date('YmdHi', time()),
                                'task_config_id' => $taskConfig->id,
                                'device_code' => $device->device_code,
                                'wechat_id' => $account['account'],
                                'task_id' => time() . rand(100, 999),
                                'task_type' => 1,
                                'auto_type' => 1,
                                'content' => $content,
                                'attachment_type' => $attachment_type,
                                'attachment_content' => $attachment_content,
                                'send_time' => date('Y-m-d H:i:s', $publishTime),
                                'send_status' => $status === 2 ? 3 : 0,
                                'persona_id' => $device->persona_id,
                                'create_time' => time()
                            ]);
                            \app\common\model\sv\SvDeviceTask::create([
                                'user_id' => $device->user_id,
                                'device_code' => $device->device_code,
                                'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE,
                                'account' => $account['account'],
                                'account_type' => 1,
                                'nickname' => $account['nickname'],
                                'avatar' => $account['avatar'],
                                'task_name' => '自动化朋友圈发布任务' . date('YmdHi', time()),
                                'auto_type' => 1,
                                'day' => date('Y-m-d', $publishTime),
                                'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'sub_task_id' => $taskConfig->id,
                                'sub_data_id' => $circleTask->id,
                                'status' => $status === 2 ? 3 : 0,
                                'remark' => $remark,
                                'persona_id' => $device->persona_id,
                                'task_scene' => DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH,
                                'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH,
                                'create_time' => time(),
                            ]);
                            $result['created']++;
                        } catch (\Throwable $th) {
                            $msg = "微信朋友圈指定素材发布" . $device->device_code . '根据指定素材生成24h视频发布任务异常：' . $th->__toString();
                            \think\facade\Log::channel('auto')->write($msg, 'create');
                            continue;
                        }
                    } else {
                        $msg = "微信朋友圈指定素材发布" . $device->device_code . '根据指定素材生成24h视频发布任务异常：' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        \think\facade\Log::channel('auto')->write($msg, 'create');
                    }
                }

                $material_use_log = [];
                foreach ($material_ids as $item) {
                    $material_use_log[] = [
                        'material_id' => $item,
                        'user_id' => $device->user_id,
                        'persona_id' => $device->persona_id,
                        'is_wechat' => 1,
                        'task_id' => 0,
                        'publish_mode' => 2,
                        'use_scene' => 1,
                        'use_status' => 0,
                        'create_time' => time(),
                        'update_time' => time()
                    ];
                }
                MaterialUseLog::insertAll($material_use_log);
                Material::destroy($material_ids);
            }

            return $result;
        } catch (\Throwable $th) {

            //$handler->del($RUNNING_KEY);
            $msg = "微信朋友圈指定素材发布" . $device->device_code . '根据指定素材生成24h视频发布任务异常：' . $th->__toString();
            \think\facade\Log::channel('auto')->write($msg, 'create');
            return $result;
        }
    }





    /**
     * 用图文仿写记录生成待发布明细。
     * 支持跨天库存：时段与发布日取 $targetPublishDay（默认今天），不要求 record.day 等于今天。
     */
    public static function createImageTextPublishFromViralRecord(
        SvDeviceViralRecord $record,
        ?string $targetPublishDay = null
    ): bool
    {
        if ((int)$record->publish_detail_id > 0) {
            return true;
        }
        if ((int)$record->publish_media_type !== AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT
            || (int)$record->image_rewrite_status !== SvDeviceViralRecord::IMAGE_REWRITE_STATUS_SUCCESS
        ) {
            return false;
        }

        $targetPublishDay = self::normalizeTargetPublishDay($targetPublishDay);

        $transStarted = false;
        try {
            Db::startTrans();
            $transStarted = true;
            $record = SvDeviceViralRecord::where('id', (int)$record->id)->lock(true)->findOrEmpty();
            if ($record->isEmpty()) {
                throw new \Exception('爆款图文记录不存在');
            }
            if ((int)$record->publish_detail_id > 0) {
                Db::commit();
                $transStarted = false;
                return true;
            }
            if ((int)$record->use_time > 0) {
                throw new \Exception('该图文仿写记录已使用');
            }

            $persona = AiPersona::where('id', (int)$record->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在');
            }
            if (!AiPersonaOptionService::isEnabledForPersonaId((int)$persona->id, 'content_publish')) {
                throw new \Exception('内容发布未启用');
            }

            $device = SvDevice::where('device_code', (string)$record->device_code)
                ->where('user_id', (int)$record->user_id)
                ->where('persona_id', (int)$record->persona_id)
                ->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备绑定不存在');
            }

            $images = self::normalizeRecordImageUrls($record->rewritten_images);
            if (empty($images)) {
                throw new \Exception('改写图片为空');
            }

            $platform = (int)($record->publish_platform ?: AiPersona::PUBLISH_PLATFORM_XHS);
            if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
                $platform,
                (string)$device->device_code,
                '视频发布任务'
            )) {
                throw new \Exception('当前平台视频发布暂未开放');
            }
            $platformConfig = AiPersona::getPlatformContentPublishConfig($persona['content_publish_config'], $platform);
            if ((int)$platformConfig['publish_media_type'] !== AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT) {
                throw new \Exception('当前平台发布内容类型不是图文');
            }
            $slot = self::getImageTextPublishSlot($device, $persona, $targetPublishDay, $platform);
            if (empty($slot)) {
                throw new \Exception('无可用发布时段或账号');
            }

            $copywriting = self::normalizeViralRecordCopywriting($record->copywriting);
            $account = $slot['account'];
            $publishTime = $slot['publish_time'];
            $taskId = generate_unique_task_id();
            $materialUrl = implode(',', array_map(static function ($image) {
                return FileService::getFileUrl($image);
            }, $images));
            $pic = FileService::getFileUrl($images[0] ?? '');
            $now = time();

            $setting = SvPublishSetting::create([
                'user_id' => $record->user_id,
                'task_type' => 99,
                'name' => '自动化图文发布任务' . date('YmdHsi', $now),
                'accounts' => json_encode([$account], JSON_UNESCAPED_UNICODE),
                'auto_type' => 1,
                'video_setting_id' => 0,
                'matrix_media_setting_id' => 0,
                'video_ids' => json_encode([$record->id], JSON_UNESCAPED_UNICODE),
                'scene' => 1,
                'type' => 0,
                'media_type' => 2,
                'publish_start' => date('Y-m-d', $publishTime),
                'publish_end' => date('Y-m-d', $publishTime),
                'time_config' => json_encode([$slot['exec_time']], JSON_UNESCAPED_UNICODE),
                'data_type' => 0,
                'date_type' => 1,
                'publish_frep' => 1,
                'persona_id' => $record->persona_id,
                'status' => 3,
                'create_time' => $now,
            ]);

            $paccount = SvPublishSettingAccount::create([
                'publish_id' => $setting->id,
                'task_type' => 99,
                'user_id' => $record->user_id,
                'name' => '自动化图文发布任务' . date('YmdHsi', $now),
                'account' => $account['account'],
                'account_type' => $account['type'],
                'nickname' => $account['nickname'],
                'avatar' => $account['avatar'],
                'auto_type' => 1,
                'device_code' => $record->device_code,
                'media_type' => 2,
                'video_setting_id' => 0,
                'video_ids' => json_encode([$record->id], JSON_UNESCAPED_UNICODE),
                'matrix_media_setting_id' => 0,
                'scene' => 1,
                'status' => 2,
                'task_status' => 2,
                'publish_start' => date('Y-m-d', $publishTime),
                'publish_end' => date('Y-m-d', $publishTime),
                'next_publish_time' => date('Y-m-d H:i:s', $publishTime),
                'count' => 1,
                'published_count' => 0,
                'persona_id' => $record->persona_id,
                'data_type' => 0,
                'create_time' => $now,
            ]);

            $detail = SvPublishSettingDetail::create([
                'publish_id' => $setting->id,
                'publish_account_id' => $paccount->id,
                'task_type' => 99,
                'video_task_id' => 0,
                'video_setting_id' => 0,
                'user_id' => $record->user_id,
                'account' => $account['account'],
                'account_type' => $account['type'],
                'nickname' => $account['nickname'],
                'avatar' => $account['avatar'],
                'auto_type' => 1,
                'device_code' => $record->device_code,
                'matrix_media_setting_id' => 0,
                'material_id' => 0,
                'material_url' => $materialUrl,
                'material_title' => $copywriting['title'],
                'material_subtitle' => $copywriting['content'],
                'material_type' => 2,
                'material_tag' => $copywriting['tag'],
                'pic' => $pic,
                'poi' => '',
                'data_type' => 0,
                'task_id' => $taskId,
                'sub_task_id' => $now . ($slot['index'] + 100),
                'scene' => 1,
                'platform' => $account['type'],
                'status' => 0,
                'remark' => '',
                'persona_id' => $record->persona_id,
                'publish_time' => date('Y-m-d H:i:s', $publishTime),
                'create_time' => $now,
            ]);

            \app\common\model\sv\SvDeviceTask::create([
                'user_id' => $record->user_id,
                'device_code' => $record->device_code,
                'task_type' => DeviceEnum::AUTO_TYPE_PUBLISH,
                'account' => $account['account'],
                'account_type' => $account['type'],
                'nickname' => $account['nickname'],
                'avatar' => $account['avatar'],
                'auto_type' => 1,
                'task_name' => '自动化图文发布任务' . date('YmdHsi', $now),
                'time_config' => json_encode([$slot['exec_time']], JSON_UNESCAPED_UNICODE),
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'day' => date('Y-m-d', $publishTime),
                'status' => 0,
                'remark' => '',
                'sub_task_id' => $paccount->id,
                'sub_data_id' => $detail->id,
                'persona_id' => $record->persona_id,
                'task_scene' => DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
                'source' => DeviceEnum::TASK_SOURCE_PUBLISH,
                'create_time' => $now,
            ]);

            AiPersonaSynthesisCopywriting::where('sv_device_viral_record_id', (int)$record->id)
                ->where('publish_media_type', AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT)
                ->where('use_state', '<>', AiPersonaSynthesisCopywriting::USE_STATE_USED)
                ->update([
                    'use_state' => AiPersonaSynthesisCopywriting::USE_STATE_USED,
                    'update_time' => $now,
                ]);

            $record->publish_detail_id = $detail->id;
            $record->publish_create_error = '图文待发布任务生成成功';
            $record->update_time = $now;
            $record->use_time = time();
            $record->save();
            Db::commit();
            $transStarted = false;
            return true;
        } catch (\Throwable $th) {
            if ($transStarted) {
                Db::rollback();
            }
            //$record->status = 5;
            $record->publish_create_error = '图文待发布任务生成失败：' . self::limitPublishCreateError($th->getMessage());
            $record->update_time = time();
            $record->save();
            \think\facade\Log::channel('auto')->write('图文待发布任务生成失败：' . $th->getMessage() . "，行号：" . $th->getLine() . "，文件：" . $th->getFile(), 'create');
            return false;
        }
    }

    private static function limitPublishCreateError(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return '未知错误';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($message, 0, 500);
        }

        return substr($message, 0, 500);
    }

    public static function normalizeTargetPublishDay(?string $targetPublishDay): string
    {
        $day = trim((string)$targetPublishDay);
        if ($day === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            return date('Y-m-d');
        }
        return $day;
    }

    /**
     * 发布排期有效窗：剥离已关平台后按每平台 10 分钟锁定结束时间。
     *
     * @param object|array $schedule
     * @return array{scene:int,start_time:string,end_time:string,time_range:string,platform:array}
     */
    private static function resolveScheduleWindow($schedule): array
    {
        if (is_object($schedule)) {
            $schedule = [
                'scene' => (int)($schedule->scene ?? DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH),
                'start_time' => (string)($schedule->start_time ?? ''),
                'end_time' => (string)($schedule->end_time ?? ''),
                'platform' => $schedule->platform ?? [],
            ];
        }
        if (!is_array($schedule)) {
            $schedule = [];
        }
        if (!isset($schedule['scene']) || (int)$schedule['scene'] <= 0) {
            $schedule['scene'] = DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH;
        }
        return AutoTaskSceneScheduleSyncService::resolveEffectiveWindow($schedule);
    }

    /**
     * 是否存在指定日的空闲图文发布时段（供填坑服务预检，避免无坑时污染库存 remark）。
     */
    public static function hasAvailableImageTextPublishSlot(
        SvDevice $device,
        AiPersona $persona,
        string $day,
        int $platform
    ): bool {
        return !empty(self::getImageTextPublishSlot($device, $persona, $day, $platform));
    }

    private static function getImageTextPublishSlot(SvDevice $device, AiPersona $persona, string $day, int $platform): array
    {
        $schedules = self::getAutoSchedule($persona, 5);
        foreach ($schedules as $schedule) {
            $window = self::resolveScheduleWindow($schedule);
            $st = strtotime($day . ' ' . $window['start_time'] . ':00');
            $et = strtotime($day . ' ' . $window['end_time'] . ':00');
            $platforms = self::normalizeShanjianPublishPlatforms($window['platform']);
            if (empty($platforms) || $st === false || $et === false || $et <= $st) {
                continue;
            }

            $interval = ($et - $st) / count($platforms);
            foreach ($platforms as $index => $platformItem) {
                if ((int)$platformItem['account_type'] !== $platform) {
                    continue;
                }
                if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                    DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
                    $platform,
                    (string)$device->device_code,
                    '视频发布任务'
                )) {
                    continue;
                }
                $startTime = (int)($st + $index * $interval);
                $endTime = (int)($startTime + $interval);
                $account = SvAccount::field('id,account,type,nickname,avatar')
                    ->where('type', $platform)
                    ->where('user_id', $device->user_id)
                    ->where('device_code', $device->device_code)
                    ->findOrEmpty();
                if ($account->isEmpty()) {
                    continue;
                }

                $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                // 与视频发布逻辑一致：已过期仅记录日志，不跳过，便于当天晚跑仍可补建任务
                if ($endTime < time()) {
                    \think\facade\Log::channel('auto')->write(
                        $device->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']发布已过期',
                        'create'
                    );
                }

                $exist = SvPublishSettingDetail::where('user_id', $device->user_id)
                    ->where('device_code', $device->device_code)
                    ->where('auto_type', 1)
                    ->where('account_type', $account['type'])
                    ->where('task_type', 99)
                    ->where('persona_id', $device->persona_id)
                    ->where('publish_time', date('Y-m-d H:i:s', $startTime))
                    ->findOrEmpty();
                if (!$exist->isEmpty()) {
                    continue;
                }

                return [
                    'account' => $account,
                    'index' => $index,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'publish_time' => $startTime,
                    'exec_time' => $window['time_range'],
                ];
            }
        }

        return [];
    }

    private static function normalizeViralRecordCopywriting(mixed $copywriting): array
    {
        if (is_string($copywriting)) {
            $decoded = json_decode($copywriting, true);
            $copywriting = is_array($decoded) ? $decoded : ['content' => $copywriting];
        }
        if (!is_array($copywriting)) {
            $copywriting = [];
        }

        $tags = $copywriting['analysis_tags'] ?? $copywriting['topic_tags'] ?? $copywriting['tags'] ?? $copywriting['tag'] ?? [];
        if (is_array($tags)) {
            $tags = implode(',', array_filter(array_map('strval', $tags)));
        }

        return [
            'title' => trim((string)($copywriting['title'] ?? '')),
            'content' => trim((string)($copywriting['rewritten_text'] ?? $copywriting['content'] ?? $copywriting['text'] ?? '')),
            'tag' => trim((string)$tags),
        ];
    }

    private static function normalizeRecordImageUrls(mixed $images): array
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : preg_split('/[,，\s]+/', $images);
        }
        if (!is_array($images)) {
            return [];
        }

        $result = [];
        foreach ($images as $image) {
            if (is_array($image)) {
                $image = $image['url'] ?? $image['src'] ?? $image['path'] ?? '';
            }
            $image = trim((string)$image);
            if ($image !== '') {
                $result[] = $image;
            }
        }

        return array_values(array_unique($result));
    }

    public static function shanjianPersonaPublishCron()
    {
        try {
            $maxDay = date('Y-m-d', time());

            $devices = SvDevice::alias('d')
                ->field('d.device_code,d.auto_type,d.status,d.user_id,ap.persona_type,d.persona_id,ap.publish_mode')
                ->join('ai_persona ap', 'ap.id = d.persona_id')
                ->where('d.auto_type', 1)
                ->where('d.persona_id', '>', 0)
                ->where('ap.publish_mode', 1)
                ->where('d.device_code', 'in', function ($query) {
                    $query->name('shanjian_video_task')->where('auto_type', 1)
                        ->where('wechat_type', 0)
                        ->where('status', 'in', [2, 3])
                        ->where('thumb_status', 'in', [2, 3, 4]) //pic状态
                        ->where('is_publish', 0)
                        ->where('persona_id', '>', 0)
                        ->where('create_time', 'between', [strtotime(date('Y-m-d') . ' 00:00:00'), strtotime(date('Y-m-d') . ' 23:59:59')])
                        ->group('device_code')
                        ->field('device_code');
                })
                //->limit(10)
                ->select();
            //print_r(Db::getLastSql());die;
            foreach ($devices as $device) {
                $videos = ShanjianVideoTask::field('id,device_code, video_setting_id,pic, msg, video_result_url, status,persona_id, remark')
                    ->where('auto_type', 1)
                    ->where('wechat_type', 0)
                    ->where('status', 'in', [2, 3])
                    ->where('thumb_status', 'in', [2, 3, 4]) //pic状态
                    ->where('device_code', $device->device_code)
                    ->where('user_id', $device->user_id)
                    ->where('is_publish', 0)
                    ->where('persona_id', $device->persona_id)
                    ->where('create_time', 'between', [strtotime($maxDay . ' 00:00:00'), strtotime($maxDay . ' 23:59:59')])
                    ->order('id', 'asc')
                    ->select();
                if (!$videos->isEmpty()) {

                    self::runCreateShanjianPublish($videos, $device, $maxDay);
                }
            }

            self::recoverStaleShanjianPublishMedia();

            return true;
        } catch (\Throwable $th) {
            //throw $th;
            \think\facade\Log::channel('auto')->write('任务处理异常: ' . $th->__toString(), 'create');
            return false;
        }
    }



    private static function runCreateShanjianPublish($medias, SvDevice $device, ?string $date = null)
    {
        $date = $date ?: date('Y-m-d', time());
        $lockKey = self::getShanjianPersonaPublishLockKey($device, $date);
        $lockValue = (getmypid() ?: 0) . ':' . microtime(true);
        if (!self::acquireShanjianPersonaPublishLock($lockKey, $lockValue)) {
            return true;
        }

        try {
            return self::runCreateShanjianPublishBySchedule($device, $date, $lockKey, $lockValue);
        } finally {
            self::releaseShanjianPersonaPublishLock($lockKey, $lockValue);
        }
    }

    public static function createShanjianVideoFallbackForImageText(SvDevice $device, string $date, int $limit): array
    {
        $limit = max(0, $limit);
        $result = [
            'success' => true,
            'requested' => $limit,
            'created' => 0,
            'empty_slots' => 0,
            'available_videos' => 0,
            'msg' => '',
        ];
        if ($limit <= 0) {
            $result['msg'] = '无需兜底';
            return $result;
        }

        $lockKey = self::getShanjianPersonaPublishLockKey($device, $date);
        $lockValue = (getmypid() ?: 0) . ':' . microtime(true);
        if (!self::acquireShanjianPersonaPublishLock($lockKey, $lockValue)) {
            $result['msg'] = '兜底处理中，已被其他进程占用';
            return $result;
        }

        try {
            return self::runCreateShanjianImageTextVideoFallback($device, $date, $limit);
        } finally {
            self::releaseShanjianPersonaPublishLock($lockKey, $lockValue);
        }
    }

    private static function runCreateShanjianImageTextVideoFallback(SvDevice $device, string $date, int $limit): array
    {
        $result = [
            'success' => true,
            'requested' => $limit,
            'created' => 0,
            'empty_slots' => 0,
            'available_videos' => 0,
            'msg' => '',
        ];

        try {
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在');
            }
            if (!AiPersonaOptionService::isEnabledForPersonaId((int)$persona->id, 'content_publish')) {
                $result['msg'] = '内容发布开关已关闭';
                \think\facade\Log::channel('auto')->write($device->device_code . ' 内容发布开关已关闭（global_option.content_publish=0），跳过小红书图文视频兜底', 'create');
                return $result;
            }

            if (self::hasPendingImageTextViralWithoutPublish($device, $date)) {
                $result['msg'] = '存在改写未完成的图文仿写';
                \think\facade\Log::channel('auto')->write(
                    $device->device_code . ' 跳过小红书图文视频兜底：存在改写未完成的图文仿写，日期=' . $date,
                    'create'
                );
                return $result;
            }

            $slots = self::getImageTextVideoFallbackSlots($device, $persona, $date);
            $result['empty_slots'] = count($slots);
            $result['requested'] = min($limit, $result['empty_slots']);
            $result['available_videos'] = self::countReusableSameSlotVideosForImageTextFallback($device, $slots);
            if ($result['empty_slots'] <= 0) {
                $result['msg'] = '无小红书空闲发布时段';
                return $result;
            }
            if ($result['available_videos'] <= 0) {
                $result['msg'] = '无同时段其他平台可复用视频';
                return $result;
            }

            $targetSlots = array_slice($slots, 0, $result['requested']);
            $skippedNoReuse = 0;
            foreach ($targetSlots as $slot) {
                $scheduleSt = (int)($slot['schedule_start_time'] ?? 0);
                $scheduleEt = (int)($slot['schedule_end_time'] ?? 0);
                $slotVideoTaskId = self::getShanjianPublishSlotVideoTaskId($device, $scheduleSt, $scheduleEt);
                if ($slotVideoTaskId <= 0) {
                    $skippedNoReuse++;
                    \think\facade\Log::channel('auto')->write(
                        $device->device_code . ' 小红书图文视频兜底跳过：同时段无其他平台可复用视频，publish_time='
                        . date('Y-m-d H:i:s', (int)($slot['publish_time'] ?? 0)),
                        'create'
                    );
                    continue;
                }

                $media = self::getShanjianPublishMediaById($slotVideoTaskId);
                if ($media === null) {
                    $skippedNoReuse++;
                    \think\facade\Log::channel('auto')->write(
                        $device->device_code . ' 小红书图文视频兜底跳过：同窗视频不存在，video_task_id=' . $slotVideoTaskId,
                        'create'
                    );
                    continue;
                }

                if (self::createShanjianImageTextVideoFallbackDetail($device, $persona, $media, $slot, true)) {
                    $result['created']++;
                }
            }

            if ($result['created'] >= $result['requested']) {
                $result['msg'] = '兜底生成完成';
            } elseif ($result['created'] > 0) {
                $result['msg'] = '兜底部分生成完成';
            } elseif ($skippedNoReuse > 0) {
                $result['msg'] = '无同时段其他平台可复用视频';
            } elseif ($result['msg'] === '') {
                $result['msg'] = '兜底生成失败';
            }
            return $result;
        } catch (\Throwable $th) {
            $result['success'] = false;
            $result['msg'] = $th->getMessage();
            \think\facade\Log::channel('auto')->write('小红书图文视频兜底执行失败：' . $th->__toString(), 'create');
            return $result;
        }
    }

    private static function getImageTextVideoFallbackSlots(SvDevice $device, AiPersona $persona, string $date): array
    {
        $slots = [];
        $schedules = self::getAutoSchedule($persona, 5);
        foreach ($schedules as $schedule) {
            $window = self::resolveScheduleWindow($schedule);
            $st = strtotime($date . ' ' . $window['start_time'] . ':00');
            $et = strtotime($date . ' ' . $window['end_time'] . ':00');
            $platforms = self::normalizeShanjianPublishPlatforms($window['platform']);
            if (empty($platforms) || $st === false || $et === false || $et <= $st) {
                continue;
            }

            $publishAccounts = self::getAvailableShanjianPublishAccounts($device, $platforms, $st, $et);
            foreach ($publishAccounts as $publishAccount) {
                $accountType = (int)($publishAccount['account']['type'] ?? 0);
                if (!self::shouldAllowImageTextVideoFallbackSlot($persona, $accountType)) {
                    continue;
                }

                $publishAccount['exec_time'] = $window['time_range'];
                $publishAccount['schedule_start_time'] = $st;
                $publishAccount['schedule_end_time'] = $et;
                $slots[] = $publishAccount;
            }
        }

        return $slots;
    }

    /**
     * 统计图文空坑中，同 schedule 窗内已有其他平台 video_task_id 可复用的数量。
     *
     * @param list<array> $slots
     */
    private static function countReusableSameSlotVideosForImageTextFallback(SvDevice $device, array $slots): int
    {
        $count = 0;
        foreach ($slots as $slot) {
            $scheduleSt = (int)($slot['schedule_start_time'] ?? 0);
            $scheduleEt = (int)($slot['schedule_end_time'] ?? 0);
            if ($scheduleSt <= 0 || $scheduleEt <= $scheduleSt) {
                continue;
            }
            if (self::getShanjianPublishSlotVideoTaskId($device, $scheduleSt, $scheduleEt) > 0) {
                $count++;
            }
        }

        return $count;
    }

    private static function countAvailableShanjianPublishMedia(SvDevice $device, string $date): int
    {
        return ShanjianVideoTask::where('auto_type', 1)
            ->where('wechat_type', 0)
            ->where('status', 'in', [2, 3])
            ->where('thumb_status', 'in', [2, 3, 4])
            ->where('device_code', $device->device_code)
            ->where('user_id', $device->user_id)
            ->where('is_publish', 0)
            ->where('persona_id', $device->persona_id)
            ->where('create_time', 'between', [strtotime($date . ' 00:00:00'), strtotime($date . ' 23:59:59')])
            ->where('id', 'not in', function ($query) {
                $query->name('sv_publish_setting_detail')
                    ->where('task_type', 99)
                    ->where('scene', 1)
                    ->where('video_task_id', '>', 0)
                    ->whereNull('delete_time')
                    ->field('video_task_id');
            })
            ->count();
    }

    /**
     * @param bool $reuseExistingMedia 为 true 时复用同窗其他平台已绑定视频，不校验/改写 is_publish
     */
    private static function createShanjianImageTextVideoFallbackDetail(
        SvDevice $device,
        AiPersona $persona,
        ShanjianVideoTask $media,
        array $slot,
        bool $reuseExistingMedia = false
    ): bool
    {
        $account = $slot['account'];
        $publishTime = (int)$slot['publish_time'];
        $startTime = (int)$slot['start_time'];
        $endTime = (int)$slot['end_time'];
        $index = (int)$slot['index'];
        $execTime = (string)$slot['exec_time'];
        $taskId = generate_unique_task_id();
        $title = '';
        $content = '';
        $tag = '';
        $status = 0;
        $remark = '';
        $libraryItemId = 0;

        if ((int)$media->status === 2) {
            $status = 2;
            $remark = $media->remark ?? 'video generate failed';
        }

        if ((int)$media->status === 3) {
            try {
                $response = self::getPublishContentByConfig(
                    $persona,
                    $media->msg != '' ? $media->msg : '',
                    $taskId,
                    $device->user_id,
                    AiPersona::PUBLISH_PLATFORM_XHS
                );
            } catch (\Throwable $th) {
                \think\facade\Log::channel('auto')->write('小红书图文视频兜底文案生成异常：' . $th->__toString(), 'create');
                return false;
            }
            if ((int)$response['code'] !== 10000) {
                \think\facade\Log::channel('auto')->write('小红书图文视频兜底文案生成失败：' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'create');
                return false;
            }

            $title = $response['data']['title'] ?? '';
            $content = $response['data']['content'] ?? '';
            $tag = $response['data']['tag'] ?? '';
            $libraryItemId = (int)($response['data']['library_item_id'] ?? 0);
            if (!empty($response['data']['library_empty'])) {
                $status = 2;
                $remark = $response['data']['library_message'] ?? '发布文案库暂无可用文案';
            }
        }

        usleep(500000);
        try {
            self::runShanjianPublishDetailTransaction(function (ShanjianVideoTask $locked) use (
                $device,
                $account,
                $publishTime,
                $startTime,
                $endTime,
                $index,
                $execTime,
                $taskId,
                $title,
                $content,
                $tag,
                $status,
                $remark,
                $libraryItemId,
                $reuseExistingMedia
            ) {
                if (!$reuseExistingMedia && (int)$locked->is_publish !== 2) {
                    throw new \Exception('闪剪发布素材未被占用：' . $locked->id);
                }

                $materialUrl = $locked->video_result_url != '' ? FileService::getFileUrl($locked->video_result_url) : '';

                $setting = SvPublishSetting::create([
                    'user_id' => $device->user_id,
                    'task_type' => 99,
                    'name' => '自动化视频发布任务' . date('YmdHsi', time()),
                    'accounts' => json_encode([$account], JSON_UNESCAPED_UNICODE),
                    'auto_type' => 1,
                    'video_setting_id' => 0,
                    'matrix_media_setting_id' => 0,
                    'video_ids' => json_encode([$locked->id], JSON_UNESCAPED_UNICODE),
                    'scene' => 1,
                    'type' => 0,
                    'media_type' => 1,
                    'publish_start' => date('Y-m-d', $publishTime),
                    'publish_end' => date('Y-m-d', $publishTime),
                    'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                    'data_type' => 0,
                    'date_type' => 1,
                    'publish_frep' => 1,
                    'persona_id' => $device->persona_id,
                    'status' => 3,
                    'create_time' => time()
                ]);

                $paccount = SvPublishSettingAccount::create([
                    'publish_id' => $setting->id,
                    'task_type' => 99,
                    'user_id' => $device->user_id,
                    'name' => '自动化视频发布任务' . date('YmdHsi', time()),
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'nickname' => $account['nickname'],
                    'avatar' => $account['avatar'],
                    'auto_type' => 1,
                    'device_code' => $device->device_code,
                    'media_type' => 1,
                    'video_setting_id' => 0,
                    'video_ids' => json_encode([$locked->id], JSON_UNESCAPED_UNICODE),
                    'matrix_media_setting_id' => 0,
                    'scene' => 1,
                    'status' => 2,
                    'task_status' => 2,
                    'publish_start' => date('Y-m-d', $publishTime),
                    'publish_end' => date('Y-m-d', $publishTime),
                    'next_publish_time' => date('Y-m-d H:i:s', $publishTime),
                    'count' => 1,
                    'published_count' => 0,
                    'persona_id' => $device->persona_id,
                    'data_type' => 0,
                    'create_time' => time()
                ]);

                $detail = SvPublishSettingDetail::create([
                    'publish_id' => $setting->id,
                    'publish_account_id' => $paccount->id,
                    'task_type' => 99,
                    'video_task_id' => $locked->id,
                    'video_setting_id' => $locked->video_setting_id,
                    'user_id' => $device->user_id,
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'nickname' => $account['nickname'],
                    'avatar' => $account['avatar'],
                    'auto_type' => 1,
                    'device_code' => $device->device_code,
                    'matrix_media_setting_id' => 0,
                    'material_id' => $locked->id,
                    'material_url' => $materialUrl,
                    'material_title' => $title,
                    'material_subtitle' => $content,
                    'material_type' => 1,
                    'material_tag' => $tag,
                    'pic' => FileService::getFileUrl($locked->pic),
                    'poi' => '',
                    'data_type' => 0,
                    'task_id' => $taskId,
                    'sub_task_id' => time() . ($index + 100),
                    'scene' => 1,
                    'platform' => $account['type'],
                    'status' => $status,
                    'remark' => $remark,
                    'persona_id' => $device->persona_id,
                    'publish_time' => date('Y-m-d H:i:s', $publishTime),
                    'create_time' => time()
                ]);

                if ($libraryItemId > 0) {
                    CopywritingLibraryLogic::recordUse(
                        $libraryItemId,
                        AiPersonaCopywritingLibraryUseLog::SCENE_PUBLISH,
                        [
                            'related_publish_detail_id' => (int)$detail->id,
                            'related_video_task_id' => (int)$locked->id,
                            'platform' => (int)$account['type'],
                            'task_id' => $taskId,
                        ]
                    );
                }

                \app\common\model\sv\SvDeviceTask::create([
                    'user_id' => $device->user_id,
                    'device_code' => $device->device_code,
                    'task_type' => DeviceEnum::AUTO_TYPE_PUBLISH,
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'nickname' => $account['nickname'],
                    'avatar' => $account['avatar'],
                    'auto_type' => 1,
                    'task_name' => '自动化视频发布任务' . date('YmdHsi', time()),
                    'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'day' => date('Y-m-d', $publishTime),
                    'status' => $status === 2 ? 3 : 0,
                    'remark' => $remark,
                    'sub_task_id' => $paccount->id,
                    'sub_data_id' => $detail->id,
                    'persona_id' => $device->persona_id,
                    'task_scene' => DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
                    'source' => DeviceEnum::TASK_SOURCE_PUBLISH,
                    'create_time' => time(),
                ]);

                if (!$reuseExistingMedia && !self::finishShanjianPublishMedia($locked)) {
                    throw new \Exception('闪剪发布素材标记完成失败：' . $locked->id);
                }

                return true;
            }, $media);

            return true;
        } catch (\Throwable $th) {
            if (!$reuseExistingMedia && self::isDeadlockException($th)) {
                self::releaseShanjianPublishMedia($media);
            }
            \think\facade\Log::channel('auto')->write('小红书图文视频兜底发布明细生成失败：' . $th->__toString(), 'create');
            return false;
        }
    }

    private static function runCreateShanjianPublishBySchedule(SvDevice $device, string $date, string $lockKey = '', string $lockValue = ''): bool
    {
        $claimedMedia = null;
        try {
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在');
            }
            if (!AiPersonaOptionService::isEnabledForPersonaId((int)$persona->id, 'content_publish')) {
                \think\facade\Log::channel('auto')->write($device->device_code . ' 内容发布开关已关闭（global_option.content_publish=0），跳过闪剪人设发布', 'create');
                return true;
            }

            $schedules = self::getAutoSchedule($persona, 5);
            foreach ($schedules as $schedule) {
                $window = self::resolveScheduleWindow($schedule);
                $st = strtotime($date . ' ' . $window['start_time'] . ':00');
                $et = strtotime($date . ' ' . $window['end_time'] . ':00');
                $platforms = self::normalizeShanjianPublishPlatforms($window['platform']);
                if (empty($platforms) || $st === false || $et === false || $et <= $st) {
                    continue;
                }

                $publishAccounts = self::getAvailableShanjianPublishAccounts($device, $platforms, $st, $et);
                $publishAccounts = array_values(array_filter($publishAccounts, static function ($publishAccount) use ($persona) {
                    $accountType = (int)($publishAccount['account']['type'] ?? 0);
                    return self::shouldCreateVideoPublishForAccount($persona, $accountType);
                }));
                if (empty($publishAccounts)) {
                    continue;
                }

                $isClaimedMedia = false;
                $slotVideoTaskId = self::getShanjianPublishSlotVideoTaskId($device, $st, $et);
                if ($slotVideoTaskId > 0) {
                    $media = self::getShanjianPublishMediaById($slotVideoTaskId);
                    if ($media === null) {
                        continue;
                    }
                } else {
                    $media = self::claimShanjianPublishMedia($device, $date);
                    if ($media === null) {
                        break;
                    }
                    $claimedMedia = $media;
                    $isClaimedMedia = true;
                }

                $status = 0;
                $remark = '';
                if ((int)$media->status === 2) {
                    $status = 2;
                    $remark = $media->remark ?? 'video generate failed';
                }

                $execTime = $window['time_range'];
                $materialUrl = $media->video_result_url != '' ? FileService::getFileUrl($media->video_result_url) : '';
                $createdCount = 0;

                foreach ($publishAccounts as $publishAccount) {
                    $account = $publishAccount['account'];
                    $publishTime = $publishAccount['publish_time'];
                    $startTime = $publishAccount['start_time'];
                    $endTime = $publishAccount['end_time'];
                    $index = $publishAccount['index'];
                    $taskId = generate_unique_task_id();
                    $title = '';
                    $content = '';
                    $tag = '';
                    $libraryItemId = 0;
                    $detailStatus = $status;
                    $detailRemark = $remark;

                    if ((int)$media->status === 3) {
                        try {
                            $response = self::getPublishContentByConfig(
                                $persona,
                                $media->msg != '' ? $media->msg : '',
                                $taskId,
                                $device->user_id,
                                (int)$account['type']
                            );
                        } catch (\Throwable $th) {
                            \think\facade\Log::channel('auto')->write('24小时闪剪发布文案生成异常：' . $th->__toString(), 'create');
                            continue;
                        }
                        if ((int)$response['code'] !== 10000) {
                            \think\facade\Log::channel('auto')->write('24小时闪剪发布文案生成失败：' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'create');
                            continue;
                        }

                        $title = $response['data']['title'] ?? '';
                        $content = $response['data']['content'] ?? '';
                        $tag = $response['data']['tag'] ?? '';
                        $libraryItemId = (int)($response['data']['library_item_id'] ?? 0);
                        if (!empty($response['data']['library_empty'])) {
                            $detailStatus = 2;
                            $detailRemark = $response['data']['library_message'] ?? '发布文案库暂无可用文案';
                        }
                    }

                    usleep(500000);
                    if ($lockKey !== '' && $lockValue !== '') {
                        self::renewShanjianPersonaPublishLock($lockKey, $lockValue);
                    }
                    try {
                        self::runShanjianPublishDetailTransaction(function (ShanjianVideoTask $locked) use (
                            $device,
                            $account,
                            $publishTime,
                            $startTime,
                            $endTime,
                            $index,
                            $execTime,
                            $taskId,
                            $title,
                            $content,
                            $tag,
                            $detailStatus,
                            $detailRemark,
                            $libraryItemId,
                            $materialUrl,
                            $createdCount,
                            $isClaimedMedia
                        ) {
                            if ($isClaimedMedia && $createdCount === 0 && (int)$locked->is_publish !== 2) {
                                throw new \Exception('闪剪发布素材未被占用：' . $locked->id);
                            }

                            $setting = SvPublishSetting::create([
                                'user_id' => $device->user_id,
                                'task_type' => 99,
                                'name' => '自动化视频发布任务' . date('YmdHsi', time()),
                                'accounts' => json_encode([$account], JSON_UNESCAPED_UNICODE),
                                'auto_type' => 1,
                                'video_setting_id' => 0,
                                'matrix_media_setting_id' => 0,
                                'video_ids' => json_encode([$locked->id], JSON_UNESCAPED_UNICODE),
                                'scene' => 1,
                                'type' => 0,
                                'media_type' => 1,
                                'publish_start' => date('Y-m-d', $publishTime),
                                'publish_end' => date('Y-m-d', $publishTime),
                                'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                                'data_type' => 0,
                                'date_type' => 1,
                                'publish_frep' => 1,
                                'persona_id' => $device->persona_id,
                                'status' => 3,
                                'create_time' => time()
                            ]);

                            $paccount = SvPublishSettingAccount::create([
                                'publish_id' => $setting->id,
                                'task_type' => 99,
                                'user_id' => $device->user_id,
                                'name' => '自动化视频发布任务' . date('YmdHsi', time()),
                                'account' => $account['account'],
                                'account_type' => $account['type'],
                                'nickname' => $account['nickname'],
                                'avatar' => $account['avatar'],
                                'auto_type' => 1,
                                'device_code' => $device->device_code,
                                'media_type' => 1,
                                'video_setting_id' => 0,
                                'video_ids' => json_encode([$locked->id], JSON_UNESCAPED_UNICODE),
                                'matrix_media_setting_id' => 0,
                                'scene' => 1,
                                'status' => 2,
                                'task_status' => 2,
                                'publish_start' => date('Y-m-d', $publishTime),
                                'publish_end' => date('Y-m-d', $publishTime),
                                'next_publish_time' => date('Y-m-d H:i:s', $publishTime),
                                'count' => 1,
                                'published_count' => 0,
                                'persona_id' => $device->persona_id,
                                'data_type' => 0,
                                'create_time' => time()
                            ]);

                            $detail = SvPublishSettingDetail::create([
                                'publish_id' => $setting->id,
                                'publish_account_id' => $paccount->id,
                                'task_type' => 99,
                                'video_task_id' => $locked->id,
                                'video_setting_id' => $locked->video_setting_id,
                                'user_id' => $device->user_id,
                                'account' => $account['account'],
                                'account_type' => $account['type'],
                                'nickname' => $account['nickname'],
                                'avatar' => $account['avatar'],
                                'auto_type' => 1,
                                'device_code' => $device->device_code,
                                'matrix_media_setting_id' => 0,
                                'material_id' => $locked->id,
                                'material_url' => $materialUrl,
                                'material_title' => $title,
                                'material_subtitle' => $content,
                                'material_type' => 1,
                                'material_tag' => $tag,
                                'pic' => FileService::getFileUrl($locked->pic),
                                'poi' => '',
                                'data_type' => 0,
                                'task_id' => $taskId,
                                'sub_task_id' => time() . ($index + 100),
                                'scene' => 1,
                                'platform' => $account['type'],
                                'status' => $detailStatus,
                                'remark' => $detailRemark,
                                'persona_id' => $device->persona_id,
                                'publish_time' => date('Y-m-d H:i:s', $publishTime),
                                'create_time' => time()
                            ]);

                            if ($libraryItemId > 0) {
                                CopywritingLibraryLogic::recordUse(
                                    $libraryItemId,
                                    AiPersonaCopywritingLibraryUseLog::SCENE_PUBLISH,
                                    [
                                        'related_publish_detail_id' => (int)$detail->id,
                                        'related_video_task_id' => (int)$locked->id,
                                        'platform' => (int)$account['type'],
                                        'task_id' => $taskId,
                                    ]
                                );
                            }

                            \app\common\model\sv\SvDeviceTask::create([
                                'user_id' => $device->user_id,
                                'device_code' => $device->device_code,
                                'task_type' => DeviceEnum::AUTO_TYPE_PUBLISH,
                                'account' => $account['account'],
                                'account_type' => $account['type'],
                                'nickname' => $account['nickname'],
                                'avatar' => $account['avatar'],
                                'auto_type' => 1,
                                'task_name' => '自动化视频发布任务' . date('YmdHsi', time()),
                                'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'day' => date('Y-m-d', $publishTime),
                                'status' => $detailStatus === 2 ? 3 : 0,
                                'remark' => $detailRemark,
                                'sub_task_id' => $paccount->id,
                                'sub_data_id' => $detail->id,
                                'persona_id' => $device->persona_id,
                                'task_scene' => DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
                                'source' => DeviceEnum::TASK_SOURCE_PUBLISH,
                                'create_time' => time(),
                            ]);

                            if ($createdCount === 0 && $isClaimedMedia) {
                                if (!self::finishShanjianPublishMedia($locked)) {
                                    throw new \Exception('闪剪发布素材标记完成失败：' . $locked->id);
                                }
                            }

                            return true;
                        }, $media);

                        $createdCount++;
                        if ($createdCount === 1 && $isClaimedMedia) {
                            $claimedMedia = null;
                        }
                    } catch (\Throwable $th) {
                        self::safeRollbackShanjianPublishTransaction(
                            $media,
                            $th,
                            $createdCount === 0 && $isClaimedMedia && self::isDeadlockException($th)
                        );
                        \think\facade\Log::channel('auto')->write('24小时闪剪发布明细生成失败：' . $th->__toString(), 'create');
                        continue;
                    }
                }

                if ($createdCount <= 0) {
                    if ($isClaimedMedia) {
                        self::releaseShanjianPublishMedia($media);
                        $claimedMedia = null;
                    }
                } elseif ($isClaimedMedia) {
                    $claimedMedia = null;
                }
            }

            return true;
        } catch (\Throwable $th) {
            if ($claimedMedia !== null) {
                self::releaseShanjianPublishMedia($claimedMedia);
            }
            \think\facade\Log::channel('auto')->write('24小时闪剪发布任务失败：' . $th->__toString(), 'create');
            return false;
        }
    }

    private static function getShanjianPersonaPublishLockKey(SvDevice $device, string $date): string
    {
        return 'shanjian_persona_publish:' . $date . ':' . $device->user_id . ':' . $device->device_code . ':' . $device->persona_id;
    }

    private static function lockShanjianPublishMediaRow(int $mediaId): ?ShanjianVideoTask
    {
        $media = ShanjianVideoTask::where('id', $mediaId)->lock(true)->findOrEmpty();
        return $media->isEmpty() ? null : $media;
    }

    private static function isDeadlockException(\Throwable $e): bool
    {
        $message = $e->getMessage();
        if (str_contains($message, '1213')
            || stripos($message, 'Deadlock') !== false
            || stripos($message, 'Serialization failure') !== false
        ) {
            return true;
        }

        $previous = $e->getPrevious();
        if ($previous instanceof \Throwable) {
            return self::isDeadlockException($previous);
        }

        return false;
    }

    /**
     * @return mixed
     * @throws \Throwable
     */
    private static function runShanjianPublishDetailTransaction(callable $callback, ShanjianVideoTask $media, int $maxRetries = self::SHANJIAN_PUBLISH_DEADLOCK_MAX_RETRIES)
    {
        $attempt = 0;
        while ($attempt < $maxRetries) {
            $attempt++;
            $transStarted = false;
            try {
                Db::startTrans();
                $transStarted = true;

                $locked = self::lockShanjianPublishMediaRow((int)$media->id);
                if ($locked === null) {
                    throw new \Exception('闪剪发布素材不存在：' . $media->id);
                }

                $result = $callback($locked);
                Db::commit();
                return $result;
            } catch (\Throwable $e) {
                if ($transStarted) {
                    try {
                        Db::rollback();
                    } catch (\Throwable $rollbackThrowable) {
                        \think\facade\Log::channel('auto')->write('闪剪发布明细事务回滚失败：' . json_encode([
                            'video_task_id' => $media->id,
                            'attempt' => $attempt,
                            'error' => $rollbackThrowable->getMessage(),
                        ], JSON_UNESCAPED_UNICODE), 'create');
                    }
                }

                if (self::isDeadlockException($e) && $attempt < $maxRetries) {
                    \think\facade\Log::channel('auto')->write('闪剪发布明细死锁重试：' . json_encode([
                        'video_task_id' => $media->id,
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                    ], JSON_UNESCAPED_UNICODE), 'create');
                    usleep(random_int(100000, 300000));
                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('闪剪发布明细事务重试次数已用尽：' . $media->id);
    }

    private static function recoverStaleShanjianPublishMedia(): void
    {
        try {
            $threshold = time() - self::SHANJIAN_PERSONA_PUBLISH_STALE_SECONDS;
            $medias = ShanjianVideoTask::field('id,user_id,device_code,persona_id,is_publish,update_time')
                ->where('auto_type', 1)
                ->where('wechat_type', 0)
                ->where('is_publish', 2)
                ->where('persona_id', '>', 0)
                ->where('update_time', '<=', $threshold)
                ->order('update_time', 'asc')
                ->limit(200)
                ->select();

            foreach ($medias as $media) {
                Db::startTrans();
                try {
                    $locked = self::lockShanjianPublishMediaRow((int)$media->id);
                    if ($locked === null || (int)$locked->is_publish !== 2) {
                        Db::rollback();
                        continue;
                    }
                    if ((int)$locked->update_time > $threshold) {
                        Db::rollback();
                        continue;
                    }

                    $detailCount = SvPublishSettingDetail::where('video_task_id', $media->id)
                        ->where('task_type', 99)
                        ->where('scene', 1)
                        ->whereNull('delete_time')
                        ->count();
                    $newStatus = $detailCount > 0 ? 1 : 0;
                    $updated = ShanjianVideoTask::where('id', $media->id)
                        ->where('is_publish', 2)
                        ->where('update_time', '<=', $threshold)
                        ->update([
                            'is_publish' => $newStatus,
                            'update_time' => time(),
                        ]);

                    Db::commit();
                    self::logShanjianPublishMediaStatus('recover', $media, 2, $newStatus, $updated, [
                        'detail_count' => $detailCount,
                        'stale_seconds' => self::SHANJIAN_PERSONA_PUBLISH_STALE_SECONDS,
                        'threshold_time' => $threshold,
                    ]);
                } catch (\Throwable $th) {
                    try {
                        Db::rollback();
                    } catch (\Throwable $rollbackThrowable) {
                        \think\facade\Log::channel('auto')->write('闪剪发布素材恢复事务回滚失败：' . $rollbackThrowable->getMessage(), 'create');
                    }
                    if (self::isDeadlockException($th)) {
                        usleep(random_int(100000, 300000));
                    }
                    \think\facade\Log::channel('auto')->write('闪剪发布素材恢复单条处理失败：' . json_encode([
                        'video_task_id' => $media->id,
                        'error' => $th->getMessage(),
                    ], JSON_UNESCAPED_UNICODE), 'create');
                }
            }
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('闪剪发布素材恢复失败：' . $th->__toString(), 'create');
        }
    }

    private static function safeRollbackShanjianPublishTransaction(ShanjianVideoTask $media, \Throwable $cause, bool $shouldReleaseMedia = false): void
    {
        try {
            Db::rollback();
        } catch (\Throwable $rollbackThrowable) {
            \think\facade\Log::channel('auto')->write('闪剪发布事务回滚失败：' . json_encode([
                'video_task_id' => $media->id,
                'device_code' => $media->device_code,
                'persona_id' => $media->persona_id,
                'cause' => $cause->getMessage(),
                'rollback_error' => $rollbackThrowable->getMessage(),
            ], JSON_UNESCAPED_UNICODE), 'create');
        }

        if ($shouldReleaseMedia) {
            try {
                self::releaseShanjianPublishMedia($media);
            } catch (\Throwable $releaseThrowable) {
                \think\facade\Log::channel('auto')->write('闪剪发布素材死锁后释放失败：' . json_encode([
                    'video_task_id' => $media->id,
                    'error' => $releaseThrowable->getMessage(),
                ], JSON_UNESCAPED_UNICODE), 'create');
            }
        }
    }

    private static function acquireShanjianPersonaPublishLock(string $lockKey, string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            if (!$redis->setnx($lockKey, $lockValue)) {
                return false;
            }
            $redis->expire($lockKey, self::SHANJIAN_PERSONA_PUBLISH_LOCK_TTL);
            return true;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('闪剪人设发布加锁失败：' . $th->getMessage(), 'create');
            return false;
        }
    }

    private static function renewShanjianPersonaPublishLock(string $lockKey, string $lockValue): void
    {
        if ($lockKey === '' || $lockValue === '') {
            return;
        }

        try {
            $redis = Cache::store('redis')->handler();
            if ($redis->get($lockKey) === $lockValue) {
                $redis->expire($lockKey, self::SHANJIAN_PERSONA_PUBLISH_LOCK_TTL);
            }
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('闪剪人设发布锁续期失败：' . $th->getMessage(), 'create');
        }
    }

    private static function releaseShanjianPersonaPublishLock(string $lockKey, string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            if ($redis->get($lockKey) === $lockValue) {
                $redis->del($lockKey);
            }
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('闪剪人设发布解锁失败：' . $th->getMessage(), 'create');
        }
    }

    /**
     * 主路径仅允许「平台发布类型=视频」生成闪剪视频发布记录。
     */
    private static function shouldCreateVideoPublishForAccount(AiPersona $persona, int $accountType): bool
    {
        $platformConfig = AiPersona::getPlatformContentPublishConfig($persona['content_publish_config'], $accountType);

        return (int)$platformConfig['publish_media_type'] === AiPersona::PUBLISH_MEDIA_TYPE_VIDEO;
    }

    /**
     * 图文缺口视频兜底：仅小红书且平台配置为图文时可占用空时段。
     */
    private static function shouldAllowImageTextVideoFallbackSlot(AiPersona $persona, int $accountType): bool
    {
        if ($accountType !== AiPersona::PUBLISH_PLATFORM_XHS) {
            return false;
        }

        $platformConfig = AiPersona::getPlatformContentPublishConfig($persona['content_publish_config'], $accountType);

        return (int)$platformConfig['publish_media_type'] === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT;
    }

    /**
     * 当天是否存在改写未完成（待改写/改写中）且尚未生成发布明细的小红书图文仿写。
     * 存在时视频兜底不得抢占图文发布时间段；已改写成功的走 fallback 先生成图文发布。
     */
    public static function hasPendingImageTextViralWithoutPublish(SvDevice $device, string $date): bool
    {
        return SvDeviceViralRecord::where('device_code', (string)$device->device_code)
            ->where('user_id', (int)$device->user_id)
            ->where('persona_id', (int)$device->persona_id)
            ->where('day', $date)
            ->where('publish_media_type', AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT)
            ->where('publish_platform', AiPersona::PUBLISH_PLATFORM_XHS)
            ->where('publish_detail_id', 0)
            ->where('image_rewrite_status', 'in', [
                SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT,
                SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING,
            ])
            ->where('status', 'in', [0, 3, 4, 6])
            ->where('is_interested', 1)
            ->count() > 0;
    }

    private static function normalizeShanjianPublishPlatforms(mixed $platforms): array
    {
        if (is_string($platforms)) {
            $platforms = json_decode($platforms, true) ?: [];
        }
        if (!is_array($platforms)) {
            return [];
        }

        $normalized = [];
        foreach ($platforms as $index => $platform) {
            if (is_object($platform)) {
                $platform = (array)$platform;
            }
            if (!is_array($platform) || empty($platform['account_type'])) {
                continue;
            }
            $platform['order'] = (int)($platform['order'] ?? ($index + 1));
            $normalized[] = $platform;
        }

        usort($normalized, static function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        return $normalized;
    }

    private static function getAvailableShanjianPublishAccounts(SvDevice $device, array $platforms, int $st, int $et): array
    {
        $interval = ($et - $st) / count($platforms);
        $accounts = [];
        foreach ($platforms as $index => $platform) {
            if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
                (int)($platform['account_type'] ?? 0),
                (string)$device->device_code,
                '视频发布任务'
            )) {
                continue;
            }
            $startTime = (int)($st + $index * $interval);
            $endTime = (int)($startTime + $interval);
            $account = SvAccount::field('id,account,type,nickname,avatar')
                ->where('type', $platform['account_type'])
                ->where('user_id', $device->user_id)
                ->where('device_code', $device->device_code)
                ->findOrEmpty();
            if ($account->isEmpty()) {
                continue;
            }

            $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
            // 与视频发布逻辑一致：已过期仅记录日志，不跳过，便于当天晚跑仍可补建任务
            if ($endTime < time()) {
                \think\facade\Log::channel('auto')->write(
                    $device->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']已过期',
                    'create'
                );
            }

            $exist = SvPublishSettingDetail::where('user_id', $device->user_id)
                ->where('device_code', $device->device_code)
                ->where('auto_type', 1)
                ->where('account_type', $account['type'])
                ->where('task_type', 99)
                ->where('persona_id', $device->persona_id)
                ->where('publish_time', date('Y-m-d H:i:s', $startTime))
                ->findOrEmpty();
            if (!$exist->isEmpty()) {
                continue;
            }

            $accounts[] = [
                'account' => $account,
                'index' => $index,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'publish_time' => $startTime,
            ];
        }
        return $accounts;
    }

    private static function getShanjianPublishSlotVideoTaskId(SvDevice $device, int $st, int $et): int
    {
        return (int)SvPublishSettingDetail::where('user_id', $device->user_id)
            ->where('device_code', $device->device_code)
            ->where('auto_type', 1)
            ->where('task_type', 99)
            ->where('scene', 1)
            ->where('persona_id', $device->persona_id)
            ->where('video_task_id', '>', 0)
            ->where('publish_time', '>=', date('Y-m-d H:i:s', $st))
            ->where('publish_time', '<', date('Y-m-d H:i:s', $et))
            ->order('id', 'asc')
            ->value('video_task_id');
    }

    private static function getShanjianPublishMediaById(int $mediaId): ?ShanjianVideoTask
    {
        $media = ShanjianVideoTask::field('id,user_id,device_code, video_setting_id,pic, msg, video_result_url, status,persona_id, remark')
            ->where('id', $mediaId)
            ->findOrEmpty();
        return $media->isEmpty() ? null : $media;
    }

    private static function claimShanjianPublishMedia(SvDevice $device, string $date): ?ShanjianVideoTask
    {
        while (true) {
            $media = ShanjianVideoTask::field('id,user_id,device_code, video_setting_id,pic, msg, video_result_url, status,persona_id, remark')
                ->where('auto_type', 1)
                ->where('wechat_type', 0)
                ->where('status', 'in', [2, 3])
                ->where('thumb_status', 'in', [2, 3, 4])
                ->where('device_code', $device->device_code)
                ->where('user_id', $device->user_id)
                ->where('is_publish', 0)
                ->where('persona_id', $device->persona_id)
                ->where('create_time', 'between', [strtotime($date . ' 00:00:00'), strtotime($date . ' 23:59:59')])
                ->where('id', 'not in', function ($query) {
                    $query->name('sv_publish_setting_detail')
                        ->where('task_type', 99)
                        ->where('scene', 1)
                        ->where('video_task_id', '>', 0)
                        ->whereNull('delete_time')
                        ->field('video_task_id');
                })
                ->order('id', 'asc')
                ->findOrEmpty();
            if ($media->isEmpty()) {
                return null;
            }

            Db::startTrans();
            try {
                $locked = self::lockShanjianPublishMediaRow((int)$media->id);
                if ($locked === null || (int)$locked->is_publish !== 0) {
                    Db::rollback();
                    continue;
                }

                $updated = ShanjianVideoTask::where('id', $media->id)
                    ->where('is_publish', 0)
                    ->update([
                        'is_publish' => 2,
                        'update_time' => time(),
                    ]);
                if ($updated <= 0) {
                    Db::rollback();
                    continue;
                }

                Db::commit();
                self::logShanjianPublishMediaStatus('claim', $media, 0, 2, $updated, [
                    'date' => $date,
                ]);
                $media->is_publish = 2;
                return $media;
            } catch (\Throwable $e) {
                try {
                    Db::rollback();
                } catch (\Throwable $rollbackThrowable) {
                    \think\facade\Log::channel('auto')->write('闪剪发布素材占用事务回滚失败：' . $rollbackThrowable->getMessage(), 'create');
                }
                if (self::isDeadlockException($e)) {
                    usleep(random_int(100000, 300000));
                    continue;
                }
                throw $e;
            }
        }
    }

    private static function finishShanjianPublishMedia(ShanjianVideoTask $media): bool
    {
        $updated = ShanjianVideoTask::where('id', $media->id)
            ->where('is_publish', 2)
            ->update([
                'is_publish' => 1,
                'update_time' => time(),
            ]);
        self::logShanjianPublishMediaStatus('finish', $media, 2, 1, $updated);
        return $updated > 0;
    }

    private static function releaseShanjianPublishMedia(ShanjianVideoTask $media): bool
    {
        Db::startTrans();
        try {
            $locked = self::lockShanjianPublishMediaRow((int)$media->id);
            if ($locked === null || (int)$locked->is_publish !== 2) {
                Db::rollback();
                return false;
            }

            $updated = ShanjianVideoTask::where('id', $media->id)
                ->where('is_publish', 2)
                ->update([
                    'is_publish' => 0,
                    'update_time' => time(),
                ]);
            Db::commit();
            self::logShanjianPublishMediaStatus('release', $media, 2, 0, $updated);
            return $updated > 0;
        } catch (\Throwable $e) {
            try {
                Db::rollback();
            } catch (\Throwable $rollbackThrowable) {
                \think\facade\Log::channel('auto')->write('闪剪发布素材释放事务回滚失败：' . $rollbackThrowable->getMessage(), 'create');
            }
            throw $e;
        }
    }

    private static function logShanjianPublishMediaStatus(string $action, ShanjianVideoTask $media, int $oldStatus, int $newStatus, int $affectedRows, array $extra = []): void
    {
        \think\facade\Log::channel('auto')->write('闪剪发布素材状态变更：' . json_encode(array_merge([
            'action' => $action,
            'video_task_id' => $media->id,
            'device_code' => $media->device_code ?? '',
            'user_id' => $media->user_id ?? null,
            'persona_id' => $media->persona_id ?? null,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'affected_rows' => $affectedRows,
        ], $extra), JSON_UNESCAPED_UNICODE), 'create');
    }
}
