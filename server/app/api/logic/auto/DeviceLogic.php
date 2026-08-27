<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaTrafficConfig;
use app\common\model\auto\AutoDeviceConfig;
use app\common\model\auto\AutoNeedsAnalysis;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\service\FileService;
use app\common\service\UserDisplaySanitizer;


/**
 * 设备自动任务逻辑
 * Class DeviceLogic    
 * @package app\api\logic\auto
 */
class DeviceLogic extends ApiLogic
{
    public static function add(array $params)
    {
        try {
            $params['user_id'] = self::$uid;
            $params['status'] = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;

            $report = AutoNeedsAnalysis::where('device_code', $params['device_code'])->where('user_id', self::$uid)->where('step', 2)->order('id', 'desc')->limit(1)->findOrEmpty();
            if ($report->isEmpty()) {
                throw new \Exception('当前设备分析报告不存在，请稍后再试');
            }

            $reportJson = json_decode($report->result, true);
            if (
                isset($reportJson['Operations']['contentType1']) && !empty($reportJson['Operations']['contentType1']) &&
                isset($reportJson['Operations']['contentType2']) && !empty($reportJson['Operations']['contentType2']) &&
                isset($reportJson['Operations']['contentType3']) && !empty($reportJson['Operations']['contentType3']) &&
                isset($reportJson['Operations']['industryType']) && !empty($reportJson['Operations']['industryType'])

            ) {
                $params['contentType3'] = $reportJson['Operations']['contentType3'];
                $params['contentType2'] = $reportJson['Operations']['contentType2'];
                $params['contentType1'] = $reportJson['Operations']['contentType1'];
                $params['industryType'] = $reportJson['Operations']['industryType'];
            } else {
                throw new \Exception('当前设备分析报告数据异常，请稍后再试');
            }

            if (isset($params['human_image']) && !empty($params['human_image'])) {
                $params['human_image'] = UserDisplaySanitizer::normalizeHumanImageForStorage($params['human_image']);
                $humanImageData = $params['human_image'];
                foreach ($humanImageData as $index => $item) {
                    if (!isset($item['anchor_url']) || empty($item['anchor_url'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id' . $params['user_id'] . '，设备号' . $params['device_code'] . '自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，anchor_url为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (!isset($item['width']) || empty($item['width'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id' . $params['user_id'] . '，设备号' . $params['device_code'] . '自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，width为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (!isset($item['height']) || empty($item['height'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id' . $params['user_id'] . '，设备号' . $params['device_code'] . '自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，height为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (empty($item['chanjing_anchor_id'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id' . $params['user_id'] . '，设备号' . $params['device_code'] . '自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，chanjing_anchor_id为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (empty($item['shanjian_anchor_id'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id' . $params['user_id'] . '，设备号' . $params['device_code'] . '自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，shanjian_anchor_id为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (empty($item['voice_id'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id' . $params['user_id'] . '，设备号' . $params['device_code'] . '自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，voice_id为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (empty($item['shanjian_voice_id'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id' . $params['user_id'] . '，设备号' . $params['device_code'] . '自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，shanjian_voice_id为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                }
                if (count($humanImageData) > 0) {
                    $params['human_image'] = array_values($humanImageData);
                } else {
                    $params['human_image'] = [];
                }
            }
            if (isset($params['clip_material']) && !empty($params['clip_material'])) {
                $clipMaterialData = $params['clip_material'];
                foreach ($clipMaterialData as $index => $item) {
                    if (isset($item['duration']) && $item['duration'] > 59.9) {
                        $errorMsg = '用户id' . $params['user_id'] . '，设备号' . $params['device_code'] . '自动化新增设备配置，clip_material：' . json_encode($params['clip_material'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index;
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        unset($clipMaterialData[$index]);
                    }
                }
                if (count($clipMaterialData) > 0) {
                    $params['clip_material'] = array_values($clipMaterialData);
                } else {
                    $params['clip_material'] = [];
                }
            }
            $find = AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if ($find->status === DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                    throw new \Exception('当前设备自动任务正在执行中，请稍后再试');
                }
                $find->status          = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
                $find->human_image     = $params['human_image'];
                $find->clip_material   = $params['clip_material'];
                $find->image_material  = $params['image_material'];
                $find->clue_theme      = $params['contentType3'] ?? '';
                $find->video_theme     = $params['contentType2'] ?? '';
                $find->text_theme      = $params['contentType1'] ?? '';
                $find->update_time     = time();
                $find->analysis        = json_encode([
                    "contentType1"     => $params['contentType1'] ?? "", //内容类型1
                    "contentType2"     => $params["contentType2"] ?? "", //内容类型2
                    "contentType3"     => $params["contentType3"] ?? "", //内容类型3
                    "industryType"     => $params["industryType"] ?? "", //行业类型
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $find->save();

                $result                 = $find->toArray();
                $analysis               = !empty($find->analysis) ? json_decode($find->analysis, true) : [];
                $result["contentType1"] = $analysis["contentType1"] ?? '';
                $result["contentType2"] = $analysis["contentType2"] ?? '';
                $result["contentType3"] = $analysis["contentType3"] ?? '';
                $result["industryType"] = $analysis["industryType"] ?? ''; //行业类型
                $result['human_image']  = UserDisplaySanitizer::normalizeHumanImageForUser($result['human_image'] ?? []);
                self::$returnData       = $result;
            } else {
                $params['create_time']     = time();
                $params['update_time']     = time();
                $params['clue_theme']      = $params['contentType3'] ?? '';
                $params['video_theme']     = $params['contentType2'] ?? '';
                $params['text_theme']      = $params['contentType1'] ?? '';
                $params['analysis']        = json_encode([
                    "contentType1"     => $params['contentType1'] ?? "", //内容类型1
                    "contentType2"     => $params["contentType2"] ?? "", //内容类型2
                    "contentType3"     => $params["contentType3"] ?? "", //内容类型3
                    "industryType"     => $params["industryType"] ?? "", //行业类型
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $result                 = AutoDeviceConfig::create($params);
                $result                 = $result->toArray();
                $analysis               = !empty($result['analysis']) ? json_decode($result['analysis'], true) : [];
                $result["contentType1"] = $analysis["contentType1"] ?? '';
                $result["contentType2"] = $analysis["contentType2"] ?? '';
                $result["contentType3"] = $analysis["contentType3"] ?? '';
                $result["industryType"] = $analysis["industryType"] ?? ''; //行业类型
                $result['human_image']  = UserDisplaySanitizer::normalizeHumanImageForUser($result['human_image'] ?? []);
                self::$returnData       = $result;
            }
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function detail(array $params)
    {
        try {
            $find = AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                $result                       = $find->toArray();
                $analysis                     = !empty($find->analysis) ? json_decode($find->analysis, true) : [];
                $result["contentType1"]       = $analysis["contentType1"] ?? '';
                $result["contentType2"]       = $analysis["contentType2"] ?? '';
                $result["contentType3"]       = $analysis["contentType3"] ?? '';
                $result['human_image']        = UserDisplaySanitizer::normalizeHumanImageForUser($result['human_image'] ?? []);

                $imageMaterial = $find->image_material;
                if (!empty($imageMaterial)) {
                    if (!is_array($imageMaterial)) {
                        $imageMaterialArray = json_decode($imageMaterial, true) ?: [];
                    } else {
                        $imageMaterialArray = $imageMaterial;
                    }
                    $isOldFormat = false;
                    foreach ($imageMaterialArray as $item) {
                        if (is_string($item)) {
                            $isOldFormat = true;
                            break;
                        }
                    }
                    if ($isOldFormat) {
                        $newImageMaterial = [];
                        foreach ($imageMaterialArray as $url) {
                            $newImageMaterial[] = [
                                'type' => 'image',
                                'cover' => $url,
                                'fileUrl' => $url,
                                'duration' => '2',
                                'status' => '0',
                                'useNumber' => '0',
                            ];
                        }
                        $result['image_material'] = $newImageMaterial;
                    }
                }

                $clipMaterial = $find->clip_material;
                if (!empty($clipMaterial)) {
                    if (!is_array($clipMaterial)) {
                        $clipMaterialArray = json_decode($clipMaterial, true) ?: [];
                    } else {
                        $clipMaterialArray = $clipMaterial;
                    }
                    $imageMaterials = [];
                    $newClipMaterials = [];
                    foreach ($clipMaterialArray as $item) {
                        if (isset($item['type']) && $item['type'] === 'image') {
                            $imageMaterials[] = [
                                'type' => 'image',
                                'cover' => $item['cover'],
                                'fileUrl' => $item['fileUrl'],
                                'duration' => $item['duration'] ?? '2',
                                'status' => $item['status'] ?? '0',
                                'useNumber' => $item['useNumber'] ?? '0',
                            ];
                        } else {
                            $newClipMaterials[] = [
                                'type' => $item['type'],
                                'cover' => $item['cover'],
                                'fileUrl' => $item['fileUrl'],
                                'duration' => $item['duration'] ?? '2',
                                'status' => $item['status'] ?? '0',
                                'useNumber' => $item['useNumber'] ?? '0',
                            ];
                        }
                    }
                    if (!empty($imageMaterials)) {
                        $existingImageMaterial = $result['image_material'] ?? [];
                        if (is_string($existingImageMaterial)) {
                            $existingImageMaterial = json_decode($existingImageMaterial, true) ?: [];
                        }
                        $mergedImageMaterials = array_merge($existingImageMaterial, $imageMaterials);
                        $result['image_material'] = array_values($mergedImageMaterials);
                    }
                    if (!empty($newClipMaterials)) {
                        $result['clip_material'] = array_values($newClipMaterials);
                    }
                }

                self::$returnData             = $result;
                self::$returnData['is_empty'] = 0;
            } else {
                self::$returnData = [
                    'device_code' => $params['device_code'],
                    'human_image' => [],
                    'clip_material' => [],
                    'image_material' => [],
                    'clue_theme' => '',
                    'video_theme' => '',
                    'text_theme' => '',
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                    'is_empty' => 1,
                ];
                self::$returnData["contentType1"]    = '';
                self::$returnData["contentType2"]    = '';
                self::$returnData["contentType3"]    = '';
            }
            list($setting, $task_status, $is_config) = self::getAutoConfigStatus($find);
            self::$returnData['auto_setting'] = $setting;
            self::$returnData['task_status'] = $task_status;
            self::$returnData['is_config'] = $is_config;
            self::$returnData['accounts'] = SvAccount::field('id,account,type')->where('user_id', self::$uid)->where('device_code', $params['device_code'])->select();
            self::$returnData['persona_type'] = AiPersona::where('user_id', self::$uid)->where('id', '=', function ($query) use ($params) {
                $query->name('sv_device')->where('device_code', $params['device_code'])->field('persona_id');
            })->value('persona_type') ?? 0;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }


    public static function checkOpt(array $params)
    {
        try {
            [$params, $account] = self::prepareOptContext($params);

            $payload = self::getPayload($params, $account);
            if (empty($payload)) {
                throw new \Exception('模型数据生成异常');
            }
            $payload = self::withOptMessageId($payload, self::buildOptMessageId());

            $content = json_decode($payload['content'], true);
            if (empty($content)) {
                throw new \Exception('模型数据生成异常');
            }
            if (!array_key_exists('isDemoData', $content)) {
                \think\facade\Log::write('opt payload missing isDemoData: ' . json_encode([
                    'device_code' => $params['device_code'] ?? '',
                    'source' => $params['source'] ?? 0,
                    'messageId' => $payload['messageId'] ?? '',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'warning');
            }

            self::$returnData = [
                'is_demo_data' => (int)($content['isDemoData'] ?? 1),
                'data' => $payload,
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }



    public static function opt(array $params)
    {
        try {
            [$params, $account] = self::prepareOptContext($params);
            $payload = self::getPayload($params, $account);
            if (empty($payload)) {
                throw new \Exception('模型数据生成异常');
            }
            $payload = self::withOptMessageId($payload, self::buildOptMessageId());
            self::publishOptPayload((string)$params['device_code'], $payload);
            self::$returnData = $payload;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function buildPreciseCluesPayloadForTest(array $params): array
    {
        $params['source'] = DeviceEnum::AUTO_TASK_SCENE_PRECISE_CLUES;
        $params['account_type'] = (int)($params['account_type'] ?? DeviceEnum::ACCOUNT_TYPE_DY);
        $params['start_time'] = self::normalizeOptTime($params['start_time'] ?? '03:00', '03:00');
        $params['end_time'] = self::normalizeOptTime($params['end_time'] ?? '06:00', '06:00');

        $account = SvAccount::where('user_id', self::$uid)
            ->where('device_code', $params['device_code'])
            ->where('type', $params['account_type'])
            ->findOrEmpty();
        if ($account->isEmpty()) {
            throw new \Exception('账号不存在');
        }

        $payload = self::getPayload($params, $account);
        if (empty($payload)) {
            throw new \Exception('模型数据生成异常');
        }

        return $payload;
    }

    private static function prepareOptContext(array $params): array
    {
        $params['source'] = (int)$params['source'];
        $params['account_type'] = (int)$params['account_type'];
        $params['start_time'] = self::normalizeOptTime($params['start_time'] ?? '03:00', '03:00');
        $params['end_time'] = self::normalizeOptTime($params['end_time'] ?? '06:00', '06:00');

        $account = SvAccount::where('user_id', self::$uid)
            ->where('device_code', $params['device_code'])
            ->where('type', $params['account_type'])
            ->findOrEmpty();
        if ($account->isEmpty()) {
            throw new \Exception('账号不存在');
        }

        \think\facade\Cache::store('redis')->handler()->select(env('redis.WS_SELECT', 8));
        $status = \think\facade\Cache::store('redis')->handler()->get("xhs:device:{$params['device_code']}:status");
        if (self::decodeDeviceStatus($status) !== 'online') {
            //throw new \Exception('设备未上线');
        }

        return [$params, $account];
    }

    private static function decodeDeviceStatus(mixed $status)
    {
        if (!is_string($status)) {
            return $status;
        }

        if ($status === 'online') {
            return $status;
        }

        $decoded = @unserialize($status, ['allowed_classes' => false]);
        if ($decoded !== false || $status === 'b:0;') {
            return $decoded;
        }

        return $status;
    }

    /**
     * 设备是否在线。状态键由 workerman RPA 连接生命周期维护（连接/心跳=online，断开=offline），
     * 指令经 Channel 实时推送，离线设备收不到，下发前应校验。读取后恢复缓存连接原 db，避免 select 泄漏。
     */
    public static function isDeviceOnline(string $deviceCode): bool
    {
        // 写入侧（ConnectionRepository::normalizeDeviceId）会 trim，读取侧保持一致
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }

        $handler = \think\facade\Cache::store('redis')->handler();
        $prevDb = method_exists($handler, 'getDbNum')
            ? (int)$handler->getDbNum()
            : (int)env('CACHE.SELECT', 2);
        try {
            $handler->select((int)env('redis.WS_SELECT', 8));
            $status = $handler->get("xhs:device:{$deviceCode}:status");
        } finally {
            $handler->select($prevDb);
        }

        return self::decodeDeviceStatus($status) === 'online';
    }

    private static function buildOptMessageId(): string
    {
        return 0;
    }

    private static function withOptMessageId(array $payload, string $messageId): array
    {
        $payload['messageId'] = $messageId;
        return $payload;
    }

    public static function publishOptPayload(string $deviceCode, array $payload): string
    {
        $channel = "device.{$deviceCode}.message";
        try {
            $data = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (false === $data) {
                throw new \RuntimeException(json_last_error_msg());
            }
            \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            \Channel\Client::publish($channel, [
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            \think\facade\Log::write('opt publish failed: ' . $th->getMessage(), 'error');
            throw new \Exception('指令下发失败');
        }

        return $channel;
    }

    public static function normalizeOptTime(mixed $value, string $default): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return $default;
        }

        if (preg_match('/^(\d{1,2}):(\d{1,2})(?::\d{1,2})?$/', $value, $matches)) {
            $hour = max(0, min(23, (int)$matches[1]));
            $minute = max(0, min(59, (int)$matches[2]));
            return sprintf('%02d:%02d', $hour, $minute);
        }

        $timestamp = strtotime($value);
        if (false === $timestamp) {
            return $default;
        }

        return date('H:i', $timestamp);
    }

    private static function normalizeTakeOverCommentSpeech(mixed $commentSpeech): array
    {
        if (is_string($commentSpeech)) {
            $decoded = json_decode($commentSpeech, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($commentSpeech) ? $commentSpeech : [];
    }

    private static function normalizeArrayValue(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    private static function getPayload(array $params, SvAccount $account): array
    {
        $st = date("Y-m-d {$params['start_time']}:00", time());
        $et = date("Y-m-d {$params['end_time']}:00", time());

        $task = SvDeviceTask::where('user_id', self::$uid)
            ->where('device_code', $params['device_code'])
            ->where('auto_type', '=', function ($query) use ($params) {
                $query->name('sv_device')->where('device_code', $params['device_code'])->field('auto_type');
            })
            ->where('account_type', $params['account_type'])
            ->where('task_scene', $params['source'])
            ->where('start_time', '<=', strtotime($et))
            ->where('end_time', '>=', strtotime($st))
            ->order('id', 'desc')
            ->findOrEmpty();
            //print_r(\think\facade\Db::getLastSql());die;
        $payload = [];
        switch ((int)$params['source']) {
            case DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH:
                $find = self::getAutoPublishImageTask($params, $account, $task);
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => 5,
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'content' => json_encode([
                        'publish_platform' => $account->type,
                        'material_id' => $find['id'],
                        'auto_type' => $find['auto_type'],
                        'title' => $find['material_title'],
                        'type' => $find['material_type'],
                        'list' => $find['material_url'],
                        'isLocation' => !empty($find['poi']) ? 1 : 0,
                        'pic' => $find['pic'],
                        'location' => $find['poi'],
                        'isScheduledTime' => true,
                        'scheduledTime' => $find['publish_time'],
                        'taskId' => $task->id,
                        'body' => $find['material_subtitle'],
                        'tag' => $find['material_tag'],
                        'isSend' => 0,
                        'isDemoData' => $find['is_demo_data'],
                    ], JSON_UNESCAPED_UNICODE),

                );
                break;

            case DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE:
                $find = self::getAutoCluesTask($params, $account, $task);

                $payload = [
                    'type' => 20,
                    'appType' => DeviceEnum::ACCOUNT_TYPE_SPH,
                    'content' => json_encode([
                        'id' => $find['id'],
                        'task_id' => $task->sub_task_id ?? 0,
                        'auto_type' => 0,
                        'platform' => DeviceEnum::getAccountTypeDesc((int)$account->type),
                        'task_type' => 'auto',
                        'device_code' => $find['device_code'] ?? $params['device_code'],
                        'keywords' => self::normalizeArrayValue($find['keywords'] ?? []),
                        'exec_number' => 10000,
                        'is_chat' => $find['chat_type'] ?? 0,
                        'chat_number' => $find['chat_number'] ?? 10,
                        'chat_interval_time' => $find['chat_interval_time'] ?? 10,
                        'add_type' => $find['add_type'] ?? 0,
                        'remarks' => self::normalizeArrayValue($find['remarks'] ?? []),
                        'add_remark_enable' => $find['add_remark_enable'] ?? 0,
                        'add_number' => $find['add_number'] ?? 10,
                        'add_interval_time' => $find['add_interval_time'] ?? 10,
                        'greeting_content' => $find['greeting_content'] ?? '',
                        'status' => 0,
                        'ocr_type' => $find['ocr_type'] ?? 1,
                        'crawl_type' => $find['crawl_type'] ?? 1,
                        'create_time' => time(),
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'time_interval' => 30,
                        'isSend' => 0,
                        'isDemoData' => $find['is_demo_data'],
                    ], JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,

                ];
                break;
            case DeviceEnum::AUTO_TASK_SCENE_TAKE_OVER:
                $find = self::getAutoTakeOverTask($params, 2, $account, $task);
                $payloadAccount = $task->isEmpty() ? ($account->account ?? '') : ($task->account ?? ($account->account ?? ''));
                $payloadAccountType = (int)($task->isEmpty() ? ($account->type ?? 0) : ($task->account_type ?? ($account->type ?? 0)));
                $commentSpeech = self::normalizeTakeOverCommentSpeech($find['comment_speech'] ?? []);
                $payload = array(
                    'type' => DeviceEnum::getTakeOverType($account->type), // 接管任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => $find['task_id'] ?? 0,
                        'task_type' => 2,
                        'deviceId' => $params['device_code'],
                        'account' => $payloadAccount,
                        'account_type' => $payloadAccountType,
                        'content' => '自动私信模拟发送内容',
                        'auto_type' => 1,
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'time_interval' => 30,
                        'comment_type' => $payloadAccountType === DeviceEnum::ACCOUNT_TYPE_SPH ? 3 : ($find['comment_type'] ?? 1),
                        'comment_speech' => $commentSpeech,
                        'msg' => '接管任务运行',
                        'isSend' => 0,
                        'isDemoData' => $find['is_demo_data'] ?? 1,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT:
                $find = self::getAutoTouchTask($params, 1, $account, $task);
                $payload = array(
                    'type' => DeviceEnum::TASK_COMMENT_TO_COMMENT, // 评论区评论任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => $task['sub_task_id'] ?? 0,
                        'auto_type' => 1,
                        'deviceId' => $params['device_code'],
                        'account' => $task->account ?? ($account->account ?? ''),
                        'account_type' => $task->account_type ?? $params['account_type'],
                        'startTime' => time(),
                        'endTime' => time() + 60 * 30,
                        'timeInterval' => 30,
                        'keyword' => $find['industry'],
                        'hasLiked' => $find['is_like'],
                        'hasFollowed' => $find['is_follow'],
                        'commentContents' => $find['content'],
                        'filteredKeywords' => $find['filter'],
                        'commentCount' => $find['send_num'],
                        'dmCount' => $find['send_num'],
                        'noteViewCount' => $find['industry_num'],
                        'industry_type' => $find['industry_type'],
                        'city' => $find['city'],
                        'is_content_author' => $find['is_content_author'],
                        'is_execed_clues' => $find['is_execed_clues'],
                        'is_touch_like' => $find['is_like'],
                        'is_touch_follow' => $find['is_follow'],
                        'content_publish_day' => $find['content_publish_day'] ?? 0,
                        'comment_publish_day' => $find['comment_publish_day'] ?? 0,
                        'ip_address' => $find['ip_address'] ?? [],
                        'is_note_like' => $find['is_like'] ?? 0,
                        'msg' => '评论区评论任务运行',
                        'is_send' => 0,
                        'isDemoData' => $find['is_demo_data'],
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,

                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG:
                $find = self::getAutoTouchTask($params, 2, $account, $task);
                $payload = array(
                    'type' => DeviceEnum::TASK_COMMENT_TO_MSG, // 评论区评论任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => $task['sub_task_id'] ?? 0,
                        'auto_type' => $find['auto_type'],
                        'deviceId' => $params['device_code'],
                        'account' => $task->account ?? ($account->account ?? ''),
                        'account_type' => $task->account_type ?? $params['account_type'],
                        'startTime' => time(),
                        'endTime' => time() + 60 * 30,
                        'timeInterval' => 30,
                        'keyword' => $find['industry'],
                        'hasLiked' => $find['is_like'],
                        'hasFollowed' => $find['is_follow'],
                        'commentContents' => $find['content'],
                        'filteredKeywords' => $find['filter'],
                        'commentCount' => $find['send_num'],
                        'dmCount' => $find['send_num'],
                        'noteViewCount' => $find['industry_num'],
                        'industry_type' => $find['industry_type'],
                        'city' => $find['city'],
                        'is_content_author' => $find['is_content_author'],
                        'is_execed_clues' => $find['is_execed_clues'],
                        'is_touch_like' => $find['is_like'],
                        'is_touch_follow' => $find['is_follow'],
                        'content_publish_day' => $find['content_publish_day'] ?? 0,
                        'comment_publish_day' => $find['comment_publish_day'] ?? 0,
                        'ip_address' => $find['ip_address'] ?? [],
                        'is_note_like' => $find['is_like'] ?? 0,
                        'msg' => '评论区私信任务运行',
                        'is_send' => 0,
                        'isDemoData' => $find['is_demo_data'],
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,

                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE:
                $find = self::getAutoTouchTask($params, 3, $account, $task);
                $payload = array(
                    'type' => DeviceEnum::TASK_COMMENT_TO_MARK_CLUE, // 评论区评论任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => $task['sub_task_id'] ?? 0,
                        'auto_type' => $find['auto_type'],
                        'deviceId' => $params['device_code'],
                        'account' => $task->account ?? ($account->account ?? ''),
                        'account_type' => $task->account_type ?? $params['account_type'],
                        'startTime' => time(),
                        'endTime' => time() + 60 * 30,
                        'timeInterval' => 30,
                        'keyword' => $find['industry'],
                        'hasLiked' => $find['is_like'],
                        'hasFollowed' => $find['is_follow'],
                        'commentContents' => $find['content'],
                        'filteredKeywords' => $find['filter'],
                        'commentCount' => $find['send_num'],
                        'dmCount' => $find['send_num'],
                        'noteViewCount' => $find['industry_num'],
                        'industry_type' => $find['industry_type'],
                        'city' => $find['city'],
                        'is_content_author' => $find['is_content_author'],
                        'is_execed_clues' => $find['is_execed_clues'],
                        'is_touch_like' => $find['is_like'],
                        'is_touch_follow' => $find['is_follow'],
                        'content_publish_day' => $find['content_publish_day'] ?? 0,
                        'comment_publish_day' => $find['comment_publish_day'] ?? 0,
                        'ip_address' => $find['ip_address'] ?? [],
                        'is_note_like' => $find['is_like'] ?? 1,
                        'is_note_comment' =>  $find['is_note_comment'] ?? 1, //评论作品
                        'is_note_collect' =>  $find['is_note_collect'] ?? 1, //收藏作品
                        'msg' => '评论区留痕任务运行',
                        'is_send' => 0,
                        'isDemoData' => $find['is_demo_data'],
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,

                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_FRIENDS:
                $sendWechatIds = self::getAutoFriendTask($params, $account, $task);
                $payload = array(
                    'type' => DeviceEnum::RPA_ADD_WECHAT, // 接管任务启动
                    'appType' => 0,
                    'content' => json_encode(array(
                        'task_id' => $task['id'] ?? 0,
                        'auto_type' => 1,
                        'deviceId' => $params['device_code'],
                        'account' => $task->account ?? ($account->account ?? ''),
                        'account_type' => $task->account_type ?? $params['account_type'],
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'time_interval' => 30,
                        'send_wechat_ids' => $sendWechatIds,
                        'add_interval_time' => 10,
                        'msg' => '加微任务运行',
                        'isSend' => 0,
                        'isDemoData' => count($sendWechatIds) > 1 ? 0 : 1,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,

                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_ACTIVE:
                $payload = array(
                    'type' => DeviceEnum::getMaintainAccountType($account->type), // 养号任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => 0,
                        'auto_type' => 1,
                        'deviceId' => $params['device_code'],
                        'account' => $task->account ?? ($account->account ?? ''),
                        'account_type' => $task->account_type ?? $params['account_type'],
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'time_interval' => 30,
                        'msg' => '养号任务运行',
                        'isSend' => 0,
                        'isDemoData' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH:

                $find = self::getAutoPublishCircleTask($params, $account, $task);
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => 5,
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'content' => json_encode([
                        'publish_platform' => 2,
                        'material_id' => $find['material_id'],
                        'title' => $find['title'],
                        'type' => $find['type'],
                        'list' => $find['list'],
                        'isLocation' => 0,
                        'location' => '',
                        'isScheduledTime' => true,
                        'scheduledTime' => $find['send_time'] ?? date('Y-m-d H:i:s', time()),
                        'taskId' => $task->id ?? 0,
                        'body' => $find['body'],
                        'tag' => $find['tag'] ?? '',
                        'comment' => $find['comment'] ?? '',
                        'isSend' => 0,
                        'isDemoData' => $find['is_demo_data'],
                    ], JSON_UNESCAPED_UNICODE)
                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_THUMB_COMMENT:
                $find = self::getAutoCircleLikeCommentTask($params, 1, $account, $task);
                $payload = array(
                    'appType' => 1,
                    'messageId' => 0,
                    'type' => DeviceEnum::WECHAT_CIRCLE_LIKE_COMMENT,
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'content' => json_encode([
                        'taskId' => $find['taskId'],
                        "hasLiked" => $find['hasLiked'],  //点赞
                        "hasComment" => $find['hasComment'],  //评论
                        "planCoverage" => $find['planCoverage'],  //当天   1、3天内   2、7天内
                        "interactionConut" => $find['interactionConut'],  //互动数量
                        "timeInterval" => $find['timeInterval'],  //互动间隔/分钟
                        "commentType" => $find['commentType'],  //AI识别并评论   1、不评论   2、固定评论
                        "commentContent" =>  $find['commentContent'],  //固定评论内容
                        'account' => $task->account ?? ($account->account ?? ''),
                        'account_type' => $task->account_type ?? $params['account_type'],
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'time_interval' => 30,
                        'isDemoData' => $find['is_demo_data'],

                    ], JSON_UNESCAPED_UNICODE)
                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_COMMENT_TAKE_OVER:
                $find = self::getAutoTakeOverTask($params, 1, $account, $task);
                $payloadAccount = $task->isEmpty() ? ($account->account ?? '') : ($task->account ?? ($account->account ?? ''));
                $payloadAccountType = (int)($task->isEmpty() ? ($account->type ?? 0) : ($task->account_type ?? ($account->type ?? 0)));
                $commentSpeech = self::normalizeTakeOverCommentSpeech($find['comment_speech'] ?? []);
                $payload = array(
                    'type' => DeviceEnum::getTakeOverType($account->type), // 接管任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => $find['task_id'] ?? 0,
                        'task_type' => 1,
                        'deviceId' => $params['device_code'],
                        'account' => $payloadAccount,
                        'account_type' => $payloadAccountType,
                        'content' => '自动评论接管模拟发送内容',
                        'auto_type' => 1,
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'time_interval' => 30,
                        'comment_type' => $payloadAccountType === DeviceEnum::ACCOUNT_TYPE_SPH ? 3 : ($find['comment_type'] ?? 1),
                        'comment_speech' => $commentSpeech,
                        'msg' => '接管任务运行',
                        'isSend' => 0,
                        'isDemoData' => $find['is_demo_data'] ?? 1,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_EXPOSURE:
                $find = self::getSameCityExposureTask($params, $account, $task);
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => DeviceEnum::TASK_SAME_CITY_EXPOSURE,
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'content' => json_encode([
                        'taskId' => $task['sub_task_id'] ?? 0,
                        'radius' => $find['radius'],
                        'interval_time' => $find['interval_time'],
                        'visit_num' => $find['visit_num'],
                        'account_feature' => $find['account_feature'],
                        'account' => $task->account ?? ($account->account ?? ''),
                        'account_type' => $task->account_type ?? $params['account_type'],
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'msg' => '同城曝光任务运行',
                        'isDemoData' => $find['is_demo_data'],

                    ], JSON_UNESCAPED_UNICODE)
                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF:
                $find = self::getSameCityCutoffTask($params, $account, $task);
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => DeviceEnum::TASK_SAME_CITY_CUTOFF,
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'content' => json_encode([
                        'taskId' => $task['sub_task_id'] ?? 0,
                        'task_type' => $find['task_type'], //1 评论 2 私信
                        'radius' => $find['radius'],
                        'account_feature' => $find['account_feature'],
                        'marker_method' => $find['marker_method'],
                        'chat_type' => $find['chat_type'],
                        'interval_time' => $find['interval_time'],
                        'watch_time' => $find['watch_time'],
                        'gender' => $find['gender'],
                        'old' => $find['old'],
                        'region' => $find['region'],
                        'city' => $find['city'],
                        'send_num' => $find['send_num'],
                        'like_num' => $find['like_num'],
                        'comment_num' => $find['comment_num'],
                        'comment_fans_num' => $find['comment_fans_num'],
                        'comment_follow_num' => $find['comment_follow_num'],
                        'filter' => $find['filter'],
                        'nickname_filter' => $find['nickname_filter'],
                        'comment_speech' => $find['comment_speech'],
                        'message_speech' => $find['message_speech'],
                        'account' => $task->account,
                        'account_type' => $task->account_type,
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'msg' => '同城视频截流任务运行',
                        'isDemoData' => $find['is_demo_data'],

                    ], JSON_UNESCAPED_UNICODE)
                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_GROUP_BUY:
                $find = self::getGroupBuyTask($params, $account, $task);
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => DeviceEnum::TASK_GROUP_BUY,
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'content' => json_encode([
                        'taskId' => $task['sub_task_id'] ?? 0,
                        'task_type' => $find['task_type'],
                        'account_feature' => $find['account_feature'],
                        'marker_method' => $find['marker_method'],
                        'chat_type' => $find['chat_type'],
                        'like_type' => $find['like_type'],
                        'group_type' => $find['group_type'],
                        'send_num' => $find['send_num'],
                        'radius' => $find['radius'],
                        'interval_time' => $find['interval_time'],
                        'watch_time' => $find['watch_time'],
                        'content_publish_day' => $find['content_publish_day'],
                        'comment_offset' => $find['comment_offset'],
                        'gender' => $find['gender'],
                        'old' => $find['old'],
                        'region' => $find['region'],
                        'city' => $find['city'],
                        'comment_keyword' => $find['comment_keyword'],
                        'filter' => $find['filter'],
                        'nickname_filter' => $find['nickname_filter'],
                        'comment_speech' => $find['comment_speech'],
                        'message_speech' => $find['message_speech'],
                        'account' => $task->account,
                        'account_type' => $task->account_type,
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'time_interval' => 30,
                        'msg' => '团购任务运行',
                        'isDemoData' => $find['is_demo_data'],

                    ], JSON_UNESCAPED_UNICODE)
                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_COMMENT_LIKE:
                $find = self::getSphAutoTakeOverTask($params, 3, $account, $task);
                $payload = array(
                    'type' => DeviceEnum::SPH_TAKE_THUMB, // 视频号点赞任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => $task->sub_task_id,
                        'auto_type' => 1,
                        'account' => $task->account,
                        'account_type' => $task->account_type,
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'msg' => '视频号点赞任务运行',
                        'isDemoData' => $find['is_demo_data'],
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_VIRAL_REWRITER:
                $find = self::getViralRewriterTask($params, $account, $task);
                $payload = array(
                    'type' => DeviceEnum::TASK_VIRAL_REWRITER, // 接管任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'taskId' => $find['sub_task_id'] ?? 0,
                        'auto_type' => 1,
                        'keywords' => $find['keywords'],
                        'account' => $task->account,
                        'account_type' => $task->account_type,
                        'publish_platform' => (int)($find['publish_platform'] ?? $params['account_type']),
                        'publish_media_type' => (int)($find['publish_media_type'] ?? 1),
                        'duration' => (int)($find['duration'] ?? 0),
                        'publish_day' => (int)($find['publish_day'] ?? 0),
                        'tracking_mode' => \app\common\model\aiPersona\AiPersona::normalizeTrackingMode($find['tracking_mode'] ?? \app\common\model\aiPersona\AiPersona::TRACKING_MODE_AUTO),
                        'tracking_account_config' => \app\common\model\aiPersona\AiPersona::normalizeTrackingAccountConfig($find['tracking_account_config'] ?? []),
                        'custom_date' => date('Y-m-d', time()),
                        'start_time' => time(),
                        'end_time' => time() + 3600,
                        'msg' => '爆款仿写任务运行',
                        'isDemoData' => $find['is_demo_data'],
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_TASK_SCENE_PRECISE_CLUES:
                $find = self::getPreciseCluesTask($params, $account, $task);
                $payload = array(
                    'type' => DeviceEnum::TASK_PRECISE_CLUES, // 精准线索任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'taskId' => $find['sub_task_id'] ?? $task->sub_task_id ?? 0,
                        'taskAccountId' => $find['task_account_id'] ?? 0,
                        'auto_type' => 1,
                        'account' => $task->account ?? $account->account ?? '',
                        'account_type' => $task->account_type ?? $params['account_type'],
                        'start_time' => time(),
                        'end_time' => time() + 60 * 30,
                        'clues' => $find['clues'],
                        'all_clues' => $find['all_clues'] ?? $find['clues'],
                        'round_no' => $find['round_no'] ?? 1,
                        'mention_limit' => $find['mention_limit'] ?? 10,
                        'wait_seconds' => $find['wait_seconds'] ?? 600,
                        'total_count' => $find['total_count'] ?? count($find['clues']),
                        'touched_count' => $find['touched_count'] ?? 0,
                        'remaining_count' => $find['remaining_count'] ?? count($find['clues']),
                        'msg' => '精准线索任务运行',
                        'isDemoData' => $find['is_demo_data'],
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                    'messageId' => 0,
                );
                break;
            default:
                throw new \Exception('任务类型不存在');
        }


        return $payload;
    }

    private static function getPreciseCluesTask(array $params, SvAccount $account, SvDeviceTask $task){
        $payload = [
            'sub_task_id' => 0,
            'clues' => [
                '6153269008',
                '62312194741',
                '77485826968'
            ],
            'all_clues' => [
                '6153269008',
                '62312194741',
                '77485826968'
            ],
            'task_account_id' => 0,
            'mention_limit' => 10,
            'wait_seconds' => 600,
            'total_count' => 2,
            'touched_count' => 0,
            'remaining_count' => 2,
            'is_demo_data' => 1,
        ];

        $find = \app\common\model\sv\SvDevicePreciseClues::alias('ps')
                ->field('ps.*,s.id as task_account_id,s.clues')
                ->join('sv_device_precise_clues_account s', 's.precise_clues_id = ps.id')
                ->where('ps.id', $task->sub_task_id)
                ->where('s.device_code', '=', $task->device_code)
                ->where('s.account_type', $task->account_type)
                ->limit(1)
                ->findOrEmpty();
        if ($find->isEmpty()) {
            return $payload;
        }
        $allClues = self::normalizePreciseClues($find->clues);
        $touchedUserIds = \app\common\model\sv\SvDevicePreciseCluesRecord::where('precise_clues_account_id', $find->task_account_id)
            ->where('status', 1)
            ->whereNull('delete_time')
            ->group('target_user_id')
            ->column('target_user_id');
        $roundNo = max(1, (int)\app\common\model\sv\SvDevicePreciseCluesRecord::where('precise_clues_account_id', $find->task_account_id)
            ->whereNull('delete_time')
            ->max('round_no') + 1);
        $remainingClues = array_values(array_filter($allClues, function ($item) use ($touchedUserIds) {
            return !in_array($item, $touchedUserIds, true);
        }));
        if (count($remainingClues) <= 0) {
            return $payload;
        }

        $find->all_clues = $remainingClues;
        $find->clues = array_slice($remainingClues, 0, 10);
        $find->round_no = $roundNo;
        $find->mention_limit = 10;
        $find->wait_seconds = 600;
        $find->total_count = count($allClues);
        $find->touched_count = count(array_intersect($allClues, $touchedUserIds));
        $find->remaining_count = count($remainingClues);
        $find->sub_task_id = $find->id;
        $find->is_demo_data = 0;
        return $find->toArray();
    }

    private static function normalizePreciseClues(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $clues = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $item = $item['target_user_id']
                    ?? $item['targetUserId']
                    ?? $item['douyin_user_id']
                    ?? $item['douyinUserId']
                    ?? $item['douyin_id']
                    ?? $item['douyinId']
                    ?? $item['sec_uid']
                    ?? $item['secUid']
                    ?? $item['uid']
                    ?? $item['user_id']
                    ?? $item['userId']
                    ?? $item['account']
                    ?? $item['id']
                    ?? '';
            }

            $item = trim((string)$item);
            if ($item !== '') {
                $clues[] = $item;
            }
        }

        return array_values(array_unique($clues));
    }

    private static function getViralRewriterTask(array $params, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'sub_task_id' => 0,
            'keywords' => [
                '爆款',
                '仿写',
            ],
            'custom_date' => date('Y-m-d', time()),
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            $persona = AiPersona::where('id', '=', function ($query) use ($params) {
                $query->name('sv_device')->where('device_code', $params['device_code'])->field('persona_id');
            })->findOrEmpty();

            $rule = null;
            if ($persona->persona_type == 1) {
                $rule = $persona->individual;
            } elseif ($persona->persona_type == 2) {
                $rule = $persona->enterprise;
            } elseif ($persona->persona_type == 3) {
                $rule = $persona->local;
            }
            $response = \app\common\service\ToolsService::Coze()->getHotWords([
                'keywords' => $rule->getClueContent($persona),
            ]);
            $payload['keywords'] = $response['data']['content'] ?? [];
            $payload['tracking_mode'] = \app\common\model\aiPersona\AiPersona::normalizeTrackingMode($persona->tracking_mode ?? \app\common\model\aiPersona\AiPersona::TRACKING_MODE_AUTO);
            $payload['duration'] = \app\common\model\aiPersona\AiPersona::normalizeTrackingDuration($persona->duration ?? \app\common\model\aiPersona\AiPersona::TRACKING_DURATION_DEFAULT);
            $payload['publish_day'] = \app\common\model\aiPersona\AiPersona::normalizeTrackingFilterValue($persona->publish_day ?? 0);
            $payload['tracking_account_config'] = \app\common\model\aiPersona\AiPersona::normalizeTrackingAccountConfig($persona->tracking_account_config ?? []);
            $payload['publish_media_type'] = (int)$params['account_type'] === 3 ? 2 : 1;
            return $payload;
        }

        $find = \app\common\model\sv\SvDeviceViral::alias('ps')
            ->field('ps.*,IF(s.publish_platform > 0, s.publish_platform, s.account_type) as publish_platform,IF(s.publish_media_type > 0, s.publish_media_type, IFNULL(ps.publish_media_type, 1)) as publish_media_type,s.duration as duration,s.publish_day as publish_day,p.tracking_mode,p.tracking_account_config')
            ->join('sv_device_viral_account s', 's.viral_id = ps.id')
            ->join('ai_persona p', 'p.id = ps.persona_id', 'left')
            ->where('ps.id', $task->sub_task_id)
            ->where('s.device_code', '=', $task->device_code)
            ->where('s.account_type', $task->account_type)
            ->limit(1)
            ->findOrEmpty();
            //print_r(\think\facade\Db::getLastSql());die;
        if ($find->isEmpty()) {
            return $payload;
        }
        $find->sub_task_id = $find->id;
        $find->is_demo_data = 0;
        return $find->toArray();
    }

    private static function getSameCityExposureTask(array $params, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'radius' => 5,
            'interval_time' => 10,
            'visit_num' => 100,
            'account_feature' => 1,
            'sub_task_id' => 0,
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

        $find = \app\common\model\sv\SvCityExposureTask::alias('ps')
            ->field('ps.*,s.id as sub_task_id')
            ->join('sv_city_exposure_task_account s', 's.city_exposure_id = ps.id')
            ->where('s.id', $task->sub_task_id)
            ->where('s.device_code', '=', $task->device_code)
            ->where('s.account', $task->account)
            ->where('s.account_type', $task->account_type)
            //->where('ps.material_type', 2)
            ->order('s.create_time desc')
            ->limit(1)
            ->findOrEmpty();
        if ($find->isEmpty()) {
            return $payload;
        }

        $find->is_demo_data = 0;
        return $find->toArray();
    }

    private static function getSameCityCutoffTask(array $params, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'radius' => 5,
            'account_feature' => 1,
            'marker_method' => [1, 2, 3],
            'task_type' => 1,
            'chat_type' => 1,
            'interval_time' => 10,
            'watch_time' => 10,
            'gender' => '不限',
            'old' => '不限',
            'region' => '不限',
            'city' => '不限',
            'send_num' => 100,
            'like_num' => 100,
            'comment_num' => 100,
            'comment_fans_num' => 100,
            'comment_follow_num' => 100,
            'filter' => [
                '?'
            ],
            'nickname_filter' => [],

            'comment_speech' => [
                '您好！'
            ],
            'message_speech' => [
                '您好！'
            ],
            'sub_task_id' => 0,
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

        $find = \app\common\model\sv\SvCityTouchTask::alias('ps')
            ->field('ps.*,s.id as sub_task_id')
            ->join('sv_city_touch_task_account s', 's.city_touch_id = ps.id')
            ->where('s.id', $task->sub_task_id)
            ->where('s.device_code', '=', $task->device_code)
            ->where('s.account', $task->account)
            ->where('s.account_type', $task->account_type)
            //->where('ps.material_type', 2)
            ->order('s.create_time desc')
            ->limit(1)
            ->findOrEmpty();
        if ($find->isEmpty()) {
            return $payload;
        }


        $setting = \app\common\model\aiPersona\AiPersonaAgentConfig::where('persona_id', $find->persona_id)->findOrEmpty();
        if ($setting->isEmpty()) {
            return $payload;
        }
        $find->comment_speech = $setting->shutoff_comment_speech;
        $find->message_speech = $setting->shutoff_msg_speech;
        $find->task_type = in_array(3, $find->marker_method) ? 1 : 2;
        $find->is_demo_data = 0;
        return $find->toArray();
    }

    private static function getGroupBuyTask(array $params, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'account_feature' => 1,
            'marker_method' => [1, 2, 4],
            'task_type' => 2,
            'chat_type' => 1,
            'like_type' => 1,
            'group_type' => 1,
            'send_num' => 100,
            'radius' => 5,
            'interval_time' => 10,
            'watch_time' => 10,
            'content_publish_day' => 1,
            'comment_offset' => 1,
            'gender' => '不限',
            'old' => '不限',
            'region' => '不限',
            'city' => '不限',
            'comment_keyword' => [],
            'filter' => [
                '?'
            ],
            'nickname_filter' => [],
            'comment_speech' => [
                '您好！'
            ],
            'message_speech' => [
                '您好！'
            ],
            'sub_task_id' => 0,
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

        $find = \app\common\model\sv\SvGroupBuyTask::alias('ps')
            ->field('ps.*,s.id as sub_task_id')
            ->join('sv_group_buy_task_account s', 's.group_buy_id = ps.id')
            ->where('s.id', $task->sub_task_id)
            ->where('s.device_code', '=', $task->device_code)
            ->where('s.account', $task->account)
            ->where('s.account_type', $task->account_type)
            //->where('ps.material_type', 2)
            ->order('s.create_time desc')
            ->limit(1)
            ->findOrEmpty();
        if ($find->isEmpty()) {
            return $payload;
        }


        $setting = \app\common\model\aiPersona\AiPersonaAgentConfig::where('persona_id', $find->persona_id)->findOrEmpty();
        if ($setting->isEmpty()) {
            return $payload;
        }
        $find->comment_speech = $setting->shutoff_comment_speech;
        $find->message_speech = $setting->shutoff_msg_speech;
        $find->task_type = in_array(3, $find->marker_method) ? 1 : 2;
        $find->is_demo_data = 0;
        return $find->toArray();
    }

    private static function getAutoPublishVideoTask(array $params, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'publish_platform' => $account->type,
            'id' => 0,
            'auto_type' => 1,
            'material_title' => '视频发布模拟发布标题',
            'material_type' => 1,
            'material_url' => [
                'https://demo.imai.work/uploads/demo/2.mp4'
            ],
            'poi' => 0,
            'publish_time' => date('Y-m-d H:i:s', time()),
            'task_id' => 0,
            'material_subtitle' => '视频发布模拟发布内容',
            'material_tag' => '#视频模拟发布',
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

        $publish = \app\common\model\sv\SvPublishSettingDetail::alias('ps')
            ->field('ps.*')
            ->join('sv_publish_setting_account s', 's.id = ps.publish_account_id')
            ->where('s.id', $task->sub_task_id)
            ->where('ps.device_code', '=', $task->device_code)
            ->where('ps.account', $task->account)
            ->where('s.account_type', $task->account_type)
            //->where('ps.material_type', 1)
            ->where('ps.data_type', 0)
            ->order('ps.publish_time desc')
            ->limit(1)
            ->findOrEmpty();
        if ($publish->isEmpty()) {
            return $payload;
        }
        $publish->material_url = explode(',', $publish->material_url);
        $publish->id = 0;
        $publish->is_demo_data = 0;
        return $publish->toArray();
    }

    private static function getAutoPublishImageTask(array $params, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'publish_platform' => $account->type,
            'id' => 0,
            'auto_type' => 1,
            'material_title' => '同一只白团子，三种心情',
            'material_type' => 2,
            'material_url' => [
                'https://demo.imai.work/uploads/demo/1.png',
                'https://demo.imai.work/uploads/demo/2.png',
                'https://demo.imai.work/uploads/demo/3.png'
            ],
            'pic' => '',
            'poi' => 0,
            'publish_time' => date('Y-m-d H:i:s', time()),
            'task_id' => 0,
            'material_subtitle' => '气球庆祝、阳光笑脸、雪地安睡',
            'material_tag' => '',
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

        $publish = \app\common\model\sv\SvPublishSettingDetail::alias('ps')
            ->field('ps.*')
            ->join('sv_publish_setting_account s', 's.id = ps.publish_account_id')
            ->where('s.id', $task->sub_task_id)
            ->where('ps.device_code', '=', $task->device_code)
            ->where('ps.account', $task->account)
            ->where('s.account_type', $task->account_type)
            //->where('ps.material_type', 2)
            ->where('ps.data_type', 0)
            //->where('ps.status', '<>', 2)
            ->order('ps.publish_time desc')
            ->limit(1)
            ->findOrEmpty();
        if ($publish->isEmpty()) {
            return $payload;
        }
        $persona = \app\common\model\aiPersona\AiPersona::where('id', $task->persona_id)->findOrEmpty();
        if ($persona->isEmpty()) {
            return $payload;
        }
        
        $contentPublishConfig = \app\common\model\aiPersona\AiPersona::normalizeContentPublishConfig($persona['content_publish_config'] ?? []);
        $canUseLocation = in_array($task->account_type, [3, 4]);
        $locationConfig = $contentPublishConfig['platform_configs'][$task->account_type] ?? [
            'is_content_location' => 0,
            'content_location' => '',
        ];
        $publish->poi = $canUseLocation && (int)($locationConfig['is_content_location'] ?? 0) === 1
            ? (string)($locationConfig['content_location'] ?? '')
            : '';


        $publish->material_url = explode(',', $publish->material_url);
        $publish->pic = FileService::getFileUrl($publish->pic ?? '');
        $publish->is_demo_data = 0;
        return $publish->toArray();
    }

    private static function getAutoPublishCircleTask(array $params, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'material_id' => 0,
            'title' => '👍',
            'type' => 1,
            'list' => [
                'https://demo.imai.work/uploads/demo/1.mp4',
            ],
            'taskId' => 0,
            'body' => '👍',
            'tag' => '',
            'comment' => '',
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

        $publish = \app\common\model\wechat\AiWechatCircleTask::field('*')
            ->where('id', $task->sub_data_id)
            ->where('device_code', '=', $task->device_code)
            ->where('wechat_id', $task->account)
            //->where('send_status', 'in', [0, 1, 2])
            ->order('send_time desc')
            ->limit(1)
            ->findOrEmpty();
        if ($publish->isEmpty()) {
            return $payload;
        }
        $publish->publish_platform = $account->type;
        $publish->material_id = $publish->id;
        $publish->title = $publish->content;
        $publish->list = $publish->attachment_content;
        $publish->body = $publish->content;
        $publish->type = $publish->attachment_type == 1 ? 2 : 1;
        $publish->is_demo_data = 0;
        return $publish->toArray();
    }

    private static function getAutoFriendTask(array $params, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            [
                'friendWechatId' => 'EIGHTBITttt',
                'message' => 'hello，你好啊',
                'recordId' => 0,
                'isManual' => 0,
            ]
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

        $records = \app\common\model\sv\SvAddWechatRecord::alias('r')
            ->field('r.*, t.add_number, t.add_interval_time, t.add_friends_prompt, t.add_remark_enable, t.remarks, t.wechat_id, t.wechat_reg_type')
            ->join('sv_crawling_task t', 'r.crawling_task_id = t.id and t.delete_time is null')
            ->where('r.device_code', $params['device_code'])
            ->where('r.status', 'in', [2, 3, 4, 5])
            ->order('r.id', 'desc')
            ->limit(10)
            ->select()
            ->toArray();
        $remarks = \app\common\service\ConfigService::get('add_remark',  'wechat',  []);
        foreach ($records as $record) {
            $remark = $remarks[array_rand($remarks)] ?? '您好！';
            array_push($payload, [
                'friendWechatId' => $record['reg_wechat'],
                'message' => $remark, //ai生成打招呼消息
                'recordId' => $record['id'],
                'isManual' => 0,
            ]);
        }
        return $payload;
    }

    private static function getAutoTouchTask(array $params, int $task_scene, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'id' => 0,
            'task_id' => 0,
            'auto_type' => 1,
            'start_time' => time(),
            'end_time' => time() + 60 * 30,
            'time_interval' => 30,
            'industry' => ['AI自动获客'],
            'is_like' => 1, //点赞
            'is_follow' => 1, //评论作品
            'is_note_collect' => 1, //收藏作品
            'is_note_comment' => 1, //评论作品
            'content' => ['你好'],
            'filter' => array_merge(
                \app\common\service\ConfigService::get('touch_clue',  'comment_screening',  []),
                [',', '.', '?', '!', '，', '。', '！', '？', '多', '少', '钱', '可', '以', '吗']
            ),
            'send_num' => 1,
            'industry_num' => 5,
            'industry_type' => 0,
            'city' => '',
            'is_content_author' => 1, //是否评论内容作者
            'is_execed_clues' => 1, //是否执行过获客任务
            'content_publish_day' => 0, //评论内容发布时间间隔/天
            'comment_publish_day' => 0, //评论发布时间间隔/天
            'ip_address' => [], //IP地址
            'is_demo_data' => 1,
        ];


        if ($task->isEmpty()) {
            return $payload;
        }

        $account = \app\common\model\sv\SvLeadScrapingSettingAccount::where('id', $task->sub_task_id)->where('task_type', $task_scene)->findOrEmpty();
        if ($account->isEmpty()) {
            return $payload;
        }

        $setting = \app\common\model\sv\SvLeadScrapingSetting::where('id', $account->scraping_id)->where('task_type', $task_scene)->findOrEmpty();
        if ($setting->isEmpty()) {
            return $payload;
        }
        $setting->task_id = $task->id;
        $setting->auto_type = $task->task_type;
        $setting->device_code = $task->device_code;
        $setting->account = $task->account;
        $setting->account_type = $task->type;
        $setting->start_time = time();
        $setting->end_time = time() + 60 * 30;
        $setting->time_interval = 30;
        $setting->industry = !empty($setting->industry) ? json_decode($setting->industry, true) : [];
        $setting->content = !empty($setting->content) ? json_decode($setting->content, true) : [];
        $setting->filter = !empty($setting->filter) ? json_decode($setting->filter, true) : [];
        $setting->content_publish_day = AiPersonaTrafficConfig::normalizeContentPublishDay($setting->content_publish_day ?? 0);
        $setting->is_note_collect =  in_array(5, $setting->marker_method) ? 1 : 0; //收藏作品;
        $setting->is_note_comment =  in_array(4, $setting->marker_method) ? 1 : 0; //评论作品;
        $setting->is_demo_data = 0;
        return $setting->toArray();
    }

    private static function getAutoCluesTask(array $params, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'id' => 0,
            'task_id' => 0,
            'auto_type' => 1,
            'crawl_type' => 1,
            'keywords' => ['AI自动获客'],
            'exec_number' => 1,
            'chat_type' => 0,
            'chat_number' => 10,
            'chat_interval_time' => 10,
            'add_type' => 0,
            'add_remark_enable' => 0,
            'add_number' => 10,
            'add_interval_time' => 10,
            'greeting_content' => '',
            'ocr_type' => 1,
            'create_time' => time(),
            'start_time' => time(),
            'end_time' => time() + 60 * 5,
            'time_interval' => 5,
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

         $find = \app\common\model\sv\SvCrawlingTask::alias('ct')
                ->field('ct.*, b.device_code,b.keywords')
                ->join('sv_crawling_task_device_bind b', 'ct.id = b.task_id')
                ->where('ct.id', $task->sub_task_id)
                ->where('b.device_code', $task->device_code)
                //->where('ct.status', 'in', [0, 1])
                ->order('ct.id', 'desc')
                ->findOrEmpty();
        if ($find->isEmpty()) {
            return $payload;
        }
        $find->task_id = $task->id;
        $find->keywords = json_decode($find->keywords, true);
        $find->is_demo_data = 0;
        //print_r($find->toArray());die;
        return $find->toArray();
    }

    private static function getAutoCircleLikeCommentTask(array $params, int $task_scene, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'taskId' => 0,
            "hasLiked" => 1, //点赞
            "hasComment" => 1, //评论
            "planCoverage" => 2, //当天   1、3天内   2、7天内
            "interactionConut" => 3,  //互动数量
            "timeInterval" => 3,  //互动间隔/分钟
            "commentType" => 2,  //AI识别并评论   1、不评论   2、固定评论
            "commentContent" =>  '👍', //固定评论内容
            'start_time' => time(),
            'end_time' => time() + 60 * 30,
            'time_interval' => 30,
            'isDemoData' => 1,
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

        $find = \app\common\model\sv\SvDeviceCircleLikeReplyAccount::where('id', $task->sub_data_id)->where('status', 0)->where('auto_type', $task->auto_type)->findOrEmpty();
        if ($find->isEmpty()) {
            // 真实任务存在但子任务不可用时，禁止回落演示双开
            $payload['hasLiked'] = 0;
            $payload['hasComment'] = 0;
            $payload['isDemoData'] = 0;
            $payload['is_demo_data'] = 0;
            return $payload;
        }

        $option = \app\common\model\sv\SvDeviceCircleLikeReply::where('id', $find['circle_like_reply_id'])->findOrEmpty();
        if ($option->isEmpty()) {
            $payload['hasLiked'] = 0;
            $payload['hasComment'] = 0;
            $payload['isDemoData'] = 0;
            $payload['is_demo_data'] = 0;
            return $payload;
        }

        $deviceFlags = \app\common\service\sv\CircleInteractionActionService::resolveDeviceFlagsFromOption($option);
        $find->taskId = $find->id;
        $find->hasLiked = $deviceFlags['hasLiked']; //点赞
        $find->hasComment = $deviceFlags['hasComment']; //评论
        $find->planCoverage = $option->range; //当天   1、3天内   2、7天内
        $find->interactionConut = $option->number;  //互动数量
        $find->timeInterval = $option->interval;  //互动间隔/分钟
        $find->commentType = $option->comment_type;  //AI识别并评论   1、不评论   2、固定评论
        $find->commentContent =  $option->comment ?? ''; //固定评论内容
        $find->is_demo_data = 0;
        //print_r($find->toArray());die;
        return $find->toArray();
    }

    private static function getAutoTakeOverTask(array $params, int $task_scene, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'id' => 0,
            'task_id' => 0,
            'comment_type' => 1,
            'comment_speech' => [
                "说的不错",
                "详细了解一下",
                "谢谢"
            ],
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

        $find = \app\common\model\sv\SvDeviceTakeOverTaskAccount::alias('ct')
            ->field('ct.*, t.task_type,t.comment_type,t.comment_speech')
            ->join('sv_device_take_over_task t', 't.id = ct.take_over_id')
            ->where('ct.id', $task->sub_task_id)
            ->where('ct.device_code', $task->device_code)
            ->where('t.task_type', $task_scene)
            ->fetchSql(false)
            ->findOrEmpty();
        if ($find->isEmpty()) {
            return $payload;
        }

        $find->task_id = $task->id;
        $find->is_demo_data = 0;
        //print_r($find->toArray());die;
        return $find->toArray();
    }

    private static function getSphAutoTakeOverTask(array $params, int $task_scene, SvAccount $account, SvDeviceTask $task)
    {
        $payload = [
            'id' => 0,
            'task_id' => 0,
            'comment_type' => 3,
            'comment_speech' => [
                "说的不错",
                "详细了解一下",
                "谢谢"
            ],
            'is_demo_data' => 1,
        ];

        if ($task->isEmpty()) {
            return $payload;
        }

        $find = \app\common\model\sv\SvDeviceTakeOverTaskAccount::alias('ct')
            ->field('ct.*, t.task_type,t.comment_type,t.comment_speech')
            ->join('sv_device_take_over_task t', 't.id = ct.take_over_id')
            ->where('ct.id', $task->sub_task_id)
            ->where('ct.device_code', $task->device_code)
            ->where('t.comment_type', 3)
            ->fetchSql(false)
            ->findOrEmpty();
        if ($find->isEmpty()) {
            return $payload;
        }

        $find->task_id = $task->id;
        $find->is_demo_data = 0;
        //print_r($find->toArray());die;
        return $find->toArray();
    }
}
