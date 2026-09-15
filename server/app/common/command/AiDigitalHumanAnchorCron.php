<?php


namespace app\common\command;

use app\api\logic\DigitalHumanLogic;
use app\common\service\digitalHuman\DefaultPublicAnchorAssetService;
use app\common\service\digitalHuman\DefaultPublicAnchorConfig;
use app\common\service\digitalHuman\DefaultPublicVoiceAssetService;
use app\common\service\digitalHuman\DefaultPublicVoiceConfig;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * DigitalHumanAnchorCron
 * @desc 公共数字人形象任务
 */
class AiDigitalHumanAnchorCron extends Command
{
    protected function configure()
    {
        $this->setName('ai_digital_human_anchor_cron')
            ->setDescription('ai授权数字人形象任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->syncDefaultPublicAnchorAssetsOnce($output);
        $this->syncDefaultPublicVoiceAssetsOnce($output);

        print_r("\n ai授权视频公共形象...'\n");
        DigitalHumanLogic::createDigitalHumanAnchorAiCron();
        return true;
    }

    /**
     * 默认公共形象母版同步到站长存储，成功一次后不再执行。
     */
    private function syncDefaultPublicAnchorAssetsOnce(Output $output): void
    {
        $config = DefaultPublicAnchorConfig::get();
        if (empty($config['enabled'])) {
            return;
        }
        if (DefaultPublicAnchorAssetService::isSynced()) {
            return;
        }

        $output->writeln('默认公共形象母版未同步，开始执行一次性同步...');
        $result = DefaultPublicAnchorAssetService::syncToStorage();
        if ($result['failed'] !== []) {
            foreach ($result['failed'] as $item) {
                $message = $item['file'] . ': ' . $item['error'];
                $output->writeln('默认公共形象母版同步失败: ' . $message);
                Log::channel('digital')->error('默认公共形象母版同步失败: ' . $message);
            }
            return;
        }

        $output->writeln(sprintf('默认公共形象母版同步完成: synced=%d', $result['synced']));
    }

    /**
     * 默认公共音色母版同步到站长存储，成功一次后不再执行。
     */
    private function syncDefaultPublicVoiceAssetsOnce(Output $output): void
    {
        $config = DefaultPublicVoiceConfig::get();
        if (empty($config['enabled'])) {
            return;
        }
        if (DefaultPublicVoiceAssetService::isSynced()) {
            return;
        }

        $output->writeln('默认公共音色母版未同步，开始执行一次性同步...');
        $result = DefaultPublicVoiceAssetService::syncToStorage();
        if ($result['failed'] !== []) {
            foreach ($result['failed'] as $item) {
                $message = $item['file'] . ': ' . $item['error'];
                $output->writeln('默认公共音色母版同步失败: ' . $message);
                Log::channel('digital')->error('默认公共音色母版同步失败: ' . $message);
            }
            return;
        }

        $output->writeln(sprintf('默认公共音色母版同步完成: synced=%d', $result['synced']));
    }
}
