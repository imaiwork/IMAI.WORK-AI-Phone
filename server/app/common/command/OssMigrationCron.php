<?php


namespace app\common\command;

use app\common\logic\OssLogic;
use app\common\service\ConfigService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * SunoStatus
 * @desc 音乐生成状态
 * @author dagouzi
 */
class OssMigrationCron extends Command
{
    protected function configure()
    {
        $this->setName('oss_migration_cron')
            ->setDescription('oss迁移');
    }

    protected function execute(Input $input, Output $output)
    {
        $key = 'oss_migration_cron';
        try {
            $storage = ConfigService::get('storage', 'default', 'local');
            $data = ConfigService::get('storage', $storage);
            if (isset($data['migration']) && in_array($data['migration'], [0, 2])) {
                return true;
            }
            cache($key, 1);
            OssLogic::migrationCron();
            cache($key, 0);
            return true;
        } catch (\Exception $e) {
            Log::channel('crontab')->error('oss 处理失败 ' . $e->getMessage());
            cache($key, 0);
        } finally {
            cache($key, 0);
        }
        return true;
    }
}
