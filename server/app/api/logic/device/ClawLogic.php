<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\api\logic\TeamLogic;
use app\common\enum\DeviceEnum;
use app\common\model\oem\Oem;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceUsed;
use app\common\model\sv\SvSetting;
use app\common\model\team\Team;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\device\RpaDeviceDispatchService;
use app\common\service\FileService;
use think\facade\Db;
use think\facade\Log;

/**
 * 设备抓取任务逻辑
 * Class ClawLogic
 * @package app\api\logic\device
 */
class ClawLogic extends ApiLogic
{
    protected static array $appMaps = [
        DeviceEnum::APP_WECHAT => 1,
        DeviceEnum::APP_XHS => 3,
        DeviceEnum::APP_DY => 4,
        DeviceEnum::APP_KS => 5,
    ];

    public static function getInfo(array $params)
    {
        try {
            if (!isset($params['device_code']) || !$params['device_code']) {
                throw new \Exception('设备号不能为空');
            }
            $device = SvDevice::field('device_name,device_code,user_id')->where('device_code', $params['device_code'])->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }

            $user = User::field('id,real_name,nickname,avatar,mobile,tokens as points')->where('id', $device->user_id)->findOrEmpty();
            if ($user->isEmpty()) {
                throw new \Exception('用户不存在');
            }

            $userData = $user->toArray();
            $oem = self::resolveUserOem((int)$user->id);
            $userData['is_oem'] = $oem['is_oem'];
            $userData['oem_domain'] = $oem['oem_domain'];

            self::$returnData = [
                'is_used' => SvDeviceUsed::where('device_code', $params['device_code'])->where('user_id', $device->user_id)->value('is_used', 0),
                'device' => $device->toArray(),
                'user' => $userData,
                'demoSwitch' => intval(ConfigService::get('rpa', 'demo_switch', 0)),
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * @return array{is_oem:int,oem_domain:string}
     */
    private static function resolveUserOem(int $userId): array
    {
        $result = [
            'is_oem' => 0,
            'oem_domain' => '',
        ];
        if ($userId <= 0) {
            return $result;
        }

        $user = User::field('id,team_id,origin_team_id')->where('id', $userId)->findOrEmpty();
        $candidateIds = [];
        if (!$user->isEmpty()) {
            $teamId = (int)$user->team_id;
            $originTeamId = (int)$user->origin_team_id;
            if ($teamId > 0) {
                $candidateIds[] = $teamId;
            }
            if ($originTeamId > 0 && $originTeamId !== $teamId) {
                $candidateIds[] = $originTeamId;
            }
        }
        if ($candidateIds) {
            $teamMap = [];
            $teams = Team::whereIn('id', $candidateIds)
                ->where('status', 1)
                ->where('oem_status', 2)
                ->field('id,domain')
                ->select();
            foreach ($teams as $team) {
                $teamMap[(int)$team->id] = (string)($team->domain ?? '');
            }
            foreach ($candidateIds as $id) {
                if (isset($teamMap[$id])) {
                    $result['is_oem'] = 1;
                    $result['oem_domain'] = TeamLogic::normalizeDomain($teamMap[$id]);
                    break;
                }
            }
        }

        $oem = Oem::where('user_id', $userId)->where('status', 1)->field('id,domain')->findOrEmpty();
        if (!$oem->isEmpty()) {
            $result['is_oem'] = 1;
            if ($result['oem_domain'] === '') {
                $result['oem_domain'] = TeamLogic::normalizeDomain((string)($oem->domain ?? ''));
            }
        }

        return $result;
    }

    public static function getTask(array $params)
    {
        try {
            if (!isset($params['device_code']) || !$params['device_code']) {
                throw new \Exception('设备号不能为空');
            }
            $task = SvDeviceTask::field('id,device_code,task_type,account,account_type,task_name,status,remark,source,start_time,end_time')
                ->where('device_code', $params['device_code'])
                ->where('auto_type', '=', function ($query) use ($params) {
                    $query->name('sv_device')->where('device_code', $params['device_code'])->field('auto_type');
                })
                ->where('day', date('Y-m-d', time()))
                ->order('start_time', 'asc')
                ->select()
                ->each(function ($item) {
                    $item['start_time'] = date('H:i', $item['start_time']);
                    $item['end_time'] = date('H:i', $item['end_time']);
                    $item['task_category'] = !in_array($item['source'], [7, 8]) ? DeviceEnum::getAccountTypeDesc($item['account_type']) . DeviceEnum::getTaskTypeDesc($item['task_type']) : DeviceEnum::getTaskSceneDesc($item['task_type']);
                    return $item;
                });

            self::$returnData = $task->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function setAccount(array $params)
    {
        $startedAt = microtime(true);
        $traceId = str_replace('.', '', uniqid('sa_', true));
        $deviceCode = '';
        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'deleted' => 0,
            'setting_created' => 0,
            'setting_reused' => 0,
        ];

        Db::startTrans();
        try {
             self::logSetAccount('set_account_request', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'params' => $params,
            ]);
            if (!isset($params['device_code']) || empty($params['device_code'])) {
                throw new \Exception('设备号不能为空');
            }
            if (!isset($params['accounts']) || empty($params['accounts'])) {
                throw new \Exception('账号不能为空');
            }

            $deviceCode = (string)$params['device_code'];
            $params['accounts'] = self::normalizeAccounts($params['accounts']);
            if (empty($params['accounts'])) {
                throw new \Exception('账号不能为空');
            }

            $accountKeys = [];
            foreach ($params['accounts'] as $account) {
                $accountKeys[] = self::accountKey((int)$account['account_type'], (string)$account['userId']);
            }
            self::logSetAccount('set_account_start', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'account_count' => count($params['accounts']),
                'account_keys' => $accountKeys,
                'msg' => '开始同步设备账号',
            ]);

            $lockStartedAt = microtime(true);
            $device = SvDevice::where('device_code', $deviceCode)->lock(true)->findOrEmpty();
            $lockWaitMs = (int)round((microtime(true) - $lockStartedAt) * 1000);
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }

            self::logSetAccount('set_account_lock_acquired', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'user_id' => (int)$device->user_id,
                'lock_wait_ms' => $lockWaitMs,
                'msg' => '已获取设备行锁',
            ]);

            try {
                self::checkAccountBindConflict($params['accounts'], $deviceCode);
            } catch (\Throwable $conflict) {
                self::logSetAccount('set_account_conflict', [
                    'trace_id' => $traceId,
                    'device_code' => $deviceCode,
                    'error' => $conflict->getMessage(),
                    'result' => 'fail',
                    'msg' => '账号绑定冲突',
                ]);
                throw $conflict;
            }

            $existingRows = SvAccount::where('device_code', $deviceCode)->select();
            $existingMap = [];
            foreach ($existingRows as $row) {
                $existingMap[self::accountKey((int)$row->type, (string)$row->account)] = $row;
            }

            $keepKeys = [];
            foreach ($params['accounts'] as $account) {
                $payload = self::buildAccountRow($device, $account);
                $key = self::accountKey((int)$payload['type'], (string)$payload['account']);
                $keepKeys[$key] = true;

                if (!isset($existingMap[$key])) {
                    $created = SvAccount::create($payload);
                    $stats['inserted']++;
                    self::logSetAccount('set_account_action', [
                        'trace_id' => $traceId,
                        'device_code' => $deviceCode,
                        'action' => 'insert',
                        'type' => (int)$payload['type'],
                        'account' => (string)$payload['account'],
                        'account_id' => (int)($created->id ?? 0),
                        'msg' => '新增账号',
                    ]);
                    $settingAction = self::ensureSvSetting(
                        (string)$payload['account'],
                        (int)$device->user_id,
                        (int)$payload['type'],
                        $traceId
                    );
                    if ($settingAction === 'create') {
                        $stats['setting_created']++;
                    } else {
                        $stats['setting_reused']++;
                    }
                    continue;
                }

                $existing = $existingMap[$key];
                if (self::accountPayloadEquals($existing->toArray(), $payload)) {
                    $stats['skipped']++;
                    continue;
                }

                $changedFields = self::diffAccountFields($existing->toArray(), $payload);
                SvAccount::where('id', (int)$existing->id)->update([
                    'user_id' => $payload['user_id'],
                    'device_code' => $payload['device_code'],
                    'account' => $payload['account'],
                    'account_no' => $payload['account_no'],
                    'nickname' => $payload['nickname'],
                    'avatar' => $payload['avatar'],
                    'type' => $payload['type'],
                    'is_verified' => $payload['is_verified'],
                    'extra' => $payload['extra'],
                ]);
                $stats['updated']++;
                self::logSetAccount('set_account_action', [
                    'trace_id' => $traceId,
                    'device_code' => $deviceCode,
                    'action' => 'update',
                    'type' => (int)$payload['type'],
                    'account' => (string)$payload['account'],
                    'account_id' => (int)$existing->id,
                    'changed_fields' => $changedFields,
                    'msg' => '更新账号资料',
                ]);
            }

            $deleted = self::removeOrphanAccounts($deviceCode, $keepKeys, $traceId);
            $stats['deleted'] += $deleted;

            self::logSetAccount('set_account_diff_summary', array_merge($stats, [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'msg' => '差量同步统计',
            ]));

            self::$returnData = [
                'device_code' => $deviceCode,
                'msg' => '保存成功',
            ];
            Db::commit();

            self::logSetAccount('set_account_success', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'elapsed_ms' => (int)round((microtime(true) - $startedAt) * 1000),
                'diff' => $stats,
                'result' => 'ok',
                'msg' => '设备账号同步成功',
            ]);
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            $errorMsg = self::formatSetAccountError($th);
            if (self::isDuplicateEntry($th)) {
                self::logSetAccount('set_account_conflict', [
                    'trace_id' => $traceId,
                    'device_code' => $deviceCode,
                    'error' => $th->getMessage(),
                    'result' => 'fail',
                    'msg' => '唯一键冲突，账号可能已被其他设备绑定',
                ]);
            }
            self::setError($errorMsg);
            self::logSetAccount('set_account_fail', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'error' => $errorMsg,
                'elapsed_ms' => (int)round((microtime(true) - $startedAt) * 1000),
                'result' => 'fail',
                'msg' => '设备账号同步失败',
            ]);
            return false;
        }
    }

    private static function normalizeAccounts(mixed $accounts): array
    {
        if (!is_array($accounts)) {
            $accounts = json_decode((string)$accounts, true);
        }
        if (!is_array($accounts)) {
            throw new \Exception('账号格式错误');
        }

        $normalized = [];
        $seen = [];
        foreach ($accounts as $account) {
            if (!is_array($account)) {
                continue;
            }

            $userId = trim(str_replace(['小红书号：', '抖音号：', '快手号：'], '', (string)($account['userId'] ?? '')));
            if ($userId === '') {
                continue;
            }

            $accountType = self::$appMaps[$account['appName'] ?? ''] ?? 0;
            if (!$accountType) {
                throw new \Exception('账号类型不能为空');
            }

            $dedupeKey = $accountType . ':' . $userId;
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $account['userId'] = $userId;
            if (!isset($account['nickname']) || $account['nickname'] === '') {
                $account['nickname'] = $userId;
            }
            $account['account_type'] = $accountType;
            $normalized[] = $account;
        }

        return $normalized;
    }

    private static function checkAccountBindConflict(array $accounts, string $deviceCode): void
    {
        foreach ($accounts as $account) {
            $boundAccount = SvAccount::where('account', $account['userId'])
                ->where('type', $account['account_type'])
                ->where('device_code', '<>', $deviceCode)
                ->findOrEmpty();

            if (!$boundAccount->isEmpty()) {
                throw new \Exception('账号' . $account['userId'] . '已绑定在设备：' . $boundAccount->device_code . '，请先解绑后重试');
            }
        }
    }

    private static function accountKey(int $type, string $account): string
    {
        return $type . ':' . $account;
    }

    private static function buildAccountRow(object $device, array $account): array
    {
        $accountType = (int)$account['account_type'];
        $userId = (string)$account['userId'];
        $extra = [
            'gender' => $account['gender'] ?? '',
            'introduction' => $account['introduction'] ?? '',
            'constellation' => $account['constellation'] ?? '',
            'area' => $account['area'] ?? '',
            'followers' => $account['numberFollowers'] ?? '',
            'fans' => $account['numberFans'] ?? '',
            'thumbup_collect' => $account['thumbsUpAndCollect'] ?? '',
            'business_card' => $account['business_card'] ?? 0,
            'account_type' => $accountType,
        ];

        return [
            'user_id' => (int)$device->user_id,
            'device_code' => (string)$device->device_code,
            'account' => $userId,
            'account_no' => $userId,
            'nickname' => (string)($account['nickname'] ?? $userId),
            'avatar' => FileService::setFileUrl((string)($account['serverAvatarUrl'] ?? '')),
            'type' => $accountType,
            'is_verified' => self::normalizeVerified($account['isVerified'] ?? 0),
            'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE),
        ];
    }

    private static function accountPayloadEquals(array $existing, array $payload): bool
    {
        return self::diffAccountFields($existing, $payload) === [];
    }

    private static function diffAccountFields(array $existing, array $payload): array
    {
        $changed = [];
        if ((string)($existing['account'] ?? '') !== (string)$payload['account']) {
            $changed[] = 'account';
        }
        if ((string)($existing['account_no'] ?? '') !== (string)$payload['account_no']) {
            $changed[] = 'account_no';
        }
        if ((int)($existing['type'] ?? 0) !== (int)$payload['type']) {
            $changed[] = 'type';
        }
        if ((string)($existing['nickname'] ?? '') !== (string)$payload['nickname']) {
            $changed[] = 'nickname';
        }
        if (FileService::setFileUrl((string)($existing['avatar'] ?? '')) !== (string)$payload['avatar']) {
            $changed[] = 'avatar';
        }
        if (self::normalizeVerified($existing['is_verified'] ?? 0) !== (int)$payload['is_verified']) {
            $changed[] = 'is_verified';
        }

        $existingExtra = self::normalizeExtraForCompare(self::decodeExtra($existing['extra'] ?? ''));
        $payloadExtra = self::normalizeExtraForCompare(self::decodeExtra($payload['extra'] ?? ''));
        if ($existingExtra !== $payloadExtra) {
            $changed[] = 'extra';
        }

        return $changed;
    }

    private static function decodeExtra(mixed $extra): array
    {
        if (is_array($extra)) {
            return $extra;
        }
        if (!is_string($extra) || $extra === '') {
            return [];
        }
        $decoded = json_decode($extra, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function normalizeExtraForCompare(array $extra): array
    {
        ksort($extra);
        return $extra;
    }

    private static function normalizeVerified(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        return (int)$value ? 1 : 0;
    }

    /**
     * @return string create|reuse|duplicate_ignore
     */
    private static function ensureSvSetting(string $account, int $userId, int $accountType, string $traceId = ''): string
    {
        $setting = SvSetting::where('account', $account)->findOrEmpty();
        if (!$setting->isEmpty()) {
            self::logSetAccount('set_account_setting', [
                'trace_id' => $traceId,
                'account' => $account,
                'action' => 'reuse',
                'msg' => '复用已有账号设置',
            ]);
            return 'reuse';
        }

        $settingData = [
            'takeover_type' => 1,
            'account' => $account,
            'user_id' => $userId,
            'open_ai' => 1,
            'robot_id' => 0,
            'takeover_mode' => 1,
        ];
        if (self::svSettingHasField('account_type')) {
            $settingData['account_type'] = $accountType;
        }

        try {
            SvSetting::create($settingData);
            self::logSetAccount('set_account_setting', [
                'trace_id' => $traceId,
                'account' => $account,
                'action' => 'create',
                'msg' => '创建账号设置',
            ]);
            return 'create';
        } catch (\Throwable $th) {
            if (!self::isDuplicateEntry($th)) {
                throw $th;
            }
            self::logSetAccount('set_account_setting', [
                'trace_id' => $traceId,
                'account' => $account,
                'action' => 'duplicate_ignore',
                'msg' => '设置并发创建冲突，已忽略',
            ]);
            return 'duplicate_ignore';
        }
    }

    private static function removeOrphanAccounts(string $deviceCode, array $keepKeys, string $traceId = ''): int
    {
        $deleted = 0;
        $existingRows = SvAccount::where('device_code', $deviceCode)->select();
        foreach ($existingRows as $row) {
            $key = self::accountKey((int)$row->type, (string)$row->account);
            if (isset($keepKeys[$key])) {
                continue;
            }

            $account = (string)$row->account;
            $type = (int)$row->type;
            $accountId = (int)$row->id;
            SvAccount::where('id', $accountId)->delete();
            $deleted++;
            self::logSetAccount('set_account_action', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'action' => 'delete',
                'type' => $type,
                'account' => $account,
                'account_id' => $accountId,
                'msg' => '删除设备孤儿账号',
            ]);

            $stillUsed = SvAccount::where('account', $account)->findOrEmpty();
            if ($stillUsed->isEmpty()) {
                SvSetting::where('account', $account)->select()->delete();
            }
        }
        return $deleted;
    }

    private static function logSetAccount(string $event, array $context = []): void
    {
        $payload = array_merge([
            'event' => $event,
            'msg' => (string)($context['msg'] ?? ''),
            'result' => (string)($context['result'] ?? ''),
        ], $context);

        try {
            Log::channel('auto')->write(
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'claw'
            );
        } catch (\Throwable) {
            // 日志失败不影响业务
        }
    }

    /**
     * 手机清本地码后的解绑上报（替代原 WS 1213）
     * 仅清 pending，不取消延迟踢线；设备已删库也不失败（幂等）
     */
    public static function unbindReport(array $params): bool
    {
        try {
            $deviceCode = trim((string)($params['device_code'] ?? ''));
            if ($deviceCode === '') {
                throw new \Exception('设备号不能为空');
            }

            if (!RpaDeviceDispatchService::ENABLE_RPA_DEVICE_UNBIND_NOTIFY) {
                RpaDeviceDispatchService::logUnbind('unbind_disabled', [
                    'device_code' => $deviceCode,
                    'params' => $params,
                    'msg' => 'RPA 解绑通知已暂时关闭，解绑上报已忽略',
                    'result' => 'skip',
                ]);
                self::$returnData = [
                    'device_code' => $deviceCode,
                    'msg' => '解绑通知已暂时关闭',
                ];
                return true;
            }

            if (!RpaDeviceDispatchService::isInUnbindFlow($deviceCode)) {
                RpaDeviceDispatchService::logUnbind('unbind_ack_api', [
                    'device_code' => $deviceCode,
                    'params' => $params,
                    'msg' => '非解绑流程上报，已忽略（幂等）',
                    'result' => 'skip',
                ]);
                self::$returnData = [
                    'device_code' => $deviceCode,
                    'msg' => '无需上报',
                ];
                return true;
            }

            $traceId = RpaDeviceDispatchService::getTraceId($deviceCode);
            RpaDeviceDispatchService::clearPendingUnbind($deviceCode);
            RpaDeviceDispatchService::logUnbind('unbind_ack_api', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'reason' => (string)($params['reason'] ?? ''),
                'ts' => (int)($params['ts'] ?? time()),
                'params' => $params,
                'force_unbind' => RpaDeviceDispatchService::hasForceUnbind($deviceCode),
                'msg' => '收到解绑 API 上报，已清除 pending（保留 force，不取消延迟踢线）',
                'result' => 'ok',
            ]);

            self::$returnData = [
                'device_code' => $deviceCode,
                'msg' => '解绑上报成功',
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            RpaDeviceDispatchService::logUnbind('unbind_error', [
                'device_code' => (string)($params['device_code'] ?? ''),
                'params' => $params,
                'msg' => '解绑 API 上报处理失败',
                'result' => 'fail',
                'error' => $th->getMessage(),
            ]);
            return false;
        }
    }

    private static function svSettingHasField(string $field): bool
    {
        static $fields = null;
        if ($fields === null) {
            try {
                $fields = Db::connect()->getTableFields((new SvSetting())->getTable());
            } catch (\Throwable) {
                $fields = [];
            }
        }
        return in_array($field, $fields, true);
    }

    private static function isDuplicateEntry(\Throwable $throwable): bool
    {
        $message = $throwable->getMessage();
        return str_contains($message, 'SQLSTATE[23000]') || str_contains($message, 'Duplicate entry');
    }

    private static function formatSetAccountError(\Throwable $throwable): string
    {
        if (self::isDuplicateEntry($throwable)) {
            return '账号已存在，请先解绑后重试';
        }
        return $throwable->getMessage();
    }
}
