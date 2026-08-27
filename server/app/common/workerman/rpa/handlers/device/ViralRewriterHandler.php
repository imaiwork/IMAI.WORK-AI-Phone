<?php

namespace app\common\workerman\rpa\handlers\device;

use app\api\logic\service\TokenLogService;
use app\common\enum\AutomationEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\sv\SvDeviceViral;
use app\common\model\sv\SvDeviceViralRecord;
use app\common\model\sv\SvDevice;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\aiPersona\AiPersonaWorkflowScheduleUser;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\model\aiPersona\AiPersona;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use app\common\service\aiPersona\ViralImageRewriteService;
use app\common\service\aiPersona\ViralKeywordService;
use app\common\service\aiPersona\ViralSharePlatformDetector;
use app\common\service\aiPersona\ViralShareTextNormalizer;
use app\common\service\aiPersona\ViralTitleDedupService;
use app\common\enum\DeviceEnum;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use Workerman\Connection\TcpConnection;
use app\common\service\ToolsService;
use app\common\service\FileService;
use app\api\logic\aiPersona\PublishLogic;


class ViralRewriterHandler extends BaseMessageHandler
{
    protected $appType = 0;
    private const VIDEO_PARSE_MAX_RETRY = 3;
    private const COPYWRITING_GENERATE_MAX_RETRY = 3;   // 文案仿写 rewritten_text 为空时最大尝试次数
    private const MAX_INTENT_RELEVANCE_COUNT = 10;
    private const DURATION_FILTER_UNLIMITED = 0;
    private const DURATION_FILTER_WITHIN_1MIN = 1;
    private const DURATION_FILTER_1_TO_5MIN = 2;
    private const DURATION_FILTER_ABOVE_5MIN = 3;
    private const DURATION_1MIN_SECONDS = 60;
    private const DURATION_5MIN_SECONDS = 300;
    /** day 配额锁：保证「读占用 + 落库占坑」原子 */
    private const DAY_QUOTA_LOCK_TTL = 20;
    private const DAY_QUOTA_LOCK_RETRY = 5;
    private const DAY_QUOTA_LOCK_SLEEP_US = 200000;

