<?php


namespace app\common\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * SunoStatus
 * @desc 音乐生成状态
 * @author dagouzi
 */
class ReplaceCron extends Command
{
    protected function configure()
    {
        $this->setName('replace_cron')
            ->setDescription('替换cron表达式');
    }

    protected function execute(Input $input, Output $output)
    {
        $env = file_get_contents(root_path() . '.env');

        if(strpos($env, '[IMAIWORK]') !== false) {
            $env = str_replace('[IMAIWORK]', '[PROJECT_KEY]', $env);
            file_put_contents(root_path() . '.env', $env);
            $output->writeln('cron表达式已替换');
            $id = \app\common\model\Crontab::where('command',  'replace_cron')->value('id');  
            \app\common\model\Crontab::destroy($id,true);
        }

        
        return true;
    }
}
