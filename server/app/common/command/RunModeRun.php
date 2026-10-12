<?php

namespace app\common\command;

use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\SynthesisConfig;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\CloudSvDevice;
use app\common\service\aiPersona\AiPersonaOptionService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Cache;

/**
 * 无手机模式：手动为指定人设跑一次出片，并把每一步实时打出来
 *
 * 凌晨那条 auto_video_synthesis 只在 00:30-08:00 干活，白天想验证只能手动触发。
 * 这个命令把链路本来就在写的日志（ipVideoSynthesis / shanjian 等通道）按增量读出来直接输出，
 * 所以看到的步骤明细与线上定时任务完全一致，不是另写一套。
 *
 * 用法:
 *   php think run-mode:run --persona=276               跑一次，打印全过程
 *   php think run-mode:run --persona=276 --check       只看前置检查（运行模式/条数/各闸门），只读不出片
 *   php think run-mode:run --persona=276 --force       今天已出过片也强制再跑（清当天标记与去重缓存）
 *   php think run-mode:run --persona=276 --watch=600   跑完继续盯合成进度，最多盯 600 秒
 */
class RunModeRun extends Command
{
    /** 跟出片有关的日志通道，按增量读出来当作步骤明细 */
    private const CHANNELS = [
        'ipVideoSynthesis' => '合成主链路',
        'explosionVideoSynthesis' => '爆款仿写',
        'shanjianQueue' => '中台排队',
        'shanjiannotice' => '合成回调',
    ];

    /** 轮询合成进度的间隔 */
    private const WATCH_INTERVAL = 10;

    /** 前置检查里最多列几条在途任务，多的折成一句总数 */
    private const PENDING_DETAIL_LIMIT = 5;

    private Output $out;
    private array $offsets = [];
    /** --force 临时抬高的每日条数，跑完必须还原，否则会永久改掉用户配置 */
    private ?int $restoreDailyCount = null;
    private int $personaId = 0;
    /** 出片子进程句柄，父进程被打断时要一并终止，别把活儿丢在后台继续跑 */
    private $childProcess = null;

    protected function configure()
    {
        $this->setName('run-mode:run')
            ->setDescription('无手机模式：为指定人设手动跑一次出片，实时打印每一步')
            ->addOption('persona', null, Option::VALUE_REQUIRED, '人设 id')
            ->addOption('check', null, Option::VALUE_NONE, '只跑前置检查：打印运行模式与各闸门，不出片、不扣算力、不改任何数据')
            ->addOption('force', null, Option::VALUE_NONE, '今天已出片也强制再多出一条（临时加一次配额，跑完还原）')
            ->addOption('watch', null, Option::VALUE_OPTIONAL, '跑完继续盯合成进度的秒数，如 --watch=600')
            // 内部用：父进程负责实时输出，子进程只闷头跑出片，别手动调
            ->addOption('child', null, Option::VALUE_NONE, '内部使用，勿手动调用');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->out = $output;
        $personaId = (int)$input->getOption('persona');
        $check = (bool)$input->getOption('check');
        $force = (bool)$input->getOption('force');
        $watch = (int)($input->getOption('watch') ?: 0);

        // 子进程：只跑出片，父进程负责读日志实时输出
        if ($input->getOption('child')) {
            try {
                (new AutoVideoSynthesis())->handlePersonaLevel();
                return 0;
            } catch (\Throwable $e) {
                $output->writeln('CHILD_ERROR ' . $e->getMessage());
                return 1;
            }
        }

        if ($personaId <= 0) {
            $output->error('必须提供 --persona');
            return 1;
        }

        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            $output->error("人设 {$personaId} 不存在");
            return 1;
        }

        $this->personaId = $personaId;

        // 只读自检：库里到底是什么模式、条数是多少、卡在哪道闸门，一次看全。
        // preflight 只有在 --force 时才会动数据，这里固定传 false，保证一个字节都不改。
        if ($check) {
            if ($force) {
                $output->writeln('  （--check 下 --force 被忽略：只读检查不改任何数据）');
            }
            $ok = $this->preflight($persona, false);
            $output->writeln('');
            $output->writeln($ok
                ? '  结论：闸门全过，去掉 --check 再跑就会出片'
                : '  结论：当前不会出片，原因见上面那行报错');
            $output->writeln('  （--check 没有建任务、没有扣算力、没有改配置）');
            return $ok ? 0 : 1;
        }

        if (!$this->preflight($persona, $force)) {
            return 1;
        }

