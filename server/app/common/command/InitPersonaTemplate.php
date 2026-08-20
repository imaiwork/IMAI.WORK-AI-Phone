<?php


namespace app\common\command;

use app\common\model\aiPersona\AiPersona;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\sv\SvDeviceExecutionSchedule;

/**
 * @author dagouzi
 */
class InitPersonaTemplate extends Command
{
    protected function configure()
    {
        $this->setName('init_persona_template')
            ->setDescription('初始化人物模板');
    }

    protected function execute(Input $input, Output $output)
    {
        print_r("\n 初始化人物模板...\n");
        return;
        try {
            $personas = AiPersona::where('workflow_template_id', 0)->order('id', 'desc')->limit(10)->select();
            //print_r($personas->toArray());die;
            foreach ($personas as $persona) {
                self::createPersonaExclusiveWorkflow($persona);
            }
        } catch (\Throwable $th) {
            //throw $th;
            \think\facade\Log::channel('auto')->write('初始化人物模板失败' . $th->__toString(), 'create');
        }
    }

    public static function createPersonaExclusiveWorkflow(AiPersona $persona): AiPersona
    {
        Db::startTrans();
        try {
            $template = MarketingTemplate::where('persona_id', $persona->id)->where('type', 1)->where('category_id', 1)->findOrEmpty();
            if ($template->isEmpty()) {
                $template = MarketingTemplate::create([
                    'user_id' => $persona->user_id,
                    'persona_id' => $persona->id,
                    'name' => $persona->persona_name . '专属工作流',
                    'type' => 1,
                    'category_id' => 1,
                    'operation_preference' => 1,
                    'description' => '系统根据您当前配置的IP人设自动生成的专属任务流，保证基础运营效果，不可更改。',
                    'status' => 1,
                    'detail_content' => '',
                    'detail_task_types' => '',
                    'detail_users' => '',
                    'detail_images' => [],
                    'detail_videos' => [],
                    'is_system_generated' => 1,
                    'create_time' => time(),
                ]);
            }

            $schedules = SvDeviceExecutionSchedule::where('persona_type', $persona->persona_type)->order('start_time', 'asc')->select();

            if (!$schedules->isEmpty()) {
                $insertData = [];
                foreach ($schedules as $schedule) {
                    array_push($insertData, [
                        'user_id' => $persona->user_id,
                        'persona_id' => $persona->id,
                        'template_id' => $template->id,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'task_category' => $schedule->task_category,
                        'scene' => $schedule->scene,
                        'platform' => self::getDefaultPlatform($schedule->platform),
                        'remark' => $schedule->remark,
                        'create_time' => time(),
                    ]);
                }
                MarketingTemplateSchedule::insertAll($insertData);
                $persona->workflow_template_id = $template->id;
                $persona->save();
                Db::commit();
            }
        } catch (\Throwable $th) {
            Db::rollback();
            \think\facade\Log::channel('auto')->write($persona->persona_name . '初次创建专属工作流失败' . $th->__toString(), 'create');
        }
        return $persona;
    }

    public static function getDefaultPlatform(array $platform): string
    {
        $account = [];
        foreach ($platform as $key => $item) {
            array_push($account, [
                'account_type' => $item,
                'order' => $key + 1,
            ]);
        }
        return json_encode($account, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
