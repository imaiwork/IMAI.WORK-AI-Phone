<?php

namespace app\api\logic\sv;

use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\sv\SvDeviceViralManualImport;
use app\common\model\sv\SvDeviceViralRecord;
use app\common\service\aiPersona\ViralManualImportService;
use think\facade\Db;

/**
 * 爆款库记录逻辑
 */
class DeviceViralRecordLogic extends SvBaseLogic
{
    /**
     * 手动导入分享链接（仅排队，0-3点解析）
     */
    public static function manualImport(array $params): bool
    {
        try {
            $data = ViralManualImportService::enqueue(
                (int)self::$uid,
                (int)$params['persona_id'],
                (string)($params['share_content'] ?? '')
            );
            self::$returnData = $data;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function interest(array $params): bool
    {
        try {
            $ids = self::parseIds($params['ids'] ?? []);
            if (empty($ids)) {
                self::setError('请选择记录');
                return false;
            }

            $source = (string)($params['source'] ?? '');
            $isInterested = (int)$params['is_interested'];

            if ($source === 'manual') {
                $count = SvDeviceViralManualImport::where('user_id', self::$uid)
                    ->whereIn('id', $ids)
                    ->count();
                if ((int)$count === 0) {
                    self::setError('记录不存在');
                    return false;
                }
                SvDeviceViralManualImport::where('user_id', self::$uid)
                    ->whereIn('id', $ids)
                    ->update([
                        'is_interested' => $isInterested,
                        'update_time' => time(),
                    ]);
                self::$returnData = ['count' => $count, 'source' => 'manual'];
                return true;
            }

            $count = SvDeviceViralRecord::where('user_id', self::$uid)
                ->whereIn('id', $ids)
                ->count();
            if ((int)$count === 0) {
                self::setError('记录不存在');
                return false;
            }

            SvDeviceViralRecord::where('user_id', self::$uid)
                ->whereIn('id', $ids)
                ->update([
                    'is_interested' => $isInterested,
                    'update_time' => time(),
                ]);

            self::$returnData = ['count' => $count, 'source' => 'auto'];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function clearUninterested(array $params): bool
    {
        try {
            $query = SvDeviceViralRecord::alias('ps')
                ->join('sv_device_viral_account va', 'va.id = ps.viral_account_id', 'left')
                ->where('ps.user_id', self::$uid)
                ->where('ps.status', 4)
                ->where('ps.is_interested', 0);

            if (!empty($params['day'])) {
                $query->where('ps.day', '=', $params['day']);
            }

            if (!empty($params['account_type'])) {
                $query->where('ps.publish_platform', '=', (int)$params['account_type']);
            }

            $ids = $query->column('ps.id');

            $manualQuery = SvDeviceViralManualImport::where('user_id', self::$uid)
                ->where('is_interested', 0);
            if (!empty($params['day'])) {
                $manualQuery->where('scheduled_day', '=', $params['day']);
            }
            if (!empty($params['account_type'])) {
                $manualQuery->where('publish_platform', '=', (int)$params['account_type']);
            }
            if (!empty($params['persona_id'])) {
                $manualQuery->where('persona_id', '=', (int)$params['persona_id']);
            }
            $manualIds = $manualQuery->column('id');

            $viralCount = 0;
            $manualCount = 0;

            Db::transaction(function () use ($ids, $manualIds, &$viralCount, &$manualCount) {
                if (!empty($ids)) {
                    AiPersonaSynthesisCopywriting::where('user_id', self::$uid)
                        ->whereIn('sv_device_viral_record_id', $ids)
                        ->select()
                        ->delete();

                    SvDeviceViralRecord::where('user_id', self::$uid)
                        ->whereIn('id', $ids)
                        ->delete();
                    $viralCount = count($ids);
                }

                if (!empty($manualIds)) {
                    SvDeviceViralManualImport::where('user_id', self::$uid)
                        ->whereIn('id', $manualIds)
                        ->update([
                            'is_interested' => 1,
                            'update_time' => time(),
                        ]);
                    $manualCount = count($manualIds);
                }
            });

            self::$returnData = [
                'count' => $viralCount + $manualCount,
                'viral_count' => $viralCount,
                'manual_count' => $manualCount,
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function saveCopywriting(array $params): bool
    {
        try {
            $record = SvDeviceViralRecord::where('id', (int)$params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($record->isEmpty()) {
                self::setError('记录不存在');
                return false;
            }

            $copywriting = self::normalizeCopywriting($record->copywriting);
            if (isset($params['title'])) {
                $copywriting['title'] = $params['title'];
            } elseif (!isset($copywriting['title'])) {
                $copywriting['title'] = '';
            }
            $copywriting['rewritten_text'] = $params['rewritten_text'];

            $record->copywriting = $copywriting;
            $record->update_time = time();
            $record->save();

            AiPersonaSynthesisCopywriting::where('user_id', self::$uid)
                ->where('sv_device_viral_record_id', (int)$record->id)
                ->update([
                    'copywriting' => json_encode($copywriting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'update_time' => time(),
                ]);

            self::$returnData = [
                'id' => (int)$record->id,
                'copywriting' => $copywriting,
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function parseIds($ids): array
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        if (!is_array($ids)) {
            return [];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn($id) => $id > 0);
        return array_values(array_unique($ids));
    }

    private static function normalizeCopywriting($copywriting): array
    {
        while (is_string($copywriting) && $copywriting !== '') {
            $decoded = json_decode($copywriting, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['rewritten_text' => $copywriting];
            }
            $copywriting = $decoded;
        }

        return is_array($copywriting) ? $copywriting : [];
    }
}
