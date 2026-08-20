<?php

namespace app\common\command;

use app\common\model\file\File;
use app\common\service\UploadService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

/**
 * 巡检并恢复卡住的素材转码任务。
 */
class MediaTranscodeRecoverCron extends Command
{
    protected function configure()
    {
        $this->setName('media:transcode-recover')
            ->setDescription('恢复长时间停留在待转码/转码中的素材任务')
            ->addOption('stale', null, Option::VALUE_OPTIONAL, '超过多少秒未更新视为卡住')
            ->addOption('limit', null, Option::VALUE_OPTIONAL, '单次最多处理数量')
            ->addOption('dry-run', null, Option::VALUE_NONE, '只打印不投递');
    }

    protected function execute(Input $input, Output $output)
    {
        $staleSeconds = (int)($input->getOption('stale') ?: config('transcoding.submit_stale_seconds', 1800));
        $limit = (int)($input->getOption('limit') ?: 50);
        $dryRun = (bool)$input->getOption('dry-run');

        if ($staleSeconds <= 0) {
            $staleSeconds = 1800;
        }
        if ($limit <= 0 || $limit > 500) {
            $limit = 50;
        }

        $deadline = time() - $staleSeconds;
        $rows = File::whereIn('transcode_status', [1, 2])
            ->where('update_time', '<', $deadline)
            ->field('id,uri,transcode_status,update_time')
            ->order('update_time asc,id asc')
            ->limit($limit)
            ->select()
            ->toArray();

        $output->writeln("发现卡住转码任务: " . count($rows) . " 条 stale={$staleSeconds}s limit={$limit}");

        $requeued = 0;
        $failed = 0;
        foreach ($rows as $row) {
            $fileId = (int)($row['id'] ?? 0);
            $uri = trim((string)($row['uri'] ?? ''));
            $status = (int)($row['transcode_status'] ?? 0);
            $updateTime = (int)($row['update_time'] ?? 0);

            if ($fileId <= 0) {
                continue;
            }

            if ($uri === '') {
                if (!$dryRun) {
                    File::where('id', $fileId)->update([
                        'transcode_status' => 4,
                        'update_time' => time(),
                    ]);
                }
                $failed++;
                $this->logRecover("无法恢复：uri为空 file_id={$fileId} status={$status} update_time={$updateTime}");
                $output->writeln("失败 file_id={$fileId} uri为空");
                continue;
            }

            $saveDir = ltrim(str_replace('\\', '/', dirname($uri)), './');
            if ($saveDir === '.' || $saveDir === '') {
                $saveDir = 'uploads/file/' . date('Ymd');
            }
            $localPath = public_path() . ltrim($uri, '/');

            $payload = [
                'file_id' => $fileId,
                'local_path' => $localPath,
                'oss_uri' => $uri,
                'save_dir' => $saveDir,
                'clip' => [],
                'custom_specs' => null,
                'recover_from' => 'media:transcode-recover',
                'recover_at' => time(),
            ];

            if (!$dryRun) {
                try {
                    UploadService::dispatchTranscodeJob($payload);
                    $requeued++;
                    $this->logRecover("已重新投递 file_id={$fileId} uri={$uri} old_status={$status} update_time={$updateTime}");
                } catch (\Throwable $e) {
                    File::where('id', $fileId)->update([
                        'transcode_status' => 4,
                        'update_time' => time(),
                    ]);
                    $failed++;
                    $this->logRecover("重新投递失败 file_id={$fileId} uri={$uri} err=" . $e->getMessage());
                    $output->writeln("失败 file_id={$fileId} err=" . $e->getMessage());
                    continue;
                }
            } else {
                $requeued++;
                $this->logRecover("[dry-run] 将重新投递 file_id={$fileId} uri={$uri} old_status={$status} update_time={$updateTime}");
            }

            $output->writeln("恢复 file_id={$fileId} uri={$uri}");
        }

        $output->writeln("完成 requeued={$requeued} failed={$failed} dry_run=" . ($dryRun ? 'yes' : 'no'));
        return 0;
    }

    private function logRecover(string $message): void
    {
        Log::channel('ffmpeg')->write('[转码巡检] ' . $message);
    }
}
