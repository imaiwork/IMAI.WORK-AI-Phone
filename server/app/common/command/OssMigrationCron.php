<?php


namespace app\common\command;

use app\common\logic\OssLogic;
use app\common\service\ConfigService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

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
        $storage = ConfigService::get('storage', 'default', 'local');
        $data = ConfigService::get('storage', $storage);
        $key = 'oss_migration_cron' ;
        if (isset($data['migration']) && in_array($data['migration'],[0,2]) ){
            return true;
        }
        cache($key, 1);

        OssLogic::migrationCron();
        cache($key, 0);
        return true;
    }
}