        try {
            return $this->runOnce($personaId, $watch);
        } finally {
            $this->restoreDailyCount();
        }
    }

    private function runOnce(int $personaId, int $watch): int
    {
        $output = $this->out;

        $code = CloudSvDevice::codeFor($personaId);
        $before = ShanjianVideoTask::where('device_code', $code)->column('id');

        $this->title('执行出片');
        $output->writeln('  出片在后台进程里跑，下面是实时步骤。中途按 Ctrl+C 会打断，已建的任务保留。');
        $this->markOffsets();
        $start = microtime(true);
        if (!$this->runInChild($personaId, $code, $before)) {
            return 1;
        }
        $output->writeln(sprintf('  耗时 %.1f 秒', microtime(true) - $start));

        $after = ShanjianVideoTask::where('device_code', $code)->column('id');
        $created = array_values(array_diff($after, $before));

        $this->title('本次结果');
        if (empty($created)) {
            $output->writeln('  没有新建任务。常见原因：当天配额已满（加 --force）、素材或形象缺失、文案来源与模式不匹配，具体看上面的步骤明细。');
            return 0;
        }
        foreach ($created as $id) {
            $this->printTask((int)$id);
        }

        if ($watch > 0) {
            $this->watch($created, $watch);
        } else {
            $output->writeln('');
            $output->writeln('  想继续看合成进度，加 --watch=600 重跑，或直接看人设「内容记录」。');
        }
        return 0;
    }

    /**
     * 出片放子进程跑，父进程每秒读一次日志增量和新任务，实时打出来。
     * 不这么做的话 handlePersonaLevel 会阻塞几分钟一声不吭，看着像卡死——
     * 之前就因此被误当成卡住而中途打断，只建出一条。
     */
    private function runInChild(int $personaId, string $code, array $before): bool
    {
        // 必须用数组形式：字符串形式会套一层 sh -c，proc_terminate 只杀得掉那个 shell，
        // PHP 子进程会变成孤儿继续跑，用户 Ctrl+C 之后还在后台出片。
        $cmd = [
            PHP_BINARY,
            app()->getRootPath() . 'think',
            'run-mode:run',
            '--persona=' . $personaId,
            '--child',
        ];
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($proc)) {
            $this->out->error('无法启动出片子进程');
            return false;
        }
        $this->childProcess = $proc;
        $this->guardRestore();
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $seen = array_flip($before);
        $childError = '';
        while (true) {
            $status = proc_get_status($proc);
            foreach ([1, 2] as $fd) {
                $chunk = stream_get_contents($pipes[$fd]);
                if (is_string($chunk) && str_contains($chunk, 'CHILD_ERROR')) {
                    $childError = trim(str_replace('CHILD_ERROR', '', $chunk));
                }
            }
            $this->dumpLogs();
            foreach (ShanjianVideoTask::where('device_code', $code)->column('id') as $id) {
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $this->out->writeln('  ✔ 已建任务 #' . $id . '（' . date('H:i:s') . '）');
            }
            if (!$status['running']) {
                break;
            }
            sleep(1);
        }
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($proc);
        $this->childProcess = null;
        $this->dumpLogs();

        if ($childError !== '') {
            $this->out->error('出片过程抛错：' . $childError);
            return false;
        }
        return true;
    }

    /** 前置门禁：把所有会导致"点了没反应"的条件一次性摊开 */
    private function preflight(AiPersona $persona, bool $force): bool
    {
        $personaId = (int)$persona->id;
        $this->title('前置检查');
        $this->kv('人设', "{$persona->persona_name}（id {$personaId}，用户 {$persona->user_id}）");

        $isCloud = (int)$persona->run_mode === AiPersona::RUN_MODE_CLOUD;
        $this->kv('运行模式', $isCloud ? '无手机模式' : '手机模式');
        if (!$isCloud) {
            $this->out->error('该人设不是无手机模式，本命令只跑无手机模式的出片。手机模式请走设备维度的定时任务。');
            return false;
        }
        if ((int)$persona->status !== 1) {
            $this->out->error('人设未启用（status≠1），不会出片');
            return false;
        }
        if ((int)$persona->publish_mode !== 1) {
            $this->out->error('人设为成品库直发（publish_mode=2），不走 AI 合成');
            return false;
        }

        $config = SynthesisConfig::where('persona_id', $personaId)->findOrEmpty();
        if ($config->isEmpty()) {
            $this->out->error('该人设还没有视频剪辑配置，先在小程序打开一次「视频剪辑」保存');
            return false;
        }
        $count = SynthesisConfig::normalizeDailyVideoCount($config->daily_video_count);
        $effective = $count > 0 ? $count : SynthesisConfig::DAILY_VIDEO_COUNT_CLOUD_DEFAULT;
        $this->kv('每日出片条数', $count > 0 ? (string)$count : "未配置，按默认 {$effective} 条");
        $this->kv('文案来源', $this->copySourceText((int)$config->copywriting_source));
        $this->kv('生成类型', json_encode($config->generation_types, JSON_UNESCAPED_UNICODE));

        $enabled = AiPersonaOptionService::isEnabledForPersonaId($personaId, 'video_clip');
        $this->kv('视频剪辑员工', $enabled ? '已启用' : '已关闭（会直接标记当天完成并跳过）');

        $today = date('Y-m-d');
        $done = (string)$persona->cloud_synthesis_date === $today;
        $this->kv('今天是否已出片', $done ? '是' : '否');
        $cacheKey = 'command_video_synthesis_' . CloudSvDevice::codeFor($personaId);
        $cached = Cache::store('material_redis')->has($cacheKey);
        $this->kv('一小时去重缓存', $cached ? '命中（会跳过）' : '未命中');

        $todayTasks = ShanjianVideoTask::where('device_code', CloudSvDevice::codeFor($personaId))
            ->whereBetween('create_time', [strtotime($today . ' 00:00:00'), strtotime($today . ' 23:59:59')])
            ->field('id,status,create_time')
            ->order('id', 'asc')
            ->select();
        $generated = count($todayTasks);
        $this->kv('今天已生成', "{$generated} / {$effective} 条");
        $this->printTodayTaskBreakdown($todayTasks, $generated >= $effective);
        $todo = max(0, $effective - $generated);
        if ($todo > 0) {
            $this->kv('本次预计出片', $todo . ' 条，每条约 1-2 分钟，合计约 ' . ($todo * 2) . ' 分钟');
        }

        if ($force) {
            AiPersona::where('id', $personaId)->update(['cloud_synthesis_date' => null]);
            Cache::store('material_redis')->delete($cacheKey);
            $this->out->writeln('  已按 --force 清掉当天完成标记与去重缓存');
            // 真正的闸门是"今天已建任务数 >= 每日条数"，只清标记不够，
            // 这里临时把配额抬到"已生成 + 1"，跑完在 finally 里还原成用户配的值
            if ($generated >= $effective) {
                $this->restoreDailyCount = (int)$config->daily_video_count;
                $this->guardRestore();
                SynthesisConfig::where('persona_id', $personaId)->update(['daily_video_count' => $generated + 1]);
                $this->out->writeln('  已临时把每日条数抬到 ' . ($generated + 1) . ' 条以便多出一条，跑完自动还原');
            }
            return true;
        }

        if ($done || $cached || $generated >= $effective) {
            $this->out->writeln('');
            $this->out->error('当前条件下不会出片（今天已完成 / 缓存命中 / 配额已满）。要强制再跑一次请加 --force');
            return false;
        }
        return true;
    }

    /**
     * 把「今天已生成 N 条」拆成 成功 / 在途 / 失败。
     *
     * 在途(等待配音、待下发、合成中)按设计同样占当天配额，
     * 只报一个总数的话，"任务卡在半路"和"配额正常用满了"打出来一模一样，
     * 用户只能看到"不出片"而看不出是哪一种。卡住的在途要等 24 小时超时收口才会转失败，
     * 当天不会自愈，所以这里把它单列出来并带上已等待时长。
     */
    private function printTodayTaskBreakdown($tasks, bool $quotaFull): void
    {
        if (count($tasks) === 0) {
            return;
        }

        $success = 0;
        $failed = 0;
        $pending = [];
        foreach ($tasks as $task) {
            $status = (int)$task->status;
            if ($status === ShanjianVideoTask::STATUS_SUCCESS) {
                $success++;
            } elseif ($status === ShanjianVideoTask::STATUS_FAILED) {
                $failed++;
            } else {
                $pending[] = $task;
            }
        }

        $this->kv('其中', sprintf('成功 %d ／ 在途 %d ／ 失败 %d', $success, count($pending), $failed));
        if (empty($pending)) {
            return;
        }

        $lines = [];
        foreach (array_slice($pending, 0, self::PENDING_DETAIL_LIMIT) as $task) {
            $lines[] = sprintf(
                '#%d %s 已 %s',
                (int)$task->id,
                $this->statusText((int)$task->status),
                // 模型给 create_time 挂了获取器返回 'Y-m-d H:i:s'，(int) 转出来是年份，必须取原始值
                $this->ageText(time() - (int)$task->getData('create_time'))
            );
        }
        if (count($pending) > self::PENDING_DETAIL_LIMIT) {
            $lines[] = '… 共 ' . count($pending) . ' 条';
        }
        $this->kv('在途任务', implode('；', $lines));

        if ($quotaFull) {
            $this->out->writeln('  提示：在途任务同样占当天配额，它们不推进今天就不会再补新片；要再跑一条加 --force');
            $this->out->writeln('        长时间停在「等待配音」先查 minimax_shanjian_cron 是否在跑，停在「待下发」查素材转码队列');
        }
    }

    private function ageText(int $seconds): string
    {
        $seconds = max(0, $seconds);
        if ($seconds < 60) {
            return $seconds . ' 秒';
        }
        if ($seconds < 3600) {
            return intdiv($seconds, 60) . ' 分钟';
        }
        return intdiv($seconds, 3600) . ' 小时 ' . intdiv($seconds % 3600, 60) . ' 分';
    }

    /**
     * 兜住"临时抬高的配额没还原"这件事。
     * 只靠 finally 不够：Ctrl+C 发来的 SIGINT 默认直接终止进程，finally 不会执行，
     * 用户的每日条数就会被永久多留 1 条。
     */
    private bool $guarded = false;

    private function guardRestore(): void
    {
        if ($this->guarded) {
            return;
        }
        $this->guarded = true;
        register_shutdown_function(function () {
            $this->terminateChild();
            $this->restoreDailyCount();
        });
        if (!function_exists('pcntl_signal') || !function_exists('pcntl_async_signals')) {
            return;
        }
        pcntl_async_signals(true);
        foreach ([SIGINT, SIGTERM] as $signal) {
            pcntl_signal($signal, function () {
                $this->terminateChild();
                $this->restoreDailyCount();
                exit(130);
            });
        }
    }

    /** 父进程被打断时终止出片子进程，避免它在后台继续跑到一半 */
    private function terminateChild(): void
    {
        if (!is_resource($this->childProcess)) {
            return;
        }
        try {
            proc_terminate($this->childProcess, SIGTERM);
        } catch (\Throwable $e) {
            // 子进程可能已经退出
        }
        $this->childProcess = null;
    }

    /** 还原 --force 临时抬高的配额；可重复调用 */
    private function restoreDailyCount(): void
    {
        if ($this->restoreDailyCount === null || $this->personaId <= 0) {
            return;
        }
        $value = $this->restoreDailyCount;
        $this->restoreDailyCount = null;
        try {
            SynthesisConfig::where('persona_id', $this->personaId)->update(['daily_video_count' => $value]);
            $this->out->writeln('');
            $this->out->writeln('  已把每日条数还原为 ' . $value);
        } catch (\Throwable $e) {
            // 进程正在退出时输出流可能已关闭，还原本身成功即可
        }
    }

    private function copySourceText(int $source): string
    {
        return [
            SynthesisConfig::COPYWRITING_SOURCE_IMITATE => '找爆款仿写（无手机模式下会自动改为 AI 生成）',
            SynthesisConfig::COPYWRITING_SOURCE_AI => 'AI 直接生成',
            SynthesisConfig::COPYWRITING_SOURCE_NONE => '无需文案',
            SynthesisConfig::COPYWRITING_SOURCE_LIBRARY => '文案库',
            SynthesisConfig::COPYWRITING_SOURCE_HOTSPOT => '热点生成',
        ][$source] ?? "未知({$source})";
    }

    /** 记录各日志通道当前文件末尾，之后只读这次跑出来的增量 */
    private function markOffsets(): void
    {
        $this->offsets = [];
        foreach (array_keys(self::CHANNELS) as $channel) {
            $file = $this->logFile($channel);
            $this->offsets[$channel] = is_file($file) ? filesize($file) : 0;
        }
    }

    private function logFile(string $channel): string
    {
        return app()->getRootPath() . 'runtime/log/' . $channel . '/' . date('Ym') . '/' . date('d') . '.log';
    }

    /** 把增量日志按通道打出来，这就是链路的真实步骤明细 */
    private function dumpLogs(): void
    {
        foreach (self::CHANNELS as $channel => $label) {
            $file = $this->logFile($channel);
            if (!is_file($file)) {
                continue;
            }
            $size = filesize($file);
            $from = $this->offsets[$channel] ?? 0;
            if ($size <= $from) {
                continue;
            }
            $fp = fopen($file, 'rb');
            if ($fp === false) {
                continue;
            }
            fseek($fp, $from);
            $chunk = stream_get_contents($fp, $size - $from);
            fclose($fp);
            $this->offsets[$channel] = $size;

            $lines = array_values(array_filter(explode("\n", (string)$chunk), static fn($l) => trim($l) !== ''));
            if (empty($lines)) {
                continue;
            }
            $this->out->writeln("  ── {$label} ──");
            foreach ($lines as $line) {
                // 去掉时间戳前缀里的日期，只留时分秒，控制台更好读
                $line = preg_replace('/^\[\d{4}-\d{2}-\d{2}T(\d{2}:\d{2}:\d{2})[^\]]*\]/', '[$1]', $line);
                $this->out->writeln('  ' . mb_substr($line, 0, 400));
            }
        }
    }

    private function printTask(int $taskId): void
    {
        $task = ShanjianVideoTask::where('id', $taskId)->findOrEmpty();
        if ($task->isEmpty()) {
            return;
        }
        $this->out->writeln("  任务 #{$task->id}  {$this->statusText((int)$task->status)}  类型={$this->typeText((int)$task->shanjian_type)}");
        $this->out->writeln('    设备号：' . $task->device_code);
        if (trim((string)$task->title) !== '') {
            $this->out->writeln('    标题：' . mb_substr((string)$task->title, 0, 60));
        }
        if (trim((string)$task->msg) !== '') {
            $this->out->writeln('    文案：' . mb_substr((string)$task->msg, 0, 80) . '…');
        }
    }

    private function statusText(int $status): string
    {
        return [
            -1 => '等待配音',
            0 => '待下发',
            1 => '合成中',
            2 => '合成失败',
            3 => '合成成功',
        ][$status] ?? "状态{$status}";
    }

    private function typeText(int $type): string
    {
        return [1 => '数字人口播', 2 => '真人口播', 3 => '素材混剪', 4 => '新闻体混剪', 5 => '数字人无包装'][$type] ?? (string)$type;
    }

    /** 盯着任务往下走，顺带把这期间各通道新写的日志一起打出来 */
    private function watch(array $taskIds, int $seconds): void
    {
        $this->title("盯合成进度（最多 {$seconds} 秒，每 " . self::WATCH_INTERVAL . " 秒查一次）");
        $deadline = time() + $seconds;
        $last = [];
        while (time() < $deadline) {
            $allDone = true;
            foreach ($taskIds as $id) {
                $task = ShanjianVideoTask::where('id', $id)->findOrEmpty();
                if ($task->isEmpty()) {
                    continue;
                }
                $status = (int)$task->status;
                $key = 'task' . $id;
                $stamp = $status . '|' . $task->queue_status . '|' . $task->download_status;
                if (($last[$key] ?? '') !== $stamp) {
                    $last[$key] = $stamp;
                    $this->out->writeln(sprintf(
                        '  [%s] 任务 #%d %s  排队=%s  成片下载=%s',
                        date('H:i:s'),
                        $id,
                        $this->statusText($status),
                        $task->queue_status ?: '-',
                        $this->downloadText((int)$task->download_status)
                    ));
                }
                if ($status === 3 && (int)$task->download_status === 2) {
                    $this->out->writeln('    成片：' . $task->video_result_url);
                    continue;
                }
                if ($status === 2) {
                    $this->out->writeln('    失败原因：' . mb_substr((string)$task->remark, 0, 120));
                    continue;
                }
                $allDone = false;
            }
            $this->dumpLogs();
            if ($allDone) {
                $this->out->writeln('  全部到达终态');
                return;
            }
            sleep(self::WATCH_INTERVAL);
        }
        $this->out->writeln('  已到观察时限，任务仍在推进，稍后可在人设「内容记录」里看结果');
    }

    private function downloadText(int $status): string
    {
        return [0 => '待下载', 1 => '下载中', 2 => '已转存', 3 => '下载失败'][$status] ?? (string)$status;
    }

    private function title(string $text): void
    {
        $this->out->writeln('');
        $this->out->writeln("==== {$text} ====");
    }

    /** 中文按显示宽度补空格，sprintf 的 %-16s 数的是字节，中文会歪 */
    private function kv(string $k, string $v): void
    {
        $pad = max(1, 18 - mb_strwidth($k, 'UTF-8'));
        $this->out->writeln('  ' . $k . str_repeat(' ', $pad) . $v);
    }
}
