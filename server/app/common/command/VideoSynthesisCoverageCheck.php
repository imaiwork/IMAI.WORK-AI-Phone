<?php


namespace app\common\command;

use app\common\model\sv\SvDevice;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * VideoSynthesisCoverageCheck
 * @desc 自动合成视频任务覆盖率核对
 *       每日 8:30（凌晨合成窗口结束后、重置窗口 9:00 开始前）统计应生成设备数与已完成数，
 *       覆盖率低于阈值时写告警日志，用于暴露"设备当天未生成视频"的无声失败
 *       注：未完成设备中可能包含发布时段为空而被跳过的设备，属于需要站长处理的配置问题
 * @author dagouzi
 */
class VideoSynthesisCoverageCheck extends Command
{
    /**
     * 覆盖率告警阈值（已完成/应生成 低于该值时按 error 级别记录）
     */
    const ALARM_RATE = 0.9;

    /**
     * 告警日志中列出的未完成设备号数量上限
     */
    const LIST_LIMIT = 50;

    protected function configure()
    {
        $this->setName('video_synthesis_coverage')
            ->setDescription('自动合成视频任务覆盖率核对');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            print_r("\n 视频合成覆盖率核对开始...\n");
            $this->checkSocial();
            $this->checkWechat();
            $this->checkDualWriteConsistency();
            return true;
        } catch (\Exception $e) {
            Log::channel('ipVideoSynthesis')->error('视频合成覆盖率核对失败：' . $e->getMessage());
            return false;
        } finally {
            print_r("\n 视频合成覆盖率核对结束...\n");
        }
    }

    /**
     * 社媒合成覆盖率，筛选口径与 AutoVideoSynthesis::handleDeviceLevel 保持一致
     */
    private function checkSocial(): void
    {
        $query = SvDevice::alias('d')
            ->join('ai_persona p', 'd.persona_id = p.id')
            ->where('d.auto_type', 1)
            ->where('p.status', 1)
            ->where('p.publish_mode', 1)
            ->where('d.persona_id', '>', 0);

        // 未完成 = 布尔锁=0 且完成日期非今天（白天 reset 清锁后靠日期判断，任何时间跑都准确）
        $pendingSql = SvDevice::synthesisPendingDateSql(SvDevice::SYNTHESIS_SCENE_SOCIAL, 'd');
        $total = (int)(clone $query)->count();
        $undoneCount = (int)(clone $query)->where('d.synthesis_m', 0)->whereRaw($pendingSql)->count();
        $undoneCodes = $undoneCount > 0
            ? (clone $query)->where('d.synthesis_m', 0)->whereRaw($pendingSql)->limit(self::LIST_LIMIT)->column('d.device_code')
            : [];
        $this->report('社媒', 'ipVideoSynthesis', $total, $undoneCount, $undoneCodes);
    }

    /**
     * 朋友圈合成覆盖率，筛选口径与 WechatVideoSynthesis 保持一致
     */
    private function checkWechat(): void
    {
        $query = SvDevice::alias('d')
            ->join('ai_persona p', 'd.persona_id = p.id')
            ->where('d.auto_type', 1)
            ->whereIn('p.wechat_publish_mode', [1, 3])
            ->where('p.status', 1)
            ->where('d.is_first', 0)
            ->where('d.persona_id', '>', 0);

        // 未完成 = 布尔锁=0 且完成日期非今天（白天 reset 清锁后靠日期判断，任何时间跑都准确）
        $pendingSql = SvDevice::synthesisPendingDateSql(SvDevice::SYNTHESIS_SCENE_WECHAT, 'd');
        $total = (int)(clone $query)->count();
        $undoneCount = (int)(clone $query)->where('d.synthesis_w', 0)->whereRaw($pendingSql)->count();
        $undoneCodes = $undoneCount > 0
            ? (clone $query)->where('d.synthesis_w', 0)->whereRaw($pendingSql)->limit(self::LIST_LIMIT)->column('d.device_code')
            : [];
        $this->report('朋友圈', 'wechatVideoSynthesis', $total, $undoneCount, $undoneCodes);
    }

    /**
     * 布尔锁与完成日期双写一致性核对（日期化过渡期）
     * 8:30 时 reset_video_synthesis 尚未清锁，synthesis_m/w=1 的设备完成日期应为今天，
     * 不一致说明存在漏写日期的写入点，日期化切换前必须修复（漏写会导致设备次日重复生成）
     */
    private function checkDualWriteConsistency(): void
    {
        $today = date('Y-m-d');
        foreach (['synthesis_m' => 'ipVideoSynthesis', 'synthesis_w' => 'wechatVideoSynthesis'] as $field => $channel) {
            $codes = SvDevice::where($field, 1)
                ->where(function ($q) use ($field, $today) {
                    $q->whereNull($field . '_date')->whereOr($field . '_date', '<', $today);
                })
                ->limit(self::LIST_LIMIT)
                ->column('device_code');
            if (empty($codes)) {
                $msg = "双写核对[{$field}]：一致";
                Log::channel($channel)->write($msg);
            } else {
                $msg = "双写核对[{$field}]：发现 " . count($codes) . " 台设备布尔锁=1 但完成日期非今天，存在漏写日期的写入点，设备号：" . implode(',', $codes);
                Log::channel($channel)->error($msg);
            }
            print_r($msg . "\n");
        }
    }

    private function report(string $scene, string $channel, int $total, int $undoneCount, array $undoneCodes): void
    {
        if ($total <= 0) {
            $msg = "覆盖率核对[{$scene}]：无应生成设备";
            Log::channel($channel)->write($msg);
            print_r($msg . "\n");
            return;
        }

        $done = $total - $undoneCount;
        $rate = $done / $total;
        $msg = sprintf('覆盖率核对[%s]：应生成 %d，已完成 %d，覆盖率 %.2f%%', $scene, $total, $done, $rate * 100);
        if ($rate < self::ALARM_RATE) {
            $msg .= sprintf(
                '，低于阈值 %.0f%%，未完成设备号(最多%d台)：%s',
                self::ALARM_RATE * 100,
                self::LIST_LIMIT,
                implode(',', $undoneCodes)
            );
            Log::channel($channel)->error($msg);
        } else {
            Log::channel($channel)->write($msg);
        }
        print_r($msg . "\n");
    }
}
