<?php

namespace app\common\command;

use app\common\model\sv\SvAddWechatRecord;
use app\common\service\sv\DySearchWechatVerifyService;
use app\common\service\sv\ServiceContactFilter;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

/**
 * 存量官方服务号清理：
 * 用统一黑名单 ServiceContactFilter 重放历史加微记录，把此前黑名单漏放、
 * 已写入加微记录表（sv_add_wechat_record，前缀随环境）且仍处于待派发状态的官方服务号联系方式
 * 闭环置为无效（不删行，留痕便于回溯）。
 *
 * 范围限定视频号(channel=1)/抖音(channel=4)两条获客链路；
 * 默认仅预览统计不写库，显式追加 --execute 才执行置无效。
 */
class SvServiceContactClean extends Command
{
    /** 单次执行最多遍历的记录数上限（防止极端数据量下长时间运行） */
    private const MAX_SCAN_LIMIT = 200000;

    /** 输出明细的条数上限 */
    private const SAMPLE_LIMIT = 20;

    protected function configure()
    {
        $this->setName('sv:service-contact-clean')
            ->setDescription('清理存量加微记录中的官方服务号联系方式（视频号/抖音获客链路，默认预览，--execute 执行置无效）')
            ->addOption('execute', null, Option::VALUE_NONE, '实际执行置无效操作（默认仅预览统计）')
            ->addOption('channel', null, Option::VALUE_OPTIONAL, '限定渠道（1视频号/4抖音，可逗号分隔，默认两个都查）', '')
            ->addOption('limit', null, Option::VALUE_OPTIONAL, '每批次读取的记录数', 500);
    }

    protected function execute(Input $input, Output $output)
    {
        $channels = $this->resolveChannels((string)$input->getOption('channel'));
        if (empty($channels)) {
            $output->writeln(json_encode([
                'code' => 0,
                'msg' => '无效的渠道参数，仅支持 1(视频号)或 4(抖音)，可逗号分隔',
            ], JSON_UNESCAPED_UNICODE));
            return 1;
        }

        $batchLimit = min(2000, max(1, (int)$input->getOption('limit') ?: 500));
        $execute = (bool)$input->getOption('execute');

        $this->ensureLogDirectory();

        try {
            $summary = $this->clean($channels, $batchLimit, $execute);
        } catch (\Throwable $e) {
            Log::channel('sv_contact_clean')->write('官方服务号存量清理异常: ' . $e->getMessage());
            $output->writeln(json_encode([
                'code' => 0,
                'msg' => '清理执行异常，详情见 runtime/log/sv_contact_clean/ 日志',
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
            return 1;
        }

        $summary['execute'] = $execute;
        $summary['channel'] = $channels;
        $output->writeln(json_encode($summary, JSON_UNESCAPED_UNICODE));
        return 0;
    }

    /**
     * 解析渠道参数：空串默认 [1,4]；仅允许 1/4，其余忽略
     * @return list<int>
     */
    private function resolveChannels(string $raw): array
    {
        $raw = trim(str_replace('，', ',', $raw));
        if ($raw === '') {
            return [1, 4];
        }
        $allowed = [1, 4];
        $parsed = array_map('intval', explode(',', $raw));

        return array_values(array_unique(array_intersect($allowed, $parsed)));
    }

    /**
     * 预热专用日志目录：
     * think 日志 File 驱动初始化存在并发竞态（is_dir 判断与 mkdir 之间窗口），
     * 多进程同刻启动会对已存在目录触发 mkdir 警告并被框架转为异常。
     * 这里先静默建目录打通窗口；目录已建则零开销。
     */
    private function ensureLogDirectory(): void
    {
        $channel = (array)config('log.channels.sv_contact_clean', []);
        $path = (string)($channel['path'] ?? '');
        if ($path === '' || is_dir($path)) {
            return;
        }
        @mkdir($path, 0755, true);
    }

    /**
     * 按主键游标分批扫描，命中官方服务号的记录置无效
     * @param list<int> $channels
     * @return array<string, mixed>
     */
    private function clean(array $channels, int $batchLimit, bool $execute): array
    {
        $lastId = 0;
        $scanned = 0;
        $hit = 0;
        $updated = 0;
        $samples = [];

        Log::channel('sv_contact_clean')->write('官方服务号存量清理开始 模式=' . ($execute ? '执行' : '预览')
            . ' 渠道=' . implode(',', $channels)
            . ' 批次大小=' . $batchLimit);

        do {
            $rows = SvAddWechatRecord::where('id', '>', $lastId)
                ->where('channel', 'in', $channels)
                ->where('reg_wechat', '<>', '')
                ->where('status', 'in', DySearchWechatVerifyService::DISPATCH_STATUSES)
                ->field('id,channel,user_id,reg_wechat')
                ->order('id', 'asc')
                ->limit($batchLimit)
                ->select()
                ->toArray();
            if (empty($rows)) {
                break;
            }
            foreach ($rows as $row) {
                $lastId = (int)$row['id'];
                $scanned++;
                $contact = trim((string)$row['reg_wechat']);
                if ($contact === '' || !ServiceContactFilter::isServiceContact($contact)) {
                    continue;
                }
                $hit++;
                Log::channel('sv_contact_clean')->write('官方服务号存量命中 record_id=' . $lastId
                    . ' channel=' . (int)$row['channel']
                    . ' user_id=' . (int)$row['user_id']
                    . ' 联系方式=' . $contact);
                if (count($samples) < self::SAMPLE_LIMIT) {
                    $samples[] = [
                        'id' => $lastId,
                        'channel' => (int)$row['channel'],
                        'user_id' => (int)$row['user_id'],
                        'reg_wechat' => $contact,
                    ];
                }
                if ($execute) {
                    // 限定仍处于待派发状态才更新：与派发端认领并发竞争时不覆盖执行中的行
                    $affected = SvAddWechatRecord::where('id', $lastId)
                        ->where('status', 'in', DySearchWechatVerifyService::DISPATCH_STATUSES)
                        ->update([
                            'status' => DySearchWechatVerifyService::ADD_STATUS_INVALID,
                            'result' => '官方服务号，存量清理拦截',
                            'update_time' => time(),
                        ]);
                    if ($affected > 0) {
                        $updated++;
                        Log::channel('sv_contact_clean')->write('存量官方服务号置无效成功 record_id=' . $lastId);
                    } else {
                        // 命中后更新失败：通常为派发并发已把该行移出待派发状态，留待下轮复核
                        Log::channel('sv_contact_clean')->write('存量官方服务号置无效未生效(可能已被并发移出待派发状态) record_id=' . $lastId);
                    }
                }
            }
            if ($scanned >= self::MAX_SCAN_LIMIT) {
                break;
            }
        } while (count($rows) === $batchLimit);

        Log::channel('sv_contact_clean')->write('官方服务号存量清理结束 扫描=' . $scanned
            . ' 命中=' . $hit
            . ' 置无效=' . $updated
            . ' 截断=' . ($scanned >= self::MAX_SCAN_LIMIT ? '是' : '否'));

        return [
            'msg' => $execute ? '存量官方服务号清理完成' : '预览完成（未写库，追加 --execute 执行）',
            'scanned' => $scanned,
            'hit_count' => $hit,
            'updated_count' => $updated,
            'scan_truncated' => $scanned >= self::MAX_SCAN_LIMIT,
            'samples' => $samples,
        ];
    }
}
