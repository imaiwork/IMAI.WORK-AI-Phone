<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\api\logic\service\TokenLogService;
use app\common\enum\DeviceEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvDySearchRecord;
use app\common\model\sv\SvDySearchSeen;
use app\common\model\sv\SvDySearchTask;
use app\common\model\sv\SvDySearchTaskDevice;
use app\common\model\user\User;
use app\common\service\display\DySearchIntentionMapper;
use app\common\service\display\IntentionCustomerService;
use think\facade\Db;
use think\facade\Log;

/**
 * 抖音获客 1008 走访入库。handler 只解析回包，业务集中在此便于单测。
 */
class DySearchIngestService
{
    public const RESULT_STORED = 1;
    public const RESULT_UNRECOGNIZED = 2;
    public const RESULT_DM = 3;
    public const RESULT_PREFILTER = 4;
    public const RESULT_EXISTS = 5;
    public const RESULT_DEFAULT_ID = 6;
    public const RESULT_DAY_CAP = 7;
    public const RESULT_KW_CAP = 8;

    /**
     * @param array<string, mixed> $content
     * @return array{
     *   ok: bool,
     *   discard: bool,
     *   code: int,
     *   msg: string,
     *   result: int,
     *   reg_content: string,
     *   clue_type: int,
     *   tokens: float|int
     * }
     */
    public static function ingest(array $content, string $deviceCode): array
    {
        $ack = self::emptyAck();
        $deviceCode = trim($deviceCode);
        $dyUid = trim((string)($content['dy_uid'] ?? ''));
        $taskId = (int)($content['task_id'] ?? 0);
        $bindIdIn = (int)($content['bind_id'] ?? 0);

        if (self::isMissingDyUid($content)) {
            self::logEvent('上报缺少抖音用户标识，已丢弃', [
                'task_id' => $taskId,
                'device' => $deviceCode,
                'bind_id' => $bindIdIn,
            ]);
            $ack['discard'] = true;
            $ack['msg'] = '缺少用户标识';
            return $ack;
        }

        $task = SvDySearchTask::where('id', $taskId)->findOrEmpty();
        if ($task->isEmpty()) {
            self::logEvent('走访上报任务不存在', [
                'task_id' => $taskId,
                'device' => $deviceCode,
                'dy_uid' => $dyUid,
            ]);
            $ack['msg'] = '获客任务不存在';
            return $ack;
        }

        $taskStatus = (int)$task->status;
        if (in_array($taskStatus, [2, 3, 4], true)) {
            $reason = $taskStatus === 2 ? '任务已暂停' : '任务已结束';
            self::logEvent('走访上报已拒绝：' . $reason, [
                'task_id' => $taskId,
                'device' => $deviceCode,
                'dy_uid' => $dyUid,
                'status' => $taskStatus,
            ]);
            $ack['msg'] = $reason;
            return $ack;
        }

        $bind = self::resolveBind($taskId, $deviceCode, $bindIdIn);
        if ($bind === null) {
            self::logEvent('走访上报已拒绝：设备未绑定该任务', [
                'task_id' => $taskId,
                'device' => $deviceCode,
                'bind_id' => $bindIdIn,
                'dy_uid' => $dyUid,
            ]);
            $ack['msg'] = '设备未绑定该获客任务';
            return $ack;
        }
        $bindStatus = (int)$bind->status;
        if (!in_array($bindStatus, [0, 1], true)) {
            $reason = $bindStatus === 2 ? '设备已暂停该获客任务' : '设备已完成该获客任务';
            self::logEvent('走访上报已拒绝：' . $reason, [
                'task_id' => $taskId,
                'device' => $deviceCode,
                'bind_id' => (int)$bind->id,
                'dy_uid' => $dyUid,
                'bind_status' => $bindStatus,
            ]);
            $ack['msg'] = $reason;
            return $ack;
        }

        $userId = (int)$task->user_id;
        $scanConfig = self::normalizeScanConfig($task->scan_config ?? []);
        $contactOpts = [
            'contact_phone' => (bool)($scanConfig['contact_phone'] ?? true),
            'contact_wechat' => (bool)($scanConfig['contact_wechat'] ?? true),
            'contact_qq' => (bool)($scanConfig['contact_qq'] ?? false),
        ];

        $bio = trim((string)($content['bio'] ?? ''));
        $dyId = trim((string)($content['dy_id'] ?? ''));
        $ocrText = trim((string)($content['ocr_text'] ?? ''));
        $nickname = trim((string)($content['nickname'] ?? ''));
        $addRule = (string)($task->add_rule ?? 'all');
        $scanDyid = (bool)($scanConfig['scan_dyid'] ?? true);
        $scanned = self::buildExtracted($bio, $dyId, $ocrText, $contactOpts, $addRule, $scanDyid);
        $extracted = self::applyAddRuleToExtracted($scanned, $addRule);
        $regContent = implode(',', $extracted['raw']);
        $hash = $regContent === '' ? '' : sha1($regContent);
        $clueType = self::decideClueType($extracted);
        $hasContacts = $extracted['raw'] !== [];

        $existing = SvDySearchRecord::withTrashed()
            ->where('task_id', $taskId)
            ->where('dy_uid', $dyUid)
            ->findOrEmpty();
        $isUpdate = !$existing->isEmpty();
        if ($isUpdate && (int)($existing->delete_time ?? 0) > 0) {
            $existing->delete_time = null;
        }
        $keepStored = $isUpdate && ((int)$existing->result === self::RESULT_STORED || (int)$existing->tokens > 0);

        $seen = SvDySearchSeen::where('user_id', $userId)->where('dy_uid', $dyUid)->findOrEmpty();
        $skipExistingCfg = (bool)($scanConfig['skip_existing'] ?? true);
        $hashExists = $hash !== '' && SvDySearchDedupService::existsCrawlingHash($userId, $hash);
        $skipExisting = !$isUpdate && $skipExistingCfg && (!$seen->isEmpty() || $hashExists);

        $dayCap = (int)($task->day_cap ?? 0);
        $dayCapReached = false;
        if ($dayCap > 0 && $hasContacts && !$keepStored) {
            $todayCount = self::countTodayStored($userId, $deviceCode);
            $dayCapReached = $todayCount >= $dayCap;
        }

        $ctx = [
            'is_default_id' => DyDefaultIdFilter::isDefaultId($dyId),
            'skip_existing' => $skipExisting,
            'day_cap_reached' => $dayCapReached,
            'device_result' => self::parseDeviceResult($content['result'] ?? 0),
            'miss_action' => (string)($scanConfig['miss_action'] ?? 'dm'),
            'dm_sent' => self::toBool($content['dm_sent'] ?? 0),
        ];

        if ($keepStored) {
            $result = self::RESULT_STORED;
            $regContent = (string)($existing->reg_content ?: $regContent);
            $clueType = (int)($existing->clue_type ?: $clueType);
            $hash = (string)($existing->hash ?: $hash);
        } else {
            $result = self::decideResult($extracted, $ctx);
        }

        $ack['result'] = $result;
        $ack['reg_content'] = $result === self::RESULT_STORED ? $regContent : ($regContent);
        $ack['clue_type'] = $result === self::RESULT_STORED ? ($clueType ?: self::decideClueType($extracted)) : self::decideClueType($extracted);

        $needCharge = $result === self::RESULT_STORED && !$keepStored;
        $points = 0.0;
        $crawlLock = null;
        if ($needCharge && $hash !== '') {
            $crawlLock = SvDySearchDedupService::claimCrawlingHash($userId, $hash);
            if ($crawlLock === null) {
                $result = self::RESULT_EXISTS;
                $needCharge = false;
                $ack['result'] = $result;
            }
        }

        if ($needCharge) {
            try {
                $points = (float)TokenLogService::checkToken($userId, 'dy_search_clue');
            } catch (\Throwable $e) {
                if ($hash !== '' && $crawlLock !== null) {
                    SvDySearchDedupService::releaseCrawlingHash($userId, $hash, $crawlLock);
                }
                if ((int)$e->getCode() === 4059) {
                    self::logEvent('走访入库算力不足，已通知设备停手', [
                        'task_id' => $taskId,
                        'device' => $deviceCode,
                        'bind_id' => (int)$bind->id,
                        'dy_uid' => $dyUid,
                    ]);
                    $ack['ok'] = false;
                    $ack['code'] = 4059;
                    $ack['msg'] = $e->getMessage() !== '' ? $e->getMessage() : '算力不足';
                    $ack['result'] = 0;
                    $ack['reg_content'] = $regContent;
                    $ack['clue_type'] = $clueType;
                    return $ack;
                }
                throw $e;
            }
        }

        $bindId = (int)$bind->id;
        $execKeyword = trim((string)($content['exec_keyword'] ?? ''));
        $scanSource = self::buildScanSource($bio, $dyId, $ocrText, $contactOpts, $extracted);
        $coverUrls = self::normalizeCoverUrls($content['cover_urls'] ?? []);
        $action = self::seenAction($result);
        $localAccount = self::localDyAccount($deviceCode, $userId);
        $isVerify = self::decideIsVerify((int)$task->add_type === 1, $scanConfig, $scanned, $result, $addRule);

        try {
            Db::startTrans();
            SvDySearchTaskDevice::where('id', $bindId)->lock(true)->find();
            if ($needCharge && $dayCap > 0 && self::countTodayStored($userId, $deviceCode) >= $dayCap) {
                $result = self::RESULT_DAY_CAP;
                $needCharge = false;
                $ack['result'] = $result;
                $action = self::seenAction($result);
                $isVerify = self::decideIsVerify((int)$task->add_type === 1, $scanConfig, $scanned, $result, $addRule);
            }
            $perKw = (int)($task->per_kw ?? 0);
            if ($needCharge && $perKw > 0 && $execKeyword !== '' && self::countKeywordStored($taskId, $execKeyword) >= $perKw) {
                $result = self::RESULT_KW_CAP;
                $needCharge = false;
                $ack['result'] = $result;
                $action = self::seenAction($result);
                $isVerify = self::decideIsVerify((int)$task->add_type === 1, $scanConfig, $scanned, $result, $addRule);
            }

            $row = [
                'user_id' => $userId,
                'task_id' => $taskId,
                'bind_id' => $bindId,
                'device_code' => $deviceCode,
                'exec_keyword' => $execKeyword,
                'dy_uid' => $dyUid,
                'dy_id' => $dyId,
                'nickname' => $nickname,
                'avatar' => (string)($content['avatar'] ?? ''),
                'fans' => (int)($content['fans'] ?? 0),
                'works' => (int)($content['works'] ?? 0),
                'is_enterprise' => (int)($content['is_enterprise'] ?? 0),
                'scan_source' => $scanSource,
                'ocr_text' => $ocrText !== '' ? $ocrText : $bio,
                'enterprise_text' => trim((string)($content['enterprise_text'] ?? '')),
                'reg_content' => $result === self::RESULT_STORED ? $regContent : ($keepStored ? $regContent : ''),
                'clue_type' => $ack['clue_type'],
                'result' => $result,
                'hash' => $result === self::RESULT_STORED ? $hash : ($keepStored ? $hash : ''),
                'cover_urls' => $coverUrls,
                'stitch_url' => (string)($content['stitch_url'] ?? ''),
                'tokens' => $needCharge ? $points : (int)($isUpdate ? $existing->tokens : 0),
                'exec_time' => date('Y-m-d H:i:s'),
                'update_time' => time(),
            ];
            if (!($isUpdate && $keepStored)) {
                $row['is_verify'] = $isVerify;
            }

            if ($isUpdate) {
                if ($keepStored) {
                    $row['reg_content'] = (string)$existing->reg_content;
                    $row['clue_type'] = (int)$existing->clue_type;
                    $row['result'] = self::RESULT_STORED;
                    $row['hash'] = (string)$existing->hash;
                    $row['tokens'] = (int)$existing->tokens;
                    $stored = $existing->getData();
                    $row = self::preserveStoredScanFields($row, is_array($stored) ? $stored : []);
                }
                $existing->save($row);
            } else {
                $row['create_time'] = time();
                try {
                    SvDySearchRecord::create($row);
                } catch (\Throwable $e) {
                    if (!self::isDuplicateKey($e)) {
                        throw $e;
                    }
                    $dup = SvDySearchRecord::withTrashed()
                        ->where('task_id', $taskId)
                        ->where('dy_uid', $dyUid)
                        ->findOrEmpty();
                    if ($dup->isEmpty()) {
                        throw $e;
                    }
                    $dup->delete_time = null;
                    if ((int)($dup->result ?? 0) === self::RESULT_STORED || (int)($dup->tokens ?? 0) > 0) {
                        $row['reg_content'] = (string)$dup->reg_content;
                        $row['clue_type'] = (int)$dup->clue_type;
                        $row['result'] = self::RESULT_STORED;
                        $row['hash'] = (string)$dup->hash;
                        $row['tokens'] = (int)$dup->tokens;
                        $stored = $dup->getData();
                        $row = self::preserveStoredScanFields($row, is_array($stored) ? $stored : []);
                    }
                    $dup->save($row);
                    $isUpdate = true;
                }
            }

            self::upsertSeen($userId, $dyUid, $dyId, $action, $taskId);

            if (!$isUpdate) {
                self::bumpCounts($taskId, $bind, $execKeyword, $result === self::RESULT_STORED);
            } elseif ($needCharge) {
                self::bumpCounts($taskId, $bind, $execKeyword, true, false);
            } elseif ($bind !== null && $execKeyword !== '') {
                $bind->exec_keyword = $execKeyword;
                $bind->update_time = time();
                $bind->save();
            }

            if ((int)$task->status === 0) {
                $task->status = 1;
                $task->update_time = time();
                $task->save();
            }

            if ($needCharge && $points > 0) {
                User::userTokensChange($userId, $points);
                $extra = ['算力单价' => $points . '算力/条', '实际消耗算力' => $points, '场景' => '抖音获客'];
                AccountLogLogic::recordUserTokensLog(
                    true,
                    $userId,
                    AccountLogEnum::TOKENS_DEC_DY_SEARCH_CLUE,
                    $points,
                    generate_unique_task_id(),
                    $extra
                );
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            if ($hash !== '' && $crawlLock !== null) {
                SvDySearchDedupService::releaseCrawlingHash($userId, $hash, $crawlLock);
            }
            if ((int)$e->getCode() === 4059) {
                self::logEvent('走访入库算力不足，已通知设备停手', [
                    'task_id' => $taskId,
                    'device' => $deviceCode,
                    'bind_id' => $bindId,
                    'dy_uid' => $dyUid,
                ]);
                $ack['ok'] = false;
                $ack['code'] = 4059;
                $ack['msg'] = $e->getMessage() !== '' ? $e->getMessage() : '算力不足';
                $ack['result'] = 0;
                return $ack;
            }
            throw $e;
        }

        if ($hash !== '' && $crawlLock !== null) {
            SvDySearchDedupService::releaseCrawlingHash($userId, $hash, $crawlLock);
        }

        if ($result === self::RESULT_STORED && ($needCharge || !$isUpdate)) {
            try {
                self::enqueueAddWechat(
                    $task,
                    $scanConfig,
                    $scanned,
                    $deviceCode,
                    $localAccount,
                    $dyId !== '' ? $dyId : $nickname,
                    trim($bio . "\n" . $ocrText),
                    $userId
                );
            } catch (\Throwable $e) {
                self::logEvent('写入加微队列失败，客资已保留：' . $e->getMessage(), [
                    'task_id' => $taskId,
                    'device' => $deviceCode,
                    'dy_uid' => $dyUid,
                ]);
            }
        }

        $ack['ok'] = true;
        $ack['msg'] = '走访上报成功';
        $ack['result'] = $result;
        $ack['tokens'] = $needCharge ? $points : 0;
        self::logEvent('走访上报已裁定', [
            'task_id' => $taskId,
            'device' => $deviceCode,
            'bind_id' => $bindId,
            'dy_uid' => $dyUid,
            'result' => $result,
            'reason' => self::resultReason($result, $needCharge, $keepStored),
            'tokens' => $needCharge ? $points : 0,
            'clue_type' => $ack['clue_type'],
        ]);
        if ($result === self::RESULT_STORED) {
            $ack['reg_content'] = $regContent;
            $ack['clue_type'] = $clueType;
        }
        IntentionCustomerService::clearCache($userId);
        return $ack;
    }

    /**
     * 服务端裁定走访结果。不采信设备 result=已入库。
     *
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>,raw?:list<string>} $extracted
     * @param array<string, mixed> $ctx
     */
    public static function decideResult(array $extracted, array $ctx): int
    {
        $has = !empty($extracted['raw']);
        $isDefault = (bool)($ctx['is_default_id'] ?? false);

        if (!$has && $isDefault) {
            return self::RESULT_DEFAULT_ID;
        }
        if ((bool)($ctx['skip_existing'] ?? false)) {
            return self::RESULT_EXISTS;
        }
        if ($has && (bool)($ctx['day_cap_reached'] ?? false)) {
            return self::RESULT_DAY_CAP;
        }
        if (!$has) {
            return self::RESULT_UNRECOGNIZED;
        }
        return self::RESULT_STORED;
    }

    /**
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>,raw?:list<string>} $extracted
     */
    public static function decideClueType(array $extracted): int
    {
        if (!empty($extracted['phones'])) {
            return 2;
        }
        if (!empty($extracted['wechats'])) {
            return 1;
        }
        if (!empty($extracted['qqs'])) {
            return 3;
        }
        return 0;
    }

    /**
     * @param array<string, mixed> $content
     */
    public static function isMissingDyUid(array $content): bool
    {
        return trim((string)($content['dy_uid'] ?? '')) === '';
    }

    /**
     * 只从简介/封面抽联系方式；抖音号按 add_rule / scan_dyid 兜底，不拼进抽取原文。
     *
     * @param array{contact_phone?:bool,contact_wechat?:bool,contact_qq?:bool} $contactOpts
     * @return array{phones: list<string>, wechats: list<string>, qqs: list<string>, raw: list<string>}
     */
    public static function buildExtracted(
        string $bio,
        string $dyId,
        string $ocrText,
        array $contactOpts = [],
        string $addRule = 'all',
        bool $scanDyid = true
    ): array {
        $text = trim($bio . "\n" . $ocrText);
        $extracted = DyContactExtractService::extract($text, $contactOpts);
        return self::mergeAcceptableDyId($extracted, $dyId, $contactOpts, $addRule, $scanDyid);
    }

    /**
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>,raw?:list<string>} $extracted
     * @param array{contact_phone?:bool,contact_wechat?:bool,contact_qq?:bool} $contactOpts
     * @return array{phones: list<string>, wechats: list<string>, qqs: list<string>, raw: list<string>}
     */
    public static function mergeAcceptableDyId(
        array $extracted,
        string $dyId,
        array $contactOpts = [],
        string $addRule = 'all',
        bool $scanDyid = true
    ): array {
        $extracted = self::uniqueExtracted($extracted);
        $dyId = trim($dyId);
        if ($dyId === '' || !DyDefaultIdFilter::isAcceptableDyId($dyId)) {
            return $extracted;
        }
        if (!self::shouldMergeDyId($extracted, $dyId, $contactOpts, $addRule, $scanDyid)) {
            return $extracted;
        }

        if (preg_match('/^1[3-9]\d{9}$/', $dyId) === 1) {
            $extracted['phones'][] = $dyId;
            $extracted['raw'][] = $dyId;
        } else {
            $extracted['wechats'][] = $dyId;
            $extracted['raw'][] = $dyId;
        }

        return self::uniqueExtracted($extracted);
    }

    /**
     * 入库展示用：按加微规则收口提取内容。校验列表不走这里。
     *
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>,raw?:list<string>} $extracted
     * @return array{phones: list<string>, wechats: list<string>, qqs: list<string>, raw: list<string>}
     */
    public static function applyAddRuleToExtracted(array $extracted, string $addRule): array
    {
        $extracted = self::uniqueExtracted($extracted);
        $addRule = self::normalizeAddRule($addRule);
        $phones = $extracted['phones'];
        $wechats = $extracted['wechats'];
        $qqs = $extracted['qqs'];

        if ($addRule === 'phone') {
            return self::uniqueExtracted([
                'phones' => $phones,
                'wechats' => [],
                'qqs' => [],
                'raw' => $phones,
            ]);
        }
        if ($addRule === 'wechat') {
            return self::uniqueExtracted([
                'phones' => [],
                'wechats' => $wechats,
                'qqs' => [],
                'raw' => $wechats,
            ]);
        }
        if ($addRule === 'phone_first') {
            if ($phones !== []) {
                return self::uniqueExtracted([
                    'phones' => $phones,
                    'wechats' => [],
                    'qqs' => [],
                    'raw' => $phones,
                ]);
            }
            return self::uniqueExtracted([
                'phones' => [],
                'wechats' => $wechats,
                'qqs' => [],
                'raw' => $wechats,
            ]);
        }

        return self::uniqueExtracted([
            'phones' => $phones,
            'wechats' => $wechats,
            'qqs' => $qqs,
            'raw' => array_merge($phones, $wechats, $qqs),
        ]);
    }

    /**
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>} $extracted
     * @return list<string>
     */
    public static function pickAddWechatContacts(array $extracted, string $addRule, bool $phoneAsWechat): array
    {
        $phones = array_values($extracted['phones'] ?? []);
        $wechats = array_values($extracted['wechats'] ?? []);
        $addRule = strtolower(trim($addRule));

        if ($addRule === 'phone') {
            return self::uniqueList($phones);
        }
        if ($addRule === 'wechat') {
            return $phoneAsWechat
                ? self::uniqueList(array_merge($wechats, $phones))
                : self::uniqueList($wechats);
        }
        if ($addRule === 'phone_first') {
            return $phones !== [] ? self::uniqueList($phones) : self::uniqueList($wechats);
        }

        return self::uniqueList(array_merge($phones, $wechats));
    }

    /**
     * @param list<string> $contacts
     * @return array{phones: list<string>, wechats: list<string>}
     */
    public static function splitAddWechatContacts(array $contacts): array
    {
        $phones = [];
        $wechats = [];
        foreach ($contacts as $contact) {
            $contact = trim((string)$contact);
            if ($contact === '') {
                continue;
            }
            if (WechatExistVerifyService::isWechatFormat($contact)) {
                $wechats[] = $contact;
            } else {
                $phones[] = $contact;
            }
        }
        return [
            'phones' => self::uniqueList($phones),
            'wechats' => self::uniqueList($wechats),
        ];
    }

    /**
     * 是否无需再校验：未入库/未开加微/按加微规则没有微信号形态。
     *
     * @param array<string, mixed> $scanConfig
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>} $extracted
     */
    public static function decideIsVerify(
        bool $autoAdd,
        array $scanConfig,
        array $extracted,
        int $result,
        string $addRule = 'all'
    ): int {
        if ($result !== self::RESULT_STORED || !$autoAdd) {
            return 1;
        }
        $contacts = self::pickAddWechatContacts(
            $extracted,
            $addRule,
            (bool)($scanConfig['phone_as_wechat'] ?? true)
        );
        $wechats = self::splitAddWechatContacts($contacts)['wechats'];
        return $wechats === [] ? 1 : 0;
    }

    /**
     * 待校验微信号：抽出的微信；phone_as_wechat 时手机也进校验列表。
     *
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>} $extracted
     * @return list<string>
     */
    public static function pickVerifyWechatContacts(array $extracted, bool $phoneAsWechat): array
    {
        $wechats = array_values($extracted['wechats'] ?? []);
        if ($phoneAsWechat) {
            $wechats = array_merge($wechats, array_values($extracted['phones'] ?? []));
        }
        return self::splitAddWechatContacts($wechats)['wechats'];
    }

    /**
     * @return array{ok: bool, discard: bool, code: int, msg: string, result: int, reg_content: string, clue_type: int, tokens: int}
     */
    private static function emptyAck(): array
    {
        return [
            'ok' => false,
            'discard' => false,
            'code' => 0,
            'msg' => '',
            'result' => 0,
            'reg_content' => '',
            'clue_type' => 0,
            'tokens' => 0,
        ];
    }

    /**
     * @param mixed $raw
     */
    private static function parseDeviceResult($raw): int
    {
        if (is_int($raw) || is_float($raw)) {
            return (int)$raw;
        }
        $s = trim((string)$raw);
        if ($s === '已入库') {
            return self::RESULT_STORED;
        }
        if ($s === '' || !is_numeric($s)) {
            return 0;
        }
        return (int)$s;
    }

    /**
     * @param mixed $scan
     * @return array<string, mixed>
     */
    private static function normalizeScanConfig($scan): array
    {
        if (is_string($scan) && $scan !== '') {
            $decoded = json_decode($scan, true);
            $scan = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($scan) || $scan === []) {
            return DySearchAccountNormalizer::defaultScanConfig();
        }
        return array_merge(DySearchAccountNormalizer::defaultScanConfig(), $scan);
    }

    /**
     * @param array{contact_phone?:bool,contact_wechat?:bool,contact_qq?:bool} $contactOpts
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>,raw?:list<string>} $extracted
     */
    public static function buildScanSource(
        string $bio,
        string $dyId,
        string $ocrText,
        array $contactOpts,
        array $extracted = []
    ): string {
        $parts = [];
        if ($bio !== '' && DyContactExtractService::extract($bio, $contactOpts)['raw'] !== []) {
            $parts[] = 'bio';
        }
        $dyId = trim($dyId);
        $usedDyId = $dyId !== '' && (
            in_array($dyId, $extracted['raw'] ?? [], true)
            || in_array($dyId, $extracted['wechats'] ?? [], true)
            || in_array($dyId, $extracted['phones'] ?? [], true)
        );
        if ($usedDyId) {
            $parts[] = 'dyid';
        }
        if ($ocrText !== '' && DyContactExtractService::extract($ocrText, $contactOpts)['raw'] !== []) {
            $parts[] = 'cover';
        }
        return implode(',', $parts);
    }

    /**
     * @param mixed $covers
     * @return list<string>
     */
    private static function normalizeCoverUrls($covers): array
    {
        if (is_string($covers) && $covers !== '') {
            $decoded = json_decode($covers, true);
            $covers = is_array($decoded) ? $decoded : [$covers];
        }
        if (!is_array($covers)) {
            return [];
        }
        $urls = [];
        foreach ($covers as $item) {
            if (is_array($item)) {
                $item = (string)($item['url'] ?? $item['image'] ?? '');
            }
            $item = trim((string)$item);
            if ($item !== '') {
                $urls[] = $item;
            }
        }
        return array_values($urls);
    }

    private static function isDuplicateKey(\Throwable $e): bool
    {
        $code = (int)$e->getCode();
        if (in_array($code, [1062, 23000], true)) {
            return true;
        }
        $msg = $e->getMessage();
        return str_contains($msg, '1062') || str_contains($msg, 'Duplicate') || str_contains($msg, 'uk_task_dy_uid');
    }

    private static function countTodayStored(int $userId, string $deviceCode): int
    {
        $start = strtotime(date('Y-m-d 00:00:00'));
        $end = $start + 86400;
        return (int)SvDySearchRecord::where('user_id', $userId)
            ->where('device_code', $deviceCode)
            ->where('result', self::RESULT_STORED)
            ->where('create_time', '>=', $start)
            ->where('create_time', '<', $end)
            ->count();
    }

    private static function countKeywordStored(int $taskId, string $execKeyword): int
    {
        if ($taskId < 1 || $execKeyword === '') {
            return 0;
        }
        return (int)SvDySearchRecord::where('task_id', $taskId)
            ->where('exec_keyword', $execKeyword)
            ->where('result', self::RESULT_STORED)
            ->count();
    }

    private static function resolveBind(int $taskId, string $deviceCode, int $bindId): ?SvDySearchTaskDevice
    {
        if ($bindId > 0) {
            $bind = SvDySearchTaskDevice::where('id', $bindId)->where('task_id', $taskId)->findOrEmpty();
            if (!$bind->isEmpty()) {
                if ((string)$bind->device_code === $deviceCode) {
                    return $bind;
                }
                self::logEvent('上报 bind_id 与设备不匹配，改按设备查找', [
                    'task_id' => $taskId,
                    'device' => $deviceCode,
                    'bind_id' => $bindId,
                    'bind_device' => (string)$bind->device_code,
                ]);
            }
        }
        $bind = SvDySearchTaskDevice::where('task_id', $taskId)
            ->where('device_code', $deviceCode)
            ->findOrEmpty();
        return $bind->isEmpty() ? null : $bind;
    }

    /**
     * @param array<string, scalar|null> $ctx
     */
    private static function logEvent(string $msg, array $ctx = []): void
    {
        $parts = [];
        foreach ($ctx as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = $key . '=' . (is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE));
        }
        Log::channel('device')->write('抖音获客 ' . $msg . ($parts === [] ? '' : ' ' . implode(' ', $parts)));
    }

