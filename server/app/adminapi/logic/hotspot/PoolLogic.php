<?php

namespace app\adminapi\logic\hotspot;

use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\hotspot\HotspotPool;
use app\common\service\hotspot\HotspotLog;
use app\common\service\hotspot\HotspotUpstreamException;
use app\common\service\hotspot\PoolService;

class PoolLogic extends BaseLogic
{
    public static function lists(array $params): array|false
    {
        $personaId = (int)($params['persona_id'] ?? 0);
        HotspotLog::write(sprintf('后台热点库列表请求：人设id=%d', $personaId));
        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            HotspotLog::write(sprintf('后台热点库列表失败：人设不存在 人设id=%d', $personaId));
            self::setError('人设不存在');
            return false;
        }
        try {
            $userId = (int)$persona->user_id;
            $result = PoolService::lists($userId, $personaId);
            HotspotLog::write(sprintf(
                '后台热点库列表成功：人设id=%d 用户=%d 待使用=%d 已使用=%d',
                $personaId,
                $userId,
                (int)($result['pending_count'] ?? 0),
                (int)($result['used_count'] ?? 0)
            ));
            return $result;
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('后台热点库列表失败：' . $e->getMessage());
            self::setError($e->getMessage());
            return false;
        } catch (\Throwable $e) {
            HotspotLog::exception('后台热点库列表异常', $e);
            self::setError('服务异常，请稍后再试');
            return false;
        }
    }

    public static function delete(array $params): bool
    {
        $id = (int)($params['id'] ?? 0);
        $personaId = (int)($params['persona_id'] ?? 0);
        HotspotLog::write(sprintf('后台热点库移出请求：人设id=%d 编号=%d', $personaId, $id));
        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            HotspotLog::write(sprintf('后台热点库移出失败：人设不存在 人设id=%d', $personaId));
            self::setError('人设不存在');
            return false;
        }
        $userId = (int)$persona->user_id;
        $row = HotspotPool::where('id', $id)
            ->where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->findOrEmpty();
        if ($row->isEmpty()) {
            HotspotLog::write(sprintf(
                '后台热点库移出失败：待用记录不存在 人设id=%d 编号=%d',
                $personaId,
                $id
            ));
            self::setError('待用记录不存在');
            return false;
        }
        try {
            PoolService::delete($userId, $id);
            HotspotLog::write(sprintf(
                '后台热点库移出成功：人设id=%d 用户=%d 编号=%d 话题=%s',
                $personaId,
                $userId,
                $id,
                (string)$row->topic
            ));
            return true;
        } catch (HotspotUpstreamException $e) {
            HotspotLog::write('后台热点库移出失败：' . $e->getMessage());
            self::setError($e->getMessage());
            return false;
        } catch (\Throwable $e) {
            HotspotLog::exception('后台热点库移出异常', $e);
            self::setError('服务异常，请稍后再试');
            return false;
        }
    }
}
