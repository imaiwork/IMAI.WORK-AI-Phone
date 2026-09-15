<?php

namespace app\common\command;

use app\common\model\aiPersona\AiPersona;
use app\common\service\workData\PersonaBindService;
use app\common\service\workData\WorkDataFetchService;
use app\common\service\workData\WorkDataLog;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class WorkDataSyncCron extends Command
{
    protected function configure()
    {
        $this->setName('work_data_sync')->setDescription('每小时同步已开启的作品数据');
    }

    protected function execute(Input $input, Output $output)
    {
        $failed = 0;
        AiPersona::where('work_data_auto_sync', 1)
            ->where('status', AiPersona::STATUS_ENABLED)->whereNull('delete_time')
            ->chunk(100, static function ($personas) use (&$failed) {
                foreach ($personas as $persona) {
                    $userId = (int)$persona->user_id;
                    $personaId = (int)$persona->id;
                    try {
                        $platforms = PersonaBindService::boundAccounts($userId, $personaId);
                    } catch (\Throwable $e) {
                        $failed++;
                        WorkDataLog::exception('定时同步账号读取失败：人设=' . $personaId, $e);
                        continue;
                    }
                    foreach ($platforms as $platform => $accounts) {
                        foreach ($accounts as $account) {
                            try {
                                WorkDataFetchService::fetch($userId, $personaId, $platform, false, $account['account'], true);
                            } catch (\Throwable $e) {
                                $failed++;
                                WorkDataLog::exception('作品数据定时同步失败：人设=' . $personaId, $e);
                            }
                        }
                    }
                }
            });
        $output->writeln('作品数据定时同步完成，失败数：' . $failed);
        return $failed > 0 ? 1 : 0;
    }
}