    private static function resultReason(int $result, bool $needCharge, bool $keepStored): string
    {
        if ($keepStored) {
            return '重复上报更新原文不扣费';
        }
        return match ($result) {
            self::RESULT_STORED => $needCharge ? '已入库并扣费' : '已入库未扣费',
            self::RESULT_UNRECOGNIZED => '未识别跳过',
            self::RESULT_DM => '未识别已私信',
            self::RESULT_PREFILTER => '初筛跳过',
            self::RESULT_EXISTS => '已有跳过',
            self::RESULT_DEFAULT_ID => '默认号丢弃',
            self::RESULT_DAY_CAP => '日上限跳过',
            self::RESULT_KW_CAP => '词上限跳过',
            default => '未裁定',
        };
    }

    private static function bumpCounts(int $taskId, ?SvDySearchTaskDevice $bind, string $execKeyword, bool $clue, bool $exec = true): void
    {
        $taskQuery = SvDySearchTask::where('id', $taskId);
        if ($exec) {
            $taskQuery->inc('exec_count');
        }
        if ($clue) {
            $taskQuery->inc('clue_count');
        }
        $taskQuery->update(['update_time' => time()]);

        if ($bind === null) {
            return;
        }
        $bindQuery = SvDySearchTaskDevice::where('id', $bind->id);
        if ($exec) {
            $bindQuery->inc('exec_count');
        }
        if ($clue) {
            $bindQuery->inc('clue_count');
        }
        $bindUpdate = ['update_time' => time()];
        if ($execKeyword !== '') {
            $bindUpdate['exec_keyword'] = $execKeyword;
        }
        $bindQuery->update($bindUpdate);
    }

