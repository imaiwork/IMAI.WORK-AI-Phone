<?php

namespace app\common\command;

use app\common\service\digitalHuman\DefaultPublicAnchorAssetService;
use app\common\service\digitalHuman\DefaultPublicVoiceAssetService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

class SyncDefaultPublicAnchorAssets extends Command
{
    protected function configure()
    {
        $this->setName('sync_default_public_anchor_assets')
            ->setDescription('将默认公共形象/音色母版文件同步到当前站长存储')
            ->addOption('force', 'f', Option::VALUE_NONE, '强制重新同步');
    }

    protected function execute(Input $input, Output $output)
    {
        $force = (bool)$input->getOption('force');
        try {
            $anchorResult = DefaultPublicAnchorAssetService::syncToStorage($force);
            $voiceResult = DefaultPublicVoiceAssetService::syncToStorage($force);

            $output->writeln(sprintf(
                '默认公共形象母版同步: synced=%d skipped=%d failed=%d',
                $anchorResult['synced'],
                $anchorResult['skipped'],
                count($anchorResult['failed'])
            ));
            $output->writeln(sprintf(
                '默认公共音色母版同步: synced=%d skipped=%d failed=%d',
                $voiceResult['synced'],
                $voiceResult['skipped'],
                count($voiceResult['failed'])
            ));

            $failed = array_merge($anchorResult['failed'], $voiceResult['failed']);
            if ($failed !== []) {
                foreach ($failed as $item) {
                    $output->writeln($item['file'] . ': ' . $item['error']);
                }
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('crontab')->error('默认公共母版同步失败: ' . $e->getMessage());
            $output->writeln('默认公共母版同步失败: ' . $e->getMessage());
            return false;
        }
    }
}
