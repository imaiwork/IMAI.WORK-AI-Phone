<?php

namespace app\api\logic\workData;

use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\service\workData\PersonaBindService;
use app\common\service\workData\WorkDataChargeService;
use app\common\service\workData\WorkDataException;
use app\common\service\workData\WorkDataFetchService;
use app\common\service\workData\WorkDataLog;
use app\common\service\workData\WorkDataSupport;

class WorkDataLogic extends BaseLogic
{
    public static function syncSettings(array $params, int $userId): array|false
    {
        try {
            WorkDataChargeService::requireUser($userId);
            $persona = AiPersona::where('id', (int)$params['persona_id'])
                ->where('user_id', $userId)->whereNull('delete_time')->find();
            if (!$persona) {
                throw new WorkDataException('人设不存在');
            }
            $enabled = (int)$params['auto_sync_enabled'];
            $persona->save(['work_data_auto_sync' => $enabled, 'update_time' => time()]);
            return ['persona_id' => (int)$persona->id, 'auto_sync_enabled' => $enabled === 1];
        } catch (\Throwable $e) {
            WorkDataLog::exception('作品数据同步设置保存失败', $e);
            self::setError($e instanceof WorkDataException ? $e->getMessage() : '同步设置保存失败，请重试');
            return false;
        }
    }

    /**
     * @return array{list: list<array<string, mixed>>, cost: array<string, mixed>}|false
     */
    public static function personas(int $userId): array|false
    {
        try {
            WorkDataChargeService::requireUser($userId);
            $list = PersonaBindService::listPersonas($userId);
            WorkDataLog::write(sprintf('作品数据人设列表：用户=%d 条数=%d', $userId, count($list)));
            // 带上取数单价，前端刷新前要告诉用户这一次要花多少算力
            return [
                'list' => $list,
                'cost' => WorkDataChargeService::costItem(),
            ];
        } catch (WorkDataException $e) {
            self::setError($e->getMessage());
            return false;
        } catch (\Throwable $e) {
            WorkDataLog::exception('作品数据人设列表异常', $e);
            self::setError('人设列表获取失败');
            return false;
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|false
     */
    public static function analytics(array $params, int $userId): array|false
    {
        $personaId = (int)($params['persona_id'] ?? 0);
        $platform = (string)($params['platform'] ?? '');
        $refresh = WorkDataSupport::normalizeRefresh($params['refresh'] ?? false);
        $account = trim((string)($params['account'] ?? ''));
        $account = $account === '' ? null : $account;
        try {
            WorkDataChargeService::requireUser($userId);
            $payload = WorkDataFetchService::fetch($userId, $personaId, $platform, $refresh, $account);
            return $payload;
        } catch (WorkDataException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '还没绑定')) {
                return [
                    'bound' => false,
                    'platform' => $platform,
                    'msg' => $msg,
                ];
            }
            WorkDataLog::write('作品数据业务失败：' . $msg);
            self::setError($msg);
            return false;
        } catch (\Throwable $e) {
            WorkDataLog::exception('作品数据取数异常', $e);
            self::setError(WorkDataException::translateUpstream($e->getMessage()));
            return false;
        }
    }
}