    private static function upsertSeen(int $userId, string $dyUid, string $dyId, int $action, int $taskId): void
    {
        $seen = SvDySearchSeen::where('user_id', $userId)->where('dy_uid', $dyUid)->findOrEmpty();
        if ($seen->isEmpty()) {
            SvDySearchSeen::create([
                'user_id' => $userId,
                'dy_uid' => $dyUid,
                'dy_id' => $dyId,
                'action' => $action,
                'last_task_id' => $taskId,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return;
        }
        $seen->dy_id = $dyId !== '' ? $dyId : (string)$seen->dy_id;
        $seen->action = max((int)$seen->action, $action);
        $seen->last_task_id = $taskId;
        $seen->update_time = time();
        $seen->save();
    }

    private static function seenAction(int $result): int
    {
        if ($result === self::RESULT_STORED) {
            return 3;
        }
        if ($result === self::RESULT_DM) {
            return 2;
        }
        return 1;
    }

    /**
     * 上报入队规划：手机号待加微；微信号 add_type=0 当场待加微，add_type=1 先待校验。
     *
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>} $extracted
     * @return list<array{contact: string, status: int, result: string}>
     */
    public static function planAddWechatWrites(
        array $extracted,
        string $addRule,
        bool $phoneAsWechat,
        int $addType
    ): array {
        $contacts = self::pickAddWechatContacts($extracted, $addRule, $phoneAsWechat);
        $split = self::splitAddWechatContacts($contacts);
        $items = [];
        foreach ($split['phones'] as $contact) {
            $items[] = [
                'contact' => $contact,
                'status' => 4,
                'result' => '',
            ];
        }
        foreach ($split['wechats'] as $contact) {
            if ($addType === 1) {
                $items[] = [
                    'contact' => $contact,
                    'status' => DySearchWechatVerifyService::ADD_STATUS_VERIFYING,
                    'result' => DySearchWechatVerifyService::VERIFYING_RESULT,
                ];
            } else {
                $items[] = [
                    'contact' => $contact,
                    'status' => 4,
                    'result' => '',
                ];
            }
        }
        return $items;
    }

    /**
     * @param array<string, mixed> $scanConfig
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>} $extracted
     */
    private static function enqueueAddWechat(
        SvDySearchTask $task,
        array $scanConfig,
        array $extracted,
        string $deviceCode,
        string $localAccount,
        string $userAccount,
        string $originalMessage,
        int $userId
    ): void {
        $items = self::planAddWechatWrites(
            $extracted,
            (string)($task->add_rule ?? 'all'),
            (bool)($scanConfig['phone_as_wechat'] ?? true),
            (int)$task->add_type
        );
        foreach ($items as $item) {
            self::writeAddWechatRecord(
                $task,
                $item['contact'],
                $deviceCode,
                $localAccount,
                $userAccount,
                $originalMessage,
                $userId,
                $item['status'],
                $item['result']
            );
        }
    }

    /**
     * 写入一条加微记录。status=4 待加微，6 校验中，0 校验无效。已存在则跳过。
     */
    public static function writeAddWechatRecord(
        SvDySearchTask $task,
        string $contact,
        string $deviceCode,
        string $localAccount,
        string $userAccount,
        string $originalMessage,
        int $userId,
        int $status,
        string $result = ''
    ): ?SvAddWechatRecord {
        $contact = trim($contact);
        if ($contact === '') {
            return null;
        }
        $existing = SvAddWechatRecord::where('user_id', $userId)
            ->where('reg_wechat', $contact)
            ->findOrEmpty();
        if (!$existing->isEmpty() && in_array((int)$existing->status, [1, 2, 4, 5, 6, SvAddWechatRecord::STATUS_RESULT_UNKNOWN], true)) {
            self::logEvent('加微号已存在，跳过该号仍保留客资', [
                'task_id' => (int)$task->id,
                'device' => $deviceCode,
                'contact' => $contact,
            ]);
            return null;
        }

        $lock = SvDySearchDedupService::claimAddWechatContact($userId, $contact);
        if ($lock === null && $existing->isEmpty()) {
            self::logEvent('加微号已存在，跳过该号仍保留客资', [
                'task_id' => (int)$task->id,
                'device' => $deviceCode,
                'contact' => $contact,
            ]);
            return null;
        }
        try {
            if (!$existing->isEmpty()) {
                $existing->device_code = $deviceCode;
                $existing->account = $localAccount;
                $existing->user_account = $userAccount;
                $existing->original_message = $originalMessage;
                $existing->status = $status;
                $existing->channel = DySearchIntentionMapper::ADD_CHANNEL;
                $existing->dy_search_task_id = (int)$task->id;
                $existing->update_time = time();
                if ($result !== '') {
                    $existing->result = $result;
                } else {
                    $existing->result = '';
                }
                $existing->save();
                return $existing;
            }
            if ($lock === null) {
                return null;
            }
            $data = [
                'user_id' => $userId,
                'device_code' => $deviceCode,
                'account' => $localAccount,
                'account_type' => DeviceEnum::ACCOUNT_TYPE_DY,
                'user_account' => $userAccount,
                'original_message' => $originalMessage,
                'reg_wechat' => $contact,
                'action' => 1,
                'status' => $status,
                'channel' => DySearchIntentionMapper::ADD_CHANNEL,
                'exec_type' => 2,
                'task_id' => (string)(time() . random_int(100, 999)),
                'crawling_task_id' => 0,
                'dy_search_task_id' => (int)$task->id,
                'create_time' => time(),
                'update_time' => time(),
            ];
            if ($result !== '') {
                $data['result'] = $result;
            }
            return SvAddWechatRecord::create($data);
        } catch (\Throwable $e) {
            self::logEvent('写入加微队列失败，客资已保留：' . $e->getMessage(), [
                'task_id' => (int)$task->id,
                'device' => $deviceCode,
                'contact' => $contact,
            ]);
            return null;
        } finally {
            SvDySearchDedupService::releaseAddWechatContact($userId, $contact, $lock);
        }
    }

    public static function localDyAccount(string $deviceCode, int $userId): string
    {
        $account = SvAccount::where('device_code', $deviceCode)
            ->where('type', DeviceEnum::ACCOUNT_TYPE_DY)
            ->where('user_id', $userId)
            ->findOrEmpty();
        return $account->isEmpty() ? '' : (string)$account->account;
    }

    /**
     * 已入库行被重复上报时，空值不得覆盖识别原文与扫描字段。
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $stored
     * @return array<string, mixed>
     */
    public static function preserveStoredScanFields(array $row, array $stored): array
    {
        foreach (['ocr_text', 'scan_source', 'nickname', 'avatar', 'enterprise_text', 'stitch_url', 'dy_id'] as $field) {
            $new = trim((string)($row[$field] ?? ''));
            $old = trim((string)($stored[$field] ?? ''));
            if ($new === '' && $old !== '') {
                $row[$field] = $old;
            }
        }
        $newCovers = $row['cover_urls'] ?? [];
        $oldCovers = $stored['cover_urls'] ?? [];
        if (is_string($oldCovers) && $oldCovers !== '') {
            $decoded = json_decode($oldCovers, true);
            $oldCovers = is_array($decoded) ? $decoded : [];
        }
        if ((!is_array($newCovers) || $newCovers === []) && is_array($oldCovers) && $oldCovers !== []) {
            $row['cover_urls'] = $oldCovers;
        }
        return $row;
    }

    /**
     * 简介/封面已有手机或微信时，抖音号只当账号标识，不并进联系方式。
     *
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>,raw?:list<string>} $extracted
     * @param array{contact_phone?:bool,contact_wechat?:bool,contact_qq?:bool} $contactOpts
     */
    public static function shouldMergeDyId(
        array $extracted,
        string $dyId,
        array $contactOpts = [],
        string $addRule = 'all',
        bool $scanDyid = true
    ): bool {
        $addRule = self::normalizeAddRule($addRule);
        $wantPhone = (bool)($contactOpts['contact_phone'] ?? true);
        $wantWechat = (bool)($contactOpts['contact_wechat'] ?? true);
        $hasPhones = ($extracted['phones'] ?? []) !== [];
        $hasWechats = ($extracted['wechats'] ?? []) !== [];

        if (preg_match('/^1[3-9]\d{9}$/', $dyId) === 1) {
            if (!$wantPhone || $hasPhones) {
                return false;
            }
            return in_array($addRule, ['all', 'phone', 'phone_first'], true);
        }

        if (!$scanDyid || !$wantWechat || $hasPhones || $addRule === 'phone') {
            return false;
        }
        if ($hasWechats) {
            return false;
        }
        return in_array($addRule, ['all', 'wechat', 'phone_first'], true);
    }

    private static function normalizeAddRule(string $addRule): string
    {
        $addRule = strtolower(trim($addRule));
        if (!in_array($addRule, ['all', 'phone', 'wechat', 'phone_first'], true)) {
            return 'all';
        }
        return $addRule;
    }

    /**
     * @param array{phones?:list<string>,wechats?:list<string>,qqs?:list<string>,raw?:list<string>} $extracted
     * @return array{phones: list<string>, wechats: list<string>, qqs: list<string>, raw: list<string>}
     */
    private static function uniqueExtracted(array $extracted): array
    {
        return [
            'phones' => self::uniqueList($extracted['phones'] ?? []),
            'wechats' => self::uniqueList($extracted['wechats'] ?? []),
            'qqs' => self::uniqueList($extracted['qqs'] ?? []),
            'raw' => self::uniqueList($extracted['raw'] ?? []),
        ];
    }

    /**
     * @param list<string> $items
     * @return list<string>
     */
    private static function uniqueList(array $items): array
    {
        $seen = [];
        $out = [];
        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item === '' || isset($seen[$item])) {
                continue;
            }
            $seen[$item] = true;
            $out[] = $item;
        }
        return $out;
    }

    /**
     * @param mixed $value
     */
    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return ((int)$value) === 1;
        }
        return in_array(strtolower(trim((string)$value)), ['1', 'true', 'yes', 'on'], true);
    }
}