    // 入口保持不变
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->appType = $payload['appType'] ?? 0;
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;
            $this->payload['reply'] = $this->returnData($content);
            $this->payload['type'] = WorkerEnum::RPA_DEVICE_VIRAL_RITER_TASK_INTERACT;
            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog($this->payload['deviceId'] . '异常信息' . $e, 'viral_rewrite');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] = WorkerEnum::RPA_DEVICE_VIRAL_RITER_TASK_FAIL;
            $this->payload['type'] = WorkerEnum::RPA_DEVICE_VIRAL_RITER_TASK_INTERACT;
            $this->payload['content'] = [
                'code' => WorkerEnum::RPA_DEVICE_VIRAL_RITER_TASK_FAIL,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection, $this->payload);
        } finally {
            unset($content);
        }
    }

    private function returnData(array $content): array
    {
        try {
            $taskId = $content['taskId'] ?? 0;
            $payload = $this->replicaTask($content);

            $task = $this->getTaskConfig($taskId);
            if ($task->isEmpty()) {
                $this->setLog($this->payload['deviceId'] . '任务不存在' . \think\facade\Db::getLastSql(), 'viral_rewrite');
                return ['isContinue' => 0, 'msg' => $this->payload['deviceId'] . "任务不存在"];
            }
            $payload['keywords'] = $task->keywords;
            return $payload;
        } catch (\Throwable $th) {
            $this->setLog($this->payload['deviceId'] . '异常信息' . $th, 'viral_rewrite');
            return ['isContinue' => 1, 'msg' => '系统异常，任务中断' . $th->getMessage()];
        }
    }
    /**
     * 流程编排：去重 -> 创建记录 -> 视频解析 -> 无文案判断 -> 意图检验 -> 仿写生成
     */
    private function replicaTask(array $content): array
    {
        try {
            $shareContent = $content['content'] ?? ($content['share_content'] ?? ($content['url'] ?? ''));
            $taskId = $content['taskId'] ?? 0;
            $keyword = $content['keyword'] ?? '';
            $image = $content['image'] ?? '';
            $images = $this->extractImageUrls($content);

            if ($taskId == 0) {
                return ['isContinue' => 0, 'msg' => $this->payload['deviceId'] . "任务id不能为空"];
            }
            $this->setLog("\n" . $this->payload['deviceId'] . '仿写地址:' . $shareContent, 'viral_rewrite');
            try {
                $detected = ViralSharePlatformDetector::detect((string)$shareContent);
                $this->setLog(
                    $this->payload['deviceId'] . '分享平台识别:' . $detected['platform_name'] . ' url=' . $detected['url'],
                    'viral_rewrite'
                );
            } catch (\Throwable $th) {
                // 设备上报内容可能无标准链接，忽略识别失败
            }

            if (empty($shareContent) && empty($images)) {
                return ['isContinue' => 1, 'msg' => $this->payload['deviceId'] . "仿写地址不能为空"];
            }

            // 1. 获取任务配置
            $task = $this->getTaskConfig($taskId);
            if ($task->isEmpty()) {
                $this->setLog($this->payload['deviceId'] . '任务不存在' . \think\facade\Db::getLastSql(), 'viral_rewrite');
                return ['isContinue' => 0, 'msg' => $this->payload['deviceId'] . "任务不存在"];
            }
            $this->setLog($this->payload['deviceId'] . '获取任务配置' . json_encode($task->toArray(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), 'viral_rewrite');
            // 获取设备对应人设配置的发布时段有多少个
            $publishTimeCount = $this->getPublishTimeCount($task);
            if ($publishTimeCount == 0) {
                $this->setLog($this->payload['deviceId'] . "绑定的IP人设配置的发布时段为空,无需仿写,任务结束", 'viral_rewrite');
                return ['isContinue' => 0, 'msg' => $this->payload['deviceId'] . "绑定的IP人设配置的发布时段为空,无需仿写,任务结束"];
            }

            if ((int)($task->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO) === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT) {
                return $this->replicaImageTextTask($content, $task, $publishTimeCount);
            }

            if (empty($shareContent)) {
                return ['isContinue' => 1, 'msg' => $this->payload['deviceId'] . "仿写地址不能为空"];
            }

            $userId = $task->user_id;
            // 2. 精确 hash + 清洗标题相似度去重（30天）
            $dedup = ViralTitleDedupService::isDuplicate((int)$userId, $shareContent);
            $hash = $dedup['hash'];
            $titleNormalized = $dedup['title_normalized'];
            if ($dedup['duplicate']) {
                $msg = $dedup['reason'] === 'similarity'
                    ? '与已有爆款标题高度相似，请抓取下一个视频'
                    : '该链接已在30天历史池中，请抓取下一个视频';
                $this->setLog($this->payload['deviceId'] . ' ' . $msg . ' matched_id=' . $dedup['matched_id'], 'viral_rewrite');
                return ['isContinue' => 1, 'msg' => $msg];
            }

            // 3. 锁内解析 day 并立即占坑创建记录，避免并发超额
            $reserved = $this->reserveDayQuotaRecord(
                $task,
                $publishTimeCount,
                $shareContent,
                $hash,
                $keyword,
                $image,
                $content,
                ['title_normalized' => $titleNormalized]
            );
            if (!$reserved['ok']) {
                return $reserved['response'];
            }
            $record = $reserved['record'];
            $recordDay = $reserved['day'];

            // 4. 视频解析（内部自动重试）
            $parseResult = $this->parseVideoWithRetry($shareContent, $record);
            if (!$parseResult['success']) {
                return $this->degradedGenerate($record, $task, '视频解析失败');
            }


            $audioText = $parseResult['audio_text'];
            $duration = (int)$parseResult['duration'];
            $record->original_text = $audioText;
            $record->video_duration = $duration;
            $durationFilter = (int)($task->duration_filter ?? 0);
            $durationError = $this->matchTrackingDuration($duration, $durationFilter);
            if ($durationError !== null) {
                $record->remark = $durationError;
                $record->status = 5;
                $record->save();
                $this->setLog($this->payload['deviceId'] . ' ' . $durationError . '，已丢弃并继续抓取', 'viral_rewrite');
                return ['isContinue' => 1, 'msg' => $this->payload['deviceId'] . ' ' . $durationError . '，已丢弃并继续抓取'];
            }

            $record->save();
            // 5. 无文案处理
            if ($this->isNoScript($audioText)) {
                $this->deductBaseCost($userId, $duration, $taskId);
                $record->status = 1;
                $record->copywriting_type = 2;
                $record->remark = '无有效口播文案';
                $record->save();
                return ['isContinue' => 1, 'msg' => '未识别到视频中含有文案，已丢弃并继续抓取'];
            }

            // 6. 正常扣费（按分钟）
            $this->deductParseFee($userId, $duration, $taskId);

            // 7. 意图检验
            $persona = AiPersona::where('id', $task->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                $this->setLog($this->payload['deviceId'] . "IP人设不存在:" . \think\facade\Db::getLastSql(), 'viral_rewrite');
                $record->status = 5;
                $record->remark = 'IP人设不存在';
                $record->save();
                return ['isContinue' => 1, 'msg' => '系统配置异常，已丢弃当前视频'];
            }
            if (!$this->checkIntentRelevance($audioText, $persona)) {
                $record->status = 5;
                $record->copywriting_type = 3;
                $record->remark = '文案与人设严重偏离';
                $record->save();
                $count = $this->getIntentRelevanceCount($task);
                $this->setLog($this->payload['deviceId'] . "视频内容与人设严重偏离,已仿写次数:" . $count, 'viral_rewrite');
                if ($count >= self::MAX_INTENT_RELEVANCE_COUNT) {
                    $this->setLog($this->payload['deviceId'] . "视频内容与人设严重偏离,降级处理", 'viral_rewrite');
                    $record = $this->createRecord($task, $shareContent, $hash, $keyword, $image, $content, $recordDay, [
                        'title_normalized' => $titleNormalized,
                    ]);
                    return $this->degradedGenerate($record, $task, '内容与人设偏离达上限');
                } else {
                    return ['isContinue' => 1, 'msg' => '视频内容与人设不符，已丢弃并继续抓取'];
                }
            }

            // 8. 仿写生成并存储
            return $this->generateAndStoreCopywriting($record, $task, $audioText, $persona);
        } catch (\Throwable $th) {
            //throw $th;
            $this->setLog($this->payload['deviceId'] . '异常信息' . $th, 'viral_rewrite');
            return ['isContinue' => 1, 'msg' => '系统异常，任务中断' . $th->getMessage()];
        }
    }

    // --------------- 拆分出的方法 ---------------
    private function replicaImageTextTask(array $content, SvDeviceViral $task, int $publishTimeCount): array
    {
        return $this->replicaImageTextTaskByTikHub($content, $task, $publishTimeCount);
    }

    private function replicaImageTextTaskLegacy(array $content, SvDeviceViral $task, int $publishTimeCount): array
    {
        $shareContent = trim((string)($content['content'] ?? $content['share_content'] ?? $content['url'] ?? ''));
        $keyword = (string)($content['keyword'] ?? '');
        $images = $this->extractImageUrls($content);
        if (empty($images)) {
            return ['isContinue' => 1, 'msg' => '未获取到图文图片，继续抓取下一条'];
        }

        $dedupSource = $shareContent !== ''
            ? $shareContent
            : trim((string)($content['title'] ?? '') . ' ' . (string)($content['text'] ?? $content['desc'] ?? ''));
        $dedup = ViralTitleDedupService::isDuplicate((int)$task->user_id, $dedupSource);
        // 图文 legacy 仍保留图片维度进 hash，避免同标题不同图误杀
        $hash = hash('sha256', json_encode([
            'content_hash' => $dedup['hash'],
            'title' => $content['title'] ?? '',
            'text' => $content['text'] ?? ($content['desc'] ?? ''),
            'images' => $images,
        ], JSON_UNESCAPED_UNICODE));
        if ($this->isDuplicateInPool((int)$task->user_id, $hash) || $dedup['duplicate']) {
            $msg = $dedup['duplicate'] && $dedup['reason'] === 'similarity'
                ? '与已有爆款标题高度相似，请抓取下一条'
                : '该图文已在30天历史池中，请抓取下一条';
            return ['isContinue' => 1, 'msg' => $msg];
        }

        $reserved = $this->reserveDayQuotaRecord(
            $task,
            $publishTimeCount,
            $shareContent !== '' ? $shareContent : $this->buildImageTextSourceText($content),
            $hash,
            $keyword,
            (string)($images[0] ?? ''),
            $content,
            [
                'title_normalized' => $dedup['title_normalized'],
                'original_text' => $this->buildImageTextSourceText($content),
                'original_images' => $images,
                'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT,
                'remark' => '图文处理中',
            ]
        );
        if (!$reserved['ok']) {
            return $reserved['response'];
        }
        $record = $reserved['record'];

        $persona = AiPersona::where('id', $task->persona_id)->findOrEmpty();
        if ($persona->isEmpty()) {
            $this->markRecordFailed($record, 'IP人设不存在', [
                'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
            ]);
            return ['isContinue' => 1, 'msg' => '系统配置异常，已丢弃当前图文'];
        }

        return $this->generateAndStoreImageTextCopywriting($record, $task, $content, $persona);
    }

    private function replicaImageTextTaskByTikHub(array $content, SvDeviceViral $task, int $publishTimeCount): array
    {
        $shareContent = trim((string)($content['content'] ?? $content['share_content'] ?? $content['url'] ?? ''));
        $keyword = (string)($content['keyword'] ?? '');
        if ($shareContent === '') {
            return ['isContinue' => 1, 'msg' => '未获取到小红书图文分享链接，继续抓取下一条'];
        }
        $shareUrl = $this->extractXhsShareUrl($shareContent);
        if ($shareUrl === '') {
            return ['isContinue' => 1, 'msg' => '未从分享内容中提取到小红书图文链接，继续抓取下一条'];
        }
        $content['share_url'] = $shareUrl;

        $dedup = ViralTitleDedupService::isDuplicate((int)$task->user_id, $shareContent);
        $hash = $dedup['hash'];
        $titleNormalized = $dedup['title_normalized'];
        if ($dedup['duplicate']) {
            $msg = $dedup['reason'] === 'similarity'
                ? '与已有爆款标题高度相似，请抓取下一条'
                : '该图文已在30天历史池中，请抓取下一条';
            return ['isContinue' => 1, 'msg' => $msg];
        }

        try {
            $rewriteUnit = TokenLogService::checkToken((int)$task->user_id, 'images_explosion_rewrite');
        } catch (\Throwable $th) {
            if ((int)$th->getCode() === WorkerEnum::TASK_TOKEN_ERROR) {
                return ['isContinue' => 0, 'msg' => $th->getMessage()];
            }
            throw $th;
        }

        // TikHub/原图下载前先占坑，避免长耗时期间并发超额写同一天
        $reserved = $this->reserveDayQuotaRecord(
            $task,
            $publishTimeCount,
            $shareUrl,
            $hash,
            $keyword,
            '',
            $content,
            [
                'title_normalized' => $titleNormalized,
                'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT,
                'image_rewrite_success_count' => 0,
                'image_rewrite_fail_count' => 0,
                'image_rewrite_charged_count' => 0,
                'remark' => '图文处理中',
            ]
        );
        if (!$reserved['ok']) {
            return $reserved['response'];
        }
        $record = $reserved['record'];

        try {
            $noteContent = $this->extractXhsImageTextNote($shareUrl);
        } catch (\Throwable $th) {
            $this->markRecordFailed($record, 'TikHub图文提取失败：' . $th->getMessage(), [
                'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
            ]);
            $this->setLog($record->device_code . "TikHub图文提取失败 record_id={$record->id}: " . $th->getMessage(), 'viral_rewrite');
            return ['isContinue' => 1, 'msg' => 'TikHub图文提取失败，继续抓取下一条'];
        }

        $content = array_merge($content, $noteContent);
        $images = is_array($noteContent['images'] ?? null) ? $noteContent['images'] : [];
        if (empty($images)) {
            $this->markRecordFailed($record, 'TikHub未返回可用图文图片', [
                'original_text' => $this->buildImageTextSourceText($content),
                'tikhub_raw' => $noteContent['tikhub_raw'] ?? [],
                'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
            ]);
            $this->setLog($record->device_code . "TikHub未返回可用图文图片 record_id={$record->id}", 'viral_rewrite');
            return ['isContinue' => 1, 'msg' => 'TikHub未返回可用图文图片，继续抓取下一条'];
        }

        // 抓取费 + 图改写费一并按企业钱包预检，避免成员个人算力为 0 被误拦 / 余额不够仍继续跑
        try {
            $rewriteEstimate = ViralImageRewriteService::estimateRewritePoints(count($images));
            $needPoints = round((float)$rewriteUnit + (float)$rewriteEstimate['points'], 2);
            $spendable = \app\common\service\TeamBillingService::spendableTokens((int)$task->user_id);
            if ($needPoints > 0 && $spendable < $needPoints) {
                $msg = \app\common\service\TeamBillingService::resolveSpender((int)$task->user_id) !== null
                    ? '当前团队算力不足，请联系团队主' : '用户算力不足';
                $this->markRecordFailed($record, $msg . "（需要{$needPoints}，可用{$spendable}）", [
                    'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
                ]);
                $this->setLog(
                    $record->device_code . "图文爆款算力预检不足 record_id={$record->id}"
                    . " need={$needPoints} spendable={$spendable}"
                    . " extract={$rewriteUnit} rewrite_unit={$rewriteEstimate['unit']}"
                    . " images={$rewriteEstimate['image_count']}",
                    'viral_rewrite'
                );
                return ['isContinue' => 0, 'msg' => $msg];
            }
        } catch (\Throwable $th) {
            if ((int)$th->getCode() === WorkerEnum::TASK_TOKEN_ERROR) {
                $this->markRecordFailed($record, $th->getMessage(), [
                    'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
                ]);
                return ['isContinue' => 0, 'msg' => $th->getMessage()];
            }
            // 模型售价未配置等：丢弃本条继续抓，避免整任务卡死
            $this->markRecordFailed($record, '图文改写计费配置异常：' . $th->getMessage(), [
                'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
            ]);
            $this->setLog($record->device_code . '图文改写计费配置异常: ' . $th->getMessage(), 'viral_rewrite');
            return ['isContinue' => 1, 'msg' => '图文改写计费配置异常，继续抓取下一条'];
        }

        // 若分享文清洗标题过短，用笔记标题补充落库
        if ($titleNormalized === '' || mb_strlen($titleNormalized, 'UTF-8') < ViralTitleDedupService::MIN_TITLE_LENGTH) {
            $noteTitleSource = trim((string)($content['title'] ?? '') . ' ' . (string)($content['text'] ?? $content['desc'] ?? ''));
            if ($noteTitleSource !== '') {
                $titleNormalized = ViralShareTextNormalizer::normalize($noteTitleSource);
            }
        }

        $record->title_normalized = $titleNormalized;
        $record->original_text = $this->buildImageTextSourceText($content);
        $record->original_images = $images;
        $record->tikhub_raw = $noteContent['tikhub_raw'] ?? [];
        $record->image = (string)($images[0] ?? '');
        $record->likes = (int)($content['likes'] ?? $record->likes ?? 0);
        $record->comments = (int)($content['comments'] ?? $record->comments ?? 0);
        $record->update_time = time();
        $record->save();

        try {
            $this->chargeImageExplosionRewrite((int)$task->user_id, (int)$record->id, $shareUrl, (float)$rewriteUnit);
        } catch (\Throwable $th) {
            return $this->handleImageExplosionChargeFailure($record, $th);
        }

        $persona = AiPersona::where('id', $task->persona_id)->findOrEmpty();
        if ($persona->isEmpty()) {
            $this->markRecordFailed($record, 'IP人设不存在', [
                'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
            ]);
            return ['isContinue' => 1, 'msg' => '系统配置异常，已丢弃当前图文'];
        }

        return $this->generateAndStoreImageTextCopywriting($record, $task, $content, $persona);
    }

    private function chargeImageExplosionRewrite(int $userId, int $recordId, string $shareUrl, float $unit): void
    {
        if ($unit <= 0) {
            return;
        }

        $taskId = 'viral_info_extract_' . $recordId;
        Db::startTrans();
        try {
            $user = User::where('id', $userId)->lock(true)->findOrEmpty();
            if ($user->isEmpty()) {
                throw new \Exception('用户查询失败');
            }

            $chargedLog = UserTokensLog::where('user_id', $userId)
                ->where('task_id', $taskId)
                ->where('change_type', AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE)
                ->where('action', AccountLogEnum::DEC)
                ->findOrEmpty();
            if (!$chargedLog->isEmpty()) {
                Db::commit();
                return;
            }

            // 企业空间成员看企业钱包，勿用个人 tokens 预检
            $spendable = \app\common\service\TeamBillingService::spendableTokens($userId);
            if ($spendable < $unit) {
                $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                    ? '当前团队算力不足，请联系团队主' : '用户算力不足';
                throw new \Exception($msg, WorkerEnum::TASK_TOKEN_ERROR);
            }

            User::userTokensChange($userId, $unit);
            AccountLogLogic::recordUserTokensLog(
                true,
                $userId,
                AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE,
                $unit,
                $taskId,
                [
                    '扣费项目' => '图文爆款仿写信息抓取',
                    '算力单价' => $unit,
                    '实际消耗算力' => $unit,
                    'record_id' => $recordId,
                    'share_url' => $shareUrl,
                ]
            );
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            throw $th;
        }
    }

    private function handleImageExplosionChargeFailure(SvDeviceViralRecord $record, \Throwable $th): array
    {
        $isTokenError = (int)$th->getCode() === WorkerEnum::TASK_TOKEN_ERROR;
        $remark = '图文信息抓取扣费失败：' . $th->getMessage();
        $this->markRecordFailed($record, $remark, [
            'image_rewrite_status' => SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL,
        ]);
        $this->setLog($record->device_code . "图文信息抓取扣费失败 record_id={$record->id}: " . $th->getMessage(), 'viral_rewrite');

        return [
            'isContinue' => $isTokenError ? 0 : 1,
            'msg' => $isTokenError ? $th->getMessage() : '图文信息抓取扣费失败，继续抓取下一条',
        ];
    }

    private function extractXhsImageTextNote(string $shareContent): array
    {
        $shareUrl = $this->extractXhsShareUrl($shareContent);
        if ($shareUrl === '') {
            throw new \Exception('未从分享内容中提取到小红书图文链接');
        }
        $this->setLog($this->payload['deviceId'] . "TikHub图文提取开始 " . $shareUrl, 'viral_rewrite');
        $response = ToolsService::TikHub()->getXhsImageNoteDetail($shareUrl);
        $this->setLog($this->payload['deviceId'] . "TikHub图文提取响应 " . json_encode($response, JSON_UNESCAPED_UNICODE), 'viral_rewrite');

        if (!$this->isSuccessApiResponse($response)) {
            throw new \Exception((string)($response['msg'] ?? $response['message'] ?? 'TikHub图文提取失败'));
        }

        $payload = $this->resolveTikHubPayload($response);
        $titleValue = $this->findFirstByKeys($payload, ['title', 'note_title', 'display_title']);
        $bodyValue = $this->findFirstByKeys($payload, ['desc', 'description', 'content', 'note_desc', 'text']);
        $likesValue = $this->findFirstByKeys($payload, ['liked_count', 'likes', 'like_count', 'likedCount']);
        $commentsValue = $this->findFirstByKeys($payload, ['comment_count', 'comments', 'commentCount']);
        $title = is_scalar($titleValue) ? trim((string)$titleValue) : '';
        $body = is_scalar($bodyValue) ? trim((string)$bodyValue) : '';
        $tags = $this->normalizeTags($this->findFirstByKeys($payload, ['tags', 'tag_list', 'topic_tags', 'topics', 'hash_tags']) ?? []);
        $images = $this->downloadXhsNoteImages($response);
        if (empty($images)) {
            throw new \Exception('TikHub原图下载失败');
        }

        return [
            'title' => $title,
            'desc' => $body,
            'tags' => $tags,
            'images' => $images,
            'likes' => is_scalar($likesValue) ? (int)$likesValue : 0,
            'comments' => is_scalar($commentsValue) ? (int)$commentsValue : 0,
            'tikhub_raw' => $response,
        ];
    }

    private function downloadXhsNoteImages(array $response): array
    {
        $imagesList = $response['data']['data'][0]['note_list'][0]['images_list'] ?? [];
        if (!is_array($imagesList) || empty($imagesList)) {
            return [];
        }

        $images = [];
        $failed = 0;
        foreach ($imagesList as $item) {
            if (!is_array($item)) {
                $failed++;
                continue;
            }

            $storedImage = $this->downloadXhsImageWithFallback($this->buildTikHubImageDownloadCandidates($item));
            if ($storedImage === '') {
                $failed++;
                continue;
            }
            $images[] = $storedImage;
        }

        $this->setLog(
            $this->payload['deviceId'] . ' ' . 
            '小红书原图下载汇总 total=' . count($imagesList)
            . ' success=' . count($images)
            . ' failed=' . $failed,
            'viral_rewrite'
        );

        return array_values(array_unique($images));
    }

    private function buildTikHubImageDownloadCandidates(array $item): array
    {
        $candidates = [];
        foreach (['original', 'url_size_large', 'url_8k', 'url'] as $key) {
            if (empty($item[$key]) || !is_scalar($item[$key])) {
                continue;
            }
            $url = $this->normalizeTikHubImageUrl((string)$item[$key]);
            if ($url !== '' && !in_array($url, $candidates, true)) {
                $candidates[] = $url;
            }
        }

        return $candidates;
    }

    private function downloadXhsImageWithFallback(array $candidateUrls): string
    {
        if (empty($candidateUrls)) {
            return '';
        }

        foreach ($candidateUrls as $url) {
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $storedImage = trim(str_replace('\\', '/', (string)$this->downloadXhsOriginalImageToLocal($url)));
                if ($storedImage !== '' && !preg_match('/^https?:\/\//i', $storedImage)) {
                    if ($attempt > 1) {
                        $this->setLog($this->payload['deviceId'] . " 小红书原图下载重试成功 attempt={$attempt} url={$url}", 'viral_rewrite');
                    }
                    return ltrim($storedImage, '/');
                }
                if ($attempt < 3) {
                    usleep(500000 * $attempt);
                }
            }
        }

        $this->setLog($this->payload['deviceId'] . ' 小红书原图全部候选下载失败 candidates=' . implode(' | ', $candidateUrls), 'viral_rewrite');
        return '';
    }

    private function normalizeTikHubImageUrl(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $url = rtrim($url, " \t\n\r\0\x0B,，.。;；!！?？)）]】}\"'");
        return preg_match('/^https?:\/\//i', $url) ? $url : '';
    }

    private function downloadXhsOriginalImageToLocal(string $url): string
    {
        $ch = curl_init();
        if ($ch === false) {
            return '';
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 25,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'Referer: https://www.xiaohongshu.com/',
            ],
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($errno !== 0 || $statusCode < 200 || $statusCode >= 300 || !is_string($body) || $body === '') {
            $this->setLog($this->payload['deviceId'] . " 小红书原图下载HTTP失败 status={$statusCode} errno={$errno} error={$error} content_type={$contentType} url={$url}", 'viral_rewrite');
            return '';
        }

        $date = date('Ymd');
        $directory = $this->getPublicRootPath() . 'uploads' . DIRECTORY_SEPARATOR . 'rewrite' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $date;
        try {
            FileService::ensureWritableDir($directory);
        } catch (\Throwable $th) {
            $this->setLog($this->payload['deviceId'] . " 小红书原图本地目录创建失败 {$directory} error=" . $th->getMessage(), 'viral_rewrite');
            return '';
        }

        $filename = date('YmdHis') . md5($url . microtime(true) . mt_rand()) . '.png';
        $absolutePath = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $filename;
        if (!$this->saveImageContentAsPng($body, $absolutePath)) {
            $this->setLog($this->payload['deviceId'] . " 小红书原图本地保存失败 {$absolutePath}", 'viral_rewrite');
            return '';
        }
        FileService::ensureWritableFile($absolutePath);

        $relativeUri = 'uploads/rewrite/images/' . $date . '/' . $filename;
        $compressedUri = ViralImageRewriteService::compressStoredImage($relativeUri);
        if ($compressedUri !== $relativeUri) {
            $this->setLog(
                $this->payload['deviceId'] . " 小红书原图已压缩 from={$relativeUri} to={$compressedUri}",
                'viral_rewrite'
            );
        }
        return $compressedUri;
    }

    private function getPublicRootPath(): string
    {
        if (function_exists('public_path')) {
            return rtrim(\public_path(), '/\\') . DIRECTORY_SEPARATOR;
        }

        return dirname(__DIR__, 6) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
    }

    private function saveImageContentAsPng(string $content, string $absolutePath): bool
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
            return false;
        }

        $image = @imagecreatefromstring($content);
        if ($image === false) {
            return false;
        }

        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $saved = imagepng($image, $absolutePath);
        imagedestroy($image);

        if (!$saved || !is_file($absolutePath) || filesize($absolutePath) <= 0) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            return false;
        }

        return true;
    }

    private function extractXhsShareUrl(string $shareContent): string
    {
        $shareContent = trim($shareContent);
        if ($shareContent === '') {
            return '';
        }

        try {
            $detected = ViralSharePlatformDetector::detect($shareContent);
            if ((int)$detected['platform'] === DeviceEnum::ACCOUNT_TYPE_XHS) {
                return (string)$detected['url'];
            }
        } catch (\Throwable $th) {
            // 回落正则
        }

        if (!preg_match('/https?:\/\/(?:www\.)?(?:xiaohongshu\.com|xhslink\.com|xhslink\.cn|xhsurl\.com)\/[^\s]+/iu', $shareContent, $matches)) {
            return '';
        }

        $url = html_entity_decode($matches[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return rtrim($url, " \t\n\r\0\x0B,，.。;；!！?？)）]】}\"'");
    }

    private function isSuccessApiResponse(array $response): bool
    {
        if (empty($response)) {
            return false;
        }
        $code = $response['code'] ?? $response['status_code'] ?? $response['status'] ?? 0;
        $message = strtolower((string)($response['msg'] ?? $response['message'] ?? ''));
        if (is_string($code) && strtolower($code) === 'success') {
            return true;
        }
        if ((int)$code === 0 && $message !== '' && !in_array($message, ['success', 'ok'], true)) {
            return false;
        }
        return in_array((int)$code, [0, 1, 200, 10000], true) || !empty($response['data']);
    }

    private function resolveTikHubPayload(array $response): array
    {
        $candidates = [
            $response['data']['data']['note_card'] ?? null,
            $response['data']['data']['note'] ?? null,
            $response['data']['data'] ?? null,
            $response['data']['note_card'] ?? null,
            $response['data']['note'] ?? null,
            $response['data']['items'][0]['note_card'] ?? null,
            $response['data']['item'] ?? null,
            $response['data'] ?? null,
            $response,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && !empty($this->extractImageUrls($candidate))) {
                return $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            if (is_array($candidate)) {
                return $candidate;
            }
        }

        return $response;
    }

    private function findFirstByKeys(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== '' && $data[$key] !== null) {
                return $data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->findFirstByKeys($value, $keys);
                if ($found !== null && $found !== '') {
                    return $found;
                }
            }
        }

        return null;
    }

    private function extractImageUrls(array $content): array
    {
        $keys = ['images', 'image_list', 'imageList', 'image_urls', 'imageUrls', 'pictures', 'pics', 'original_images', 'image'];
        $raw = [];
        foreach ($keys as $key) {
            if (!empty($content[$key])) {
                $raw = $content[$key];
                break;
            }
        }
        if (empty($raw) && !empty($content['image'])) {
            $raw = $content['image'];
        }
        if (empty($raw)) {
            $nested = [];
            foreach ($content as $value) {
                if (is_array($value)) {
                    $nested = array_merge($nested, $this->extractImageUrls($value));
                }
            }
            if (!empty($nested)) {
                return array_values(array_unique($nested));
            }
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : preg_split('/[,，\s]+/', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }

        $images = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $item = $item['url'] ?? $item['src'] ?? $item['path'] ?? $item['url_default'] ?? $item['url_pre'] ?? $item['origin_url'] ?? $item['original_url'] ?? $item['image_url'] ?? ($item['url_list'][0] ?? '') ?? ($item['info_list'][0]['url'] ?? '');
            }
            $url = trim((string)$item);
            if ($url !== '') {
                $images[] = $url;
            }
        }

        return array_values(array_unique($images));
    }

    private function buildImageTextSourceText(array $content): string
    {
        $title = trim((string)($content['title'] ?? ''));
        $body = trim((string)($content['body'] ?? $content['text'] ?? $content['desc'] ?? $content['description'] ?? $content['content_text'] ?? ''));
        $tags = $this->normalizeTags($content['tags'] ?? $content['topic_tags'] ?? $content['topics'] ?? []);

        $parts = [];
        if ($title !== '') {
            $parts[] = '标题：' . $title;
        }
        if ($body !== '') {
            $parts[] = '正文：' . $body;
        }
        if (!empty($tags)) {
            $parts[] = '标签：' . implode(' ', $tags);
        }

        return implode("\n", $parts);
    }

    private function normalizeTags(mixed $tags): array
    {
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            $tags = is_array($decoded) ? $decoded : preg_split('/[,，\s]+/', $tags);
        }
        if (!is_array($tags)) {
            return [];
        }

        $result = [];
        foreach ($tags as $tag) {
            if (is_array($tag)) {
                $tag = $tag['name'] ?? $tag['title'] ?? '';
            }
            $tag = trim((string)$tag);
            if ($tag !== '') {
                $result[] = $tag;
            }
        }
        return array_values(array_unique($result));
    }

    private function generateAndStoreImageTextCopywriting(SvDeviceViralRecord $record, SvDeviceViral $task, array $content, AiPersona $persona): array
    {
        try {
            $rule = null;
            if ($persona->persona_type == 1) {
                $rule = $persona->individual;
            } elseif ($persona->persona_type == 2) {
                $rule = $persona->enterprise;
            } elseif ($persona->persona_type == 3) {
                $rule = $persona->local;
            }
            if (!$rule) {
                throw new \Exception('IP人设规则不存在');
            }

            // 图文发布文案严格按内容发布配置生成，不走爆款原文 AI 仿写，也不回落合成规则文案库
            $defaultKeywords = $rule->getClueContent($persona);
            $taskId = generate_unique_task_id();
            $platform = (int)($task->publish_platform ?: AiPersona::PUBLISH_PLATFORM_XHS);
            $platformConfig = AiPersona::getPlatformContentPublishConfig(
                $persona['content_publish_config'] ?? [],
                $platform
            );
            $this->setLog(
                $record->device_code . '图文发布文案开始生成：' . json_encode([
                    'record_id' => (int)$record->id,
                    'viral_id' => (int)$task->id,
                    'persona_id' => (int)$persona->id,
                    'platform' => $platform,
                    'task_id' => $taskId,
                    '内容发布配置' => [
                        'generate_mode' => (int)($platformConfig['generate_mode'] ?? 0),
                        'generate_basis' => (int)($platformConfig['generate_basis'] ?? 0),
                        'library_use_mode' => (int)($platformConfig['library_use_mode'] ?? 0),
                        'library_reuse_mode' => (int)($platformConfig['library_reuse_mode'] ?? 0),
                        'custom_direction' => (string)($platformConfig['custom_direction'] ?? ''),
                        'custom_copywriting' => $platformConfig['custom_copywriting'] ?? [],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'viral_rewrite'
            );
            $response = PublishLogic::resolveContentPublishCopywriting(
                $persona,
                $defaultKeywords,
                $taskId,
                (int)$task->user_id,
                $platform,
                true
            );
            if ((int)($response['code'] ?? 0) !== 10000) {
                $this->setLog(
                    $record->device_code . '图文发布文案生成失败响应：' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'viral_rewrite'
                );
                throw new \Exception((string)($response['msg'] ?? $response['message'] ?? '内容发布文案生成失败'));
            }

            $data = is_array($response['data'] ?? null) ? $response['data'] : [];
            if (!empty($data['library_empty'])) {
                $this->setLog(
                    $record->device_code . '图文发布文案库为空：' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'viral_rewrite'
                );
                throw new \Exception((string)($data['library_message'] ?? '发布文案库暂无可用文案'));
            }

            $copywritingContent = trim((string)($data['content'] ?? ''));
            $record->copywriting = [
                'title' => trim((string)($data['title'] ?? '')),
                'content' => $copywritingContent,
                'rewritten_text' => $copywritingContent,
                'tag' => trim((string)($data['tag'] ?? '')),
            ];
            $record->copywriting_type = 1;
            $record->status = 4;
            $record->remark = '图文文案生成成功，等待图片改写';
            $record->image_rewrite_status = SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT;
            $record->update_time = time();
            $record->save();

            $this->setLog(
                $record->device_code . '图文发布文案已落库：' . json_encode([
                    'record_id' => (int)$record->id,
                    'task_id' => $taskId,
                    'library_item_id' => (int)($data['library_item_id'] ?? 0),
                    'from_ai' => (int)($data['from_ai'] ?? 0),
                    '文案' => $record->copywriting,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'viral_rewrite'
            );

            $this->setLog(
                $record->device_code . "图文图片改写已进入后台队列 record_id=" . $record->id . "\n",
                'viral_rewrite'
            );

            return ['isContinue' => 1, 'msg' => '图文文案已生成，图片已进入后台改写'];
        } catch (\Throwable $th) {
            $this->setLog($record->device_code . "图文仿写失败: " . $th->__toString(), 'viral_rewrite');
            $record->status = 5;
            $record->copywriting_type = 4;
            $record->remark = '图文文案生成失败：' . $th->getMessage();
            $record->image_rewrite_status = SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL;
            $record->update_time = time();
            $record->save();
            return ['isContinue' => 1, 'msg' => '图文仿写失败，继续抓取下一条'];
        }
    }

    private function getIntentRelevanceCount(SvDeviceViral $task): int
    {
        try {
            $count = SvDeviceViralRecord::where('viral_id', $task->id)
                ->where('status', 5)
                ->where('copywriting_type', 3)
                ->where('id', '>', function ($query) use ($task) {
                    $query->name('sv_device_viral_record')->where('viral_id', $task->id)->where('status', 'in', [3, 4])->order('id', 'desc')->limit(1)->field('id');
                })
                ->count();
            return $count;
        } catch (\Throwable $th) {
            //throw $th;
            $this->setLog($this->payload['deviceId'] . '获取意图相关度统计异常:' . $th, 'viral_rewrite');
            return 0; // 异常时保守处理
        }
    }
    /**
     * 锁内解析目标 day 并立即创建占坑记录，避免并发读到同一配额。
     *
     * @return array{ok:bool,day?:string,record?:SvDeviceViralRecord,response?:array}
     */
    private function reserveDayQuotaRecord(
        SvDeviceViral $task,
        int $publishTimeCount,
        string $shareContent,
        string $hash,
        string $keyword,
        string $image,
        array $content,
        array $extra = []
    ): array {
        $deviceCode = (string)($this->payload['deviceId'] ?? '');
        $personaId = (int)$task->persona_id;
        $mediaType = (int)($task->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO);
        $lockKey = $this->buildDayQuotaLockKey($deviceCode, $personaId, $mediaType);
        $lockValue = $this->acquireDayQuotaLock($lockKey);
        if ($lockValue === null) {
            $this->setLog($deviceCode . ' viral rewrite day quota lock busy', 'viral_rewrite');
            return [
                'ok' => false,
                'response' => ['isContinue' => 1, 'msg' => $deviceCode . '配额锁定中，稍后重试下一条'],
            ];
        }

        try {
            $recordDayInfo = $this->resolveRecordDay($publishTimeCount, $task);
            $recordDay = $recordDayInfo['day'];
            $this->setLog(
                $deviceCode
                . ' viral rewrite date quota: today_occupied=' . $recordDayInfo['today_count']
                . ', next_day_occupied=' . $recordDayInfo['next_day_count']
                . ', publish_time_count=' . $publishTimeCount
                . ', target_day=' . ($recordDay ?: 'none'),
                'viral_rewrite'
            );
            $this->setLog(
                $deviceCode . '已占用配额:' . $recordDayInfo['today_count'] . '总仿写次数:' . $publishTimeCount,
                'viral_rewrite'
            );
            if (empty($recordDay)) {
                $this->setLog($deviceCode . '仿写占用次数超过发布时段数，任务结束', 'viral_rewrite');
                return [
                    'ok' => false,
                    'response' => ['isContinue' => 0, 'msg' => $deviceCode . '仿写成功次数超过发布时段数，任务结束'],
                ];
            }

            $record = $this->createRecord($task, $shareContent, $hash, $keyword, $image, $content, $recordDay, $extra);
            return [
                'ok' => true,
                'day' => $recordDay,
                'record' => $record,
            ];
        } finally {
            $this->releaseDayQuotaLock($lockKey, $lockValue);
        }
    }

    private function buildDayQuotaLockKey(string $deviceCode, int $personaId, int $mediaType): string
    {
        return 'viral_rewrite:day_quota:' . $deviceCode . ':' . $personaId . ':' . $mediaType;
    }

    private function acquireDayQuotaLock(string $lockKey): ?string
    {
        try {
            $redis = Cache::store('redis')->handler();
            $lockValue = uniqid((string)($this->payload['deviceId'] ?? 'vr'), true);
            for ($i = 0; $i < self::DAY_QUOTA_LOCK_RETRY; $i++) {
                if ($redis->setnx($lockKey, $lockValue)) {
                    $redis->expire($lockKey, self::DAY_QUOTA_LOCK_TTL);
                    return $lockValue;
                }
                usleep(self::DAY_QUOTA_LOCK_SLEEP_US);
            }
            return null;
        } catch (\Throwable $th) {
            $this->setLog($this->payload['deviceId'] . ' viral rewrite day quota lock acquire failed: ' . $th->getMessage(), 'viral_rewrite');
            return null;
        }
    }

    private function releaseDayQuotaLock(string $lockKey, string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            if ((string)$redis->get($lockKey) === $lockValue) {
                $redis->del($lockKey);
            }
        } catch (\Throwable $th) {
            $this->setLog('viral rewrite day quota lock release failed: ' . $th->getMessage(), 'viral_rewrite');
        }
    }

    private function markRecordFailed(SvDeviceViralRecord $record, string $remark, array $extra = []): void
    {
        $record->status = 5;
        $record->remark = $remark;
        $record->update_time = time();
        foreach ($extra as $key => $value) {
            $record->$key = $value;
        }
        $record->save();
    }

    private function resolveRecordDay(int $publishTimeCount, SvDeviceViral $task): array
    {
        $today = date('Y-m-d');
        $nextDay = date('Y-m-d', strtotime('+1 day'));
        $deviceCode = (string)($this->payload['deviceId'] ?? '');
        $todayCount = $this->getOccupiedQuotaCount($deviceCode, $today, $task);
        $nextDayCount = $this->getOccupiedQuotaCount($deviceCode, $nextDay, $task);

        $targetDay = '';
        if ($todayCount < $publishTimeCount) {
            $targetDay = $today;
        } elseif ($nextDayCount < $publishTimeCount) {
            $targetDay = $nextDay;
        }

        return [
            'day' => $targetDay,
            'today' => $today,
            'next_day' => $nextDay,
            'today_count' => $todayCount,
            'next_day_count' => $nextDayCount,
        ];
    }

    /**
     * 统计已占用配额：进行中(0) + 可用成功(3/4/6)，不含失败(5)与用户取消兴趣。
     */
    private function getOccupiedQuotaCount(string $deviceCode, string $day, SvDeviceViral $task): int
    {
        try {
            $query = SvDeviceViralRecord::where('device_code', $deviceCode)
                ->where('day', $day)
                ->where('persona_id', (int)$task->persona_id)
                ->where('publish_media_type', (int)($task->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO))
                ->where('status', 'in', [0, 3, 4, 6])
                ->where('is_interested', 1);
            if ((int)($task->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO) === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT) {
                $query->where('publish_platform', AiPersona::PUBLISH_PLATFORM_XHS);
            }
            return $query->count();
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }
    private function getPublishTimeCount(SvDeviceViral $task): int
    {
        try {

            $persona = AiPersona::where('id', $task->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                $this->setLog("IP人设不存在:" . \think\facade\Db::getLastSql(), 'viral_rewrite');
                return 0;
            }
            $isImageText = (int)($task->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO) === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT;
            //根据工作模式获取对应配置的发布时段
            $schedules = MarketingTemplateSchedule::where('template_id', $persona->workflow_template_id)
                ->where('scene', 5)
                ->field('id,platform')
                ->select();
            //根据设备id和用户id获取已启用的时段id
            $userRemoveIds = AiPersonaWorkflowScheduleUser::where('persona_id', $persona->id)
                ->where('template_id', $persona->workflow_template_id)
                ->where('user_id', $task->user_id)
                ->where('scene', 5)
                ->where('status', 0)
                ->column('schedule_id');
            $disabledIds = array_map('intval', $userRemoveIds);
            $count = 0;
            foreach ($schedules as $schedule) {
                if (in_array((int)$schedule->id, $disabledIds, true)) {
                    continue;
                }
                if ($isImageText && !$this->scheduleContainsPlatform($schedule->platform, AiPersona::PUBLISH_PLATFORM_XHS)) {
                    continue;
                }
                $count++;
            }

            return $count;
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    private function scheduleContainsPlatform(mixed $platforms, int $platform): bool
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

    /**
     * 获取任务配置（含关联账号）
     */
    private function getTaskConfig(int $taskId)
    {
        try {
            
            return SvDeviceViral::alias('ps')
                ->field('ps.*,s.id as viral_account_id,s.account_type,s.account,s.nickname,s.avatar,s.duration as duration_filter,IF(s.publish_platform > 0, s.publish_platform, s.account_type) as publish_platform,IF(s.publish_media_type > 0, s.publish_media_type, IFNULL(ps.publish_media_type, 1)) as publish_media_type')
                ->join('sv_device_viral_account s', 's.viral_id = ps.id')
                ->where('ps.id', $taskId)
                ->where('s.device_code', '=', $this->payload['deviceId'])
                ->where('s.account_type', $this->appType)
                ->limit(1)
                ->findOrEmpty();
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 30天内成功记录去重检查
     */
    private function isDuplicateInPool(int $userId, string $hash): bool
    {
        try {
            $exists = SvDeviceViralRecord::where('user_id', $userId)
                ->where('hash', $hash)
                //->where('status', 4)
                ->where('create_time', '>', time() - 30 * 86400)
                ->order('id', 'desc')
                ->findOrEmpty();
            if (!$exists->isEmpty()) {
                $this->setLog('该链接已在30天历史池中，请抓取下一个视频' . \think\facade\Db::getLastSql(), 'viral_rewrite');
                return true;
            }
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage(), $th->getCode());
        }

        return false;
    }

    /**
     * 创建处理记录
     */
    private function createRecord(SvDeviceViral $task, string $shareContent, string $hash, string $keyword, string $image, array $content, string $day, array $extra = []): SvDeviceViralRecord
    {
        try {
            if (!array_key_exists('title_normalized', $extra)) {
                $extra['title_normalized'] = ViralShareTextNormalizer::normalize($shareContent);
            }
            return SvDeviceViralRecord::create(array_merge([
                'user_id'          => $task->user_id,
                'viral_id'         => $task->id,
                'viral_account_id' => $task->viral_account_id,
                'auto_type'        => $task->auto_type,
                'device_code'      => $this->payload['deviceId'],
                'account'          => $task->account,
                'nickname'         => $task->nickname,
                'persona_id'       => $task->persona_id,
                'keyword'          => $keyword,
                'content'          => $shareContent,
                'generation_types' => $task->generation_types,
                'publish_platform' => (int)($task->publish_platform ?? $task->account_type ?? 4),
                'publish_media_type' => (int)($task->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO),
                'video_duration'   => 0,
                'image'            => $image,
                'day'              => $day,
                'likes'            => $content['likes'] ?? 0,
                'comments'         => $content['comments'] ?? 0,
                'status'           => 0,
                'retry'            => 0,
                'hash'             => $hash,
                'create_time'      => time(),
            ], $extra));
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 视频解析并自动重试（最多3次）
     * @return array ['success' => bool, 'audio_text' => string, 'duration' => int, 'last_error' => string]
     */
    private function parseVideoWithRetry(string $shareContent, SvDeviceViralRecord $record): array
    {
        try {
            $lastErrorMsg = '';
            for ($i = $record->retry; $i < self::VIDEO_PARSE_MAX_RETRY; $i++) {
                try {
                    $response = ToolsService::VideoImitation()->video2text($shareContent);
                    $this->setLog($record->device_code . "视频解析第" . ($i + 1) . "次结果: " . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'viral_rewrite');
                    $audioText = '';
                    if (isset($response['code']) && $response['code'] == 10000) {
                        $resData = $response['data'] ?? [];
                        $audioText = trim($resData['audio_text'] ?? '');

                        // 如果文案为空，降级调用MCP
                        if (empty($audioText)) {
                            $requestParams = [
                                'input' => [
                                    'prompt' => $shareContent
                                ],
                                'version' => 'v2'
                            ];
                            $this->setLog($record->device_code . "MCP视频视频解析请求参数: " . json_encode($requestParams, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'viral_rewrite');
                            $res = ToolsService::Copywriting()->videoImitation($requestParams);
                            $this->setLog($record->device_code . "MCP视频解析结果: " . json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'viral_rewrite');
                            if (isset($res['code']) && $res['code'] == 10000) {
                                $data = $res['data'] ?? [];
                                $messageJson = $data['message'] ?? '';
                                $parsedMsg = json_decode($messageJson, true);
                                $this->setLog($record->device_code . "MCP视频解析messageJson结果: " . json_encode($parsedMsg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'viral_rewrite');
                                if ($parsedMsg) {
                                    $audioText = $parsedMsg['original_text'] ?? '';
                                }
                            }
                        }

                        return [
                            'success'    => true,
                            'audio_text' => $audioText,
                            'duration'   => $resData['duration'] ?? 0,
                            'last_error' => ''
                        ];
                    }
                    $lastErrorMsg = $response['msg'] ?? '视频解析失败';
                } catch (\Exception $e) {
                    $lastErrorMsg = '解析异常: ' . $e->getMessage();
                    $this->setLog($record->device_code . "视频解析异常: {$e->getMessage()}", 'viral_rewrite');
                }

                $record->retry = $i + 1;
                $record->update_time = time();
                $record->save();
            }

            $record->remark = '视频解析多次失败，原因: ' . $lastErrorMsg;
            $record->save();
            $this->setLog($record->device_code . "视频解析全部失败，开始降级生成", 'viral_rewrite');
            return ['success' => false, 'audio_text' => '', 'duration' => 0, 'last_error' => $lastErrorMsg];
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 无文案判断（字数<30）
     */
    private function isNoScript(string $audioText): bool
    {
        try {
            return empty($audioText) || mb_strlen($audioText) < 30;
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 校验解析后的视频时长是否符合热点追踪筛选配置
     * @return string|null 不符合时返回丢弃原因，符合时返回 null
     */
    private function matchTrackingDuration(int $actualSeconds, int $filter): ?string
    {
        $filter = in_array($filter, [
            self::DURATION_FILTER_UNLIMITED,
            self::DURATION_FILTER_WITHIN_1MIN,
            self::DURATION_FILTER_1_TO_5MIN,
            self::DURATION_FILTER_ABOVE_5MIN,
        ], true) ? $filter : self::DURATION_FILTER_UNLIMITED;

        switch ($filter) {
            case self::DURATION_FILTER_WITHIN_1MIN:
                if ($actualSeconds > self::DURATION_1MIN_SECONDS) {
                    return '视频时长不符合筛选(需一分钟以下,实际' . $actualSeconds . '秒)';
                }
                break;
            case self::DURATION_FILTER_1_TO_5MIN:
                if ($actualSeconds <= self::DURATION_1MIN_SECONDS || $actualSeconds > self::DURATION_5MIN_SECONDS) {
                    return '视频时长不符合筛选(需一到5分钟,实际' . $actualSeconds . '秒)';
                }
                break;
            case self::DURATION_FILTER_ABOVE_5MIN:
                if ($actualSeconds < self::DURATION_5MIN_SECONDS) {
                    return '视频时长不符合筛选(需5分钟以上,实际' . $actualSeconds . '秒)';
                }
                break;
        }

        return null;
    }

    /**
     * 扣取基础成本价（无文案时仅扣1 unit）
     */
    private function deductBaseCost(int $userId, int $duration, string $taskId): void
    {
        try {
            // checkToken 内部已按企业钱包/个人算力预检
            $unit = TokenLogService::checkToken($userId, 'video_imitation_copywriting_parse');
            if ($unit > 0) {
                User::userTokensChange($userId, $unit);
                $extra = [
                    '扣费项目' => '视频文案提取(成本费)',
                    '算力单价' => $unit,
                    '原视频时长' => $duration . '秒',
                    '实际消耗算力' => $unit,
                    '场景' => '24h'
                ];
                AccountLogLogic::recordUserTokensLog(
                    true,
                    $userId,
                    AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE,
                    $unit,
                    $taskId,
                    $extra
                );
            }
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 扣取视频文案提取费用（按分钟计费）
     */
    private function deductParseFee(int $userId, int $duration, string $taskId): void
    {
        try {
            // checkToken 内部已按企业钱包/个人算力预检(按分钟实扣额再检一次)
            $unit = TokenLogService::checkToken($userId, 'video_imitation_copywriting_parse');
            $minutes = ceil($duration / 60);
            $deductPoint = $unit * $minutes;
            if ($deductPoint > 0) {
                $spendable = \app\common\service\TeamBillingService::spendableTokens($userId);
                if ($spendable < $deductPoint) {
                    $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                        ? '当前团队算力不足，请联系团队主' : '用户算力不足';
                    throw new \Exception($msg, WorkerEnum::TASK_TOKEN_ERROR);
                }
                User::userTokensChange($userId, $deductPoint);
                $extra = [
                    '扣费项目' => '视频文案提取',
                    '算力单价' => $unit,
                    '原视频时长' => $duration . '秒',
                    '实际消耗算力' => $deductPoint,
                    '场景' => '24h'
                ];
                AccountLogLogic::recordUserTokensLog(
                    true,
                    $userId,
                    AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE,
                    $deductPoint,
                    $taskId,
                    $extra
                );
            }
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 意图检验（当前为直接放行，可接入Coze工作流）
     */
    private function checkIntentRelevance(string $audioText, AiPersona $persona): bool
    {
        // TODO: 实际应调用 Coze 工作流或 NLP 服务进行判断
        // 这里示例实现，可替换为 ToolsService::checkContentRelevance($audioText, $persona)
        try {
            $rule = null;
            if ($persona->persona_type == 1) {
                $rule = $persona->individual;
            } elseif ($persona->persona_type == 2) {
                $rule = $persona->enterprise;
            } elseif ($persona->persona_type == 3) {
                $rule = $persona->local;
            }
            if (empty($rule)) {
                return true; // 无参考则放行
            }

            $response = \app\common\service\ToolsService::Coze()->checkIntentRelevance([
                'keywords' => $audioText,
                'persona' => $rule->getClueContent($persona),
            ]);
            $this->setLog($this->payload['deviceId'] . '意图检验结果:' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'viral_rewrite');
            if ((int)$response['code'] !== 10000) {
                return false;
            }
            $result = $response['data'] ?? [];
            return (int)$result['content']['result'] === 1 ? false : true;
        } catch (\Exception $e) {
            $this->setLog($this->payload['deviceId'] . "意图检验异常: {$e->getMessage()}", 'viral_rewrite');
            return true;
        }
    }

    /**
     * 执行仿写生成并存储结果
     */
    private function generateAndStoreCopywriting(SvDeviceViralRecord $record, SvDeviceViral $task, string $audioText, AiPersona $persona): array
    {
        try {
            $rule = null;
            if ($persona->persona_type == 1) {
                $rule = $persona->individual;
            } elseif ($persona->persona_type == 2) {
                $rule = $persona->enterprise;
            } elseif ($persona->persona_type == 3) {
                $rule = $persona->local;
            }

            $productContent = "我的IP人设产品内容：\n主营业务/产品：{$persona['main_business']}\n目标客户与痛点：{$persona['target_pain_points']}\n差异化优势与行为引导：{$persona['conversion_hook']}";
            $promptContent  = "我的IP人设内容是：\n" . $rule->getClueContent($persona) . "\n\n" . $productContent . "\n\n视频文案：\n{$audioText}";
            $titlecoze['keywords'] = $promptContent;

            // 文案仿写：rewritten_text 为空或异常时重试，最多 COPYWRITING_GENERATE_MAX_RETRY 次
            $rewrittenText   = '';
            $lastException   = null;
            for ($attempt = 1; $attempt <= self::COPYWRITING_GENERATE_MAX_RETRY; $attempt++) {
                // 每次 attempt 都重置为 null，防止异常 continue 后沿用上一轮结果（避免重复退费）
                $imitationResult = null;
                try {
                    $this->setLog(
                        $record->device_code . "文案仿写第{$attempt}次请求参数: " . json_encode([
                            'keywords' => $titlecoze['keywords'] ?? '',
                            'userId' => $task->user_id,
                            'type' => 5,
                            'withBillingMeta' => true,
                        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                        'viral_rewrite'
                    );
                    $titlecoze['persona'] = $rule->getClueContent($persona);
                    $titlecoze['original'] = $audioText;
                    $titlecoze['voice'] = '';
                    $titlecoze['hook'] = '';
                    $titlecoze['model'] = 0;

                    $imitationResult = \app\api\logic\auto\AutoDeviceSettingLogic::copywriting($titlecoze, $task->user_id, 7, true);
                    $lastException = null;
                } catch (\Throwable $e) {
                    $this->setLog($record->device_code . "文案仿写第{$attempt}次异常: {$e->getMessage()}", 'viral_rewrite');
                    if ((int)$e->getCode() === WorkerEnum::TASK_TOKEN_ERROR) {
                        throw $e;
                    }
                    $lastException = $e;
                    $imitationResult = null;
                    continue;
                }

                $this->setLog($record->device_code . "文案仿写第{$attempt}次结果: " . json_encode($imitationResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'viral_rewrite');

                $rewrittenText = trim((string)($imitationResult['content']['rewritten_text'] ?? ''));
                if ($rewrittenText !== '') {
                    break;
                }
                $refundStatus = $this->refundEmptyCopywriting($imitationResult, (int)$task->user_id, $record->device_code, $attempt);
                $this->setLog(
                    $record->device_code . "文案仿写第{$attempt}次结果 rewritten_text 为空，退费状态={$refundStatus}，将重试",
                    'viral_rewrite'
                );
            }

            // 重试耗尽仍为空（含全部异常）：降级生成兜底
            if ($rewrittenText === '') {
                $reason = $lastException !== null ? '文案仿写异常重试耗尽' : '文案仿写重试' . self::COPYWRITING_GENERATE_MAX_RETRY . '次后 rewritten_text 仍为空';
                $this->setLog($record->device_code . $reason . "，开始降级生成", 'viral_rewrite');
                return $this->degradedGenerate($record, $task, $reason);
            }

            $record->copywriting      = $imitationResult['content'] ?? [];
            $record->copywriting_type = 1;
            $record->status           = 4;
            $record->remark           = '文案生成成功';
            $record->update_time      = time();
            $record->save();

            AiPersonaSynthesisCopywriting::create([
                'user_id'                    => $task->user_id,
                'device_code'                => $this->payload['deviceId'],
                'persona_id'                 => $task->persona_id,
                'sv_device_viral_record_id'  => $record->id,
                'publish_media_type'         => AiPersona::PUBLISH_MEDIA_TYPE_VIDEO,
                'copywriting'                => is_string($imitationResult['content']) ? $imitationResult['content'] : json_encode($imitationResult['content'] ?? [], JSON_UNESCAPED_UNICODE),
                'status'                     => 2,
                'day'                        => $record->day,
                'create_time'                => time(),
            ]);

            ViralKeywordService::consumeOnSuccess($persona, $task, (string)$record->keyword);

            return ['isContinue' => 2, 'msg' => '文案生成成功,执行下一个关键词'];
        } catch (\Throwable $th) {
            //throw $th;
            $this->setLog($record->device_code . "文案仿写失败: " . $th->getTraceAsString(), 'viral_rewrite');
            $record->status = 5;
            $record->copywriting_type = 4;
            $record->remark = '文案生成失败：' . $th->getMessage();
            $record->update_time      = time();
            $record->save();
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 文案仿写空结果退费：仅当上游 code=10000 已扣费但 rewritten_text 为空时退回算力
     * 同一 task_id 只允许一次退费（幂等），防止重复退费造成资金损失
     *
     * @return string refunded|skipped_no_charge|skipped_already_refunded|failed
     */
    private function refundEmptyCopywriting(array $result, int $userId, string $deviceCode, int $attempt): string
    {
        $billing = $result['_billing'] ?? null;
        if (empty($billing) || empty($billing['charged']) || (float)($billing['points'] ?? 0) <= 0) {
            $this->setLog($deviceCode . "文案仿写第{$attempt}次空结果无需退费（未扣费或算力为0）", 'viral_rewrite');
            return 'skipped_no_charge';
        }
        // 退费幂等兜底：同一 task_id 已经有 INC 记录则不再退
        $taskId = (string)($billing['task_id'] ?? '');
        if ($taskId !== '') {
            $refunded = UserTokensLog::where('task_id', $taskId)
                ->where('action', AccountLogEnum::INC)
                ->count();
            if ($refunded > 0) {
                $this->setLog($deviceCode . "文案仿写第{$attempt}次空结果跳过（task_id={$taskId} 已退过费）", 'viral_rewrite');
                return 'skipped_already_refunded';
            }
        }
        try {
            AccountLogLogic::recordUserTokensLog(
                false,
                $userId,
                (int)$billing['token_code'],
                (float)$billing['points'],
                $taskId,
                [
                    '扣费项目'     => '文案仿写生成(空结果退费)',
                    '算力单价'     => $billing['points'],
                    '实际消耗算力' => $billing['points'],
                    '重试次数'     => $attempt,
                ]
            );
            $this->setLog($deviceCode . "文案仿写第{$attempt}次空结果退费成功，task_id={$taskId}，points={$billing['points']}", 'viral_rewrite');
            return 'refunded';
        } catch (\Throwable $e) {
            $this->setLog($deviceCode . "文案仿写第{$attempt}次空结果退费失败: {$e->getMessage()}", 'viral_rewrite');
            return 'failed';
        }
    }

    // 降级生成及其他辅助方法保持不变（略）
    /**
     * 降级处理：当视频提取失败3次后，直接由 Coze 根据人设纯AI生成文案
     */
    private function degradedGenerate(SvDeviceViralRecord $record, SvDeviceViral $task, string $reason = '视频解析失败')
    {
        try {
            $this->setLog($record->device_code . "{$reason}，开始降级生成文案", 'viral_rewrite');
            $persona = \app\common\model\aiPersona\AiPersona::where('id', $task->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                $record->status = 5;
                $record->copywriting_type = 4;
                $record->remark = '降级失败：IP人设不存在';
                $record->save();
                $this->setLog($record->device_code . '系统配置异常，任务中断', 'viral_rewrite');
                return [
                    'isContinue' => 1,
                    'msg'        => '降级失败，获取下一个视频',
                ];
            }

            $rule = null;
            if ($persona->persona_type == 1) {
                $rule = $persona->individual;
            } elseif ($persona->persona_type == 2) {
                $rule = $persona->enterprise;
            } elseif ($persona->persona_type == 3) {
                $rule = $persona->local;
            }

            $productContent = "我的IP人设产品内容：\n主营业务/产品：{$persona['main_business']}\n目标客户与痛点：{$persona['target_pain_points']}\n差异化优势与行为引导：{$persona['conversion_hook']}";
            // 没有视频原文，只靠人设和产品生成
            $promptContent = "我的IP人设内容是：\n" . $rule->getClueContent($persona) . "\n\n" . $productContent . "\n\n请根据以上信息，生成一篇吸引人的营销文案。";
            $titlecoze['keywords'] = $promptContent;

            $this->setLog(
                $record->device_code . "降级文案仿写请求参数: " . json_encode([
                    'keywords' => $titlecoze['keywords'] ?? '',
                    'userId' => $task->user_id,
                    'type' => 5,
                    'withBillingMeta' => true,
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'viral_rewrite'
            );
            $titlecoze['persona'] = $rule->getClueContent($persona);
            $titlecoze['original'] = '';
            $titlecoze['voice'] = '';
            $titlecoze['hook'] = '';
            $titlecoze['model'] = 0;

            $imitationResult = \app\api\logic\auto\AutoDeviceSettingLogic::copywriting($titlecoze, $task->user_id, 7, true);

            // 修复#4：降级结果校验 rewritten_text，为空则退费并标记失败（降级不重试，按计划仅正常仿写重试3次）
            $degradedText = trim((string)($imitationResult['content']['rewritten_text'] ?? ''));
            if ($degradedText === '') {
                $refundStatus = $this->refundEmptyCopywriting($imitationResult, (int)$task->user_id, $record->device_code, 0);
                $record->status = 5;
                $record->copywriting_type = 4;
                $record->remark = $reason . '：降级生成结果为空';
                $record->update_time = time();
                $record->save();
                $this->setLog($record->device_code . "ViralRewriter降级生成结果为空，退费状态={$refundStatus}，已标记失败，记录:{$record->id}", 'viral_rewrite');
                return [
                    'isContinue' => 1,
                    'msg'        => '降级生成结果为空，获取下一个视频',
                ];
            }

            $previousRemark = trim((string)($record->remark ?? ''));
            $defaultRemark = $reason . '，已降级使用AI生成文案';
            $record->copywriting      = $imitationResult['content'] ?? [];
            $record->copywriting_type = 4;
            $record->status           = 3;   // 降级处理
            $record->remark           = $defaultRemark;
            $record->update_time      = time();
            $record->save();

            AiPersonaSynthesisCopywriting::create([
                'user_id' => $task->user_id,
                'device_code' => $this->payload['deviceId'],
                'persona_id' => $task->persona_id,
                'sv_device_viral_record_id' => $record->id,
                'publish_media_type' => AiPersona::PUBLISH_MEDIA_TYPE_VIDEO,
                'copywriting' => is_string($imitationResult['content']) ? $imitationResult['content'] : json_encode($imitationResult['content'] ?? [], JSON_UNESCAPED_UNICODE),
                'status' => 1,
                'day' => $record->day,
                'create_time' => time(),
            ]);

            //$failureRemark = $this->resolveDegradedFailureRemark($record, $task, $previousRemark, $defaultRemark);
            //$this->createDegradedFallbackErrorRecord($record, $task, $defaultRemark);

            $this->setLog($record->device_code . "ViralRewriter降级生成成功，记录 :{$record->id}", 'viral_rewrite');
            return [
                'isContinue' => 2,
                'msg'        => '降级生成文案成功,执行下一个关键词',
            ];
        } catch (\Throwable $e) {
            $this->setLog($record->device_code . "ViralRewriter降级生成失败：{$e->getMessage()}", 'viral_rewrite');
            $record->status = 5;
            $record->remark = '降级生成失败: ' . $e->getMessage();
            $record->save();
            return [
                'isContinue' => 1,
                'msg'        => '降级处理异常，获取下一个视频',
            ];
        }
    }

    /**
     * 降级 status=7 的 remark：优先同维度最新 status=5，其次当前记录覆盖前 remark，最后默认文案。
     */
    private function resolveDegradedFailureRemark(
        SvDeviceViralRecord $record,
        SvDeviceViral $task,
        string $previousRemark,
        string $defaultRemark
    ): string {
        try {
            $remark = SvDeviceViralRecord::where('device_code', (string)$record->device_code)
                ->where('day', (string)$record->day)
                ->where('persona_id', (int)$task->persona_id)
                ->where('publish_media_type', (int)($task->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO))
                ->where('status', 5)
                ->order('id', 'desc')
                ->value('remark');
            $remark = trim((string)$remark);
            if ($remark !== '') {
                return $remark;
            }
        } catch (\Throwable $th) {
            $this->setLog($record->device_code . '降级获取最新异常remark失败: ' . $th->getMessage(), 'viral_rewrite');
        }

        if ($previousRemark !== '') {
            return $previousRemark;
        }

        return $defaultRemark;
    }

    /**
     * 降级生成成功后写入 status=7 错误标记记录（不占配额）。
     */
    private function createDegradedFallbackErrorRecord(
        SvDeviceViralRecord $record,
        SvDeviceViral $task,
        string $failureRemark
    ): void {
        try {
            $now = time();
            $taskDay = (string)($record->day ?: date('Y-m-d'));
            $dayKey = str_replace('-', '', $taskDay);
            $errorContent = 'degraded_generate_error://'
                . $task->id . '/' . $dayKey . '/' . uniqid('', true);
            $errorHash = hash('sha256', $errorContent);

            $useTime = strtotime($taskDay . ' ' . date('H:i:s')) ?: $now;
            $errorRecord = SvDeviceViralRecord::create([
                'user_id'            => $task->user_id,
                'viral_id'           => $task->id,
                'viral_account_id'   => $task->viral_account_id,
                'auto_type'          => $task->auto_type,
                'device_code'        => $this->payload['deviceId'] ?? $record->device_code,
                'account'            => $task->account,
                'nickname'           => $task->nickname,
                'persona_id'         => $task->persona_id,
                'keyword'            => $record->keyword ?? '',
                'generation_types'   => $task->generation_types,
                'publish_platform'   => (int)($task->publish_platform ?? $task->account_type ?? 4),
                'publish_media_type' => (int)($task->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO),
                'video_duration'     => 0,
                'content'            => $errorContent,
                'hash'               => $errorHash,
                'copywriting'        => [],
                'copywriting_type'   => SvDeviceViralRecord::COPYWRITING_TYPE_FALLBACK_ERROR,
                'status'             => SvDeviceViralRecord::STATUS_FALLBACK_ERROR,
                'remark'             => $failureRemark,
                'day'                => $taskDay,
                'use_time'           => $useTime,
                'retry'              => 0,
                'create_time'        => $now,
                'update_time'        => $now,
            ]);

            $this->setLog(
                $record->device_code
                . "降级生成写入status=7成功: 主记录ID={$record->id}, 错误记录ID={$errorRecord->id}, remark={$failureRemark}",
                'viral_rewrite'
            );
        } catch (\Throwable $th) {
            $this->setLog(
                $record->device_code
                . "降级生成写入status=7失败: 主记录ID={$record->id}, 错误=" . $th->getMessage(),
                'viral_rewrite'
            );
        }
    }
}
