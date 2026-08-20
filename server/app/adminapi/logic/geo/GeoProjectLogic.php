<?php

namespace app\adminapi\logic\geo;

use app\common\logic\BaseLogic;
use app\common\model\geo\GeoContent;
use app\common\model\geo\GeoKeyword;
use app\common\model\geo\GeoMonitor;
use app\common\model\geo\GeoProject;
use app\common\model\geo\GeoPublish;
use app\common\model\geo\GeoSiteTask;
use app\common\model\geo\GeoTask;
use app\common\model\geo\GeoTopic;
use app\common\service\geo\GeoPublishService;
use think\facade\Db;

/**
 * GEO 项目管理(后台全局视角)。
 * 删除不复用 api 侧 GeoLogic::projectDelete:那边按归属用户 assertOwn 校验,
 * 后台跨租户操作,但清理链必须与其保持一致(退费→停站点任务→终止批次→软删)。
 */
class GeoProjectLogic extends BaseLogic
{
    public static function detail(array $params): bool
    {
        try {
            $p = GeoProject::findOrEmpty((int)$params['id']);
            if ($p->isEmpty()) {
                throw new \Exception('项目不存在');
            }
            $data = $p->toArray();
            $pid = (int)$p->id;
            $data['stat'] = [
                'topic_count' => GeoTopic::where('project_id', $pid)->count(),
                'question_count' => GeoKeyword::where('project_id', $pid)->count(),
                'content_count' => GeoContent::where('project_id', $pid)->count(),
                'monitor_count' => GeoMonitor::where('project_id', $pid)->count(),
                'publish_count' => GeoPublish::where('project_id', $pid)->count(),
                'running_batch' => GeoTask::where('project_id', $pid)
                    ->where('task_type', 'monitor_batch')->where('status', 'running')->count(),
            ];
            self::$returnData = $data;
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function setAutoMonitor(array $params): bool
    {
        try {
            $p = GeoProject::findOrEmpty((int)$params['id']);
            if ($p->isEmpty()) {
                throw new \Exception('项目不存在');
            }
            $p->save(['auto_monitor' => (int)$params['auto_monitor'] ? 1 : 0, 'update_time' => time()]);
            self::$returnData = ['id' => (int)$p->id, 'auto_monitor' => (int)$p->auto_monitor];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete(array $params): bool
    {
        try {
            $p = GeoProject::findOrEmpty((int)$params['id']);
            if ($p->isEmpty()) {
                throw new \Exception('项目不存在');
            }
            Db::transaction(function () use ($p) {
                // 未发布投递先退费再删:项目软删后用户端退费通道会被堵死
                $rows = GeoPublish::where('project_id', $p->id)
                    ->where('status', '<>', 'published')->where('cost', '>', 0)
                    ->lock(true)->select();
                foreach ($rows as $r) {
                    GeoPublishService::refundIfUnpublished($r);
                    $r->delete();
                }
                // 停官网定时发布,避免项目删了仍继续发文
                GeoSiteTask::where('project_id', (int)$p->id)->update(['status' => 0, 'update_time' => time()]);
                // 终止在跑的诊断批次,释放每分钟的调度预算
                GeoTask::where('project_id', (int)$p->id)
                    ->where('task_type', 'monitor_batch')->where('status', 'running')
                    ->update(['status' => 'failed', 'update_time' => time()]);
                $p->delete();
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
