<?php

namespace app\common\command;

use app\api\logic\aiPersona\SynthesisConfigLogic;
use app\api\logic\videoSynthesis\CopywritingAiGenerationLogic;
use app\api\logic\videoSynthesis\CopywritingImitationLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;
use app\common\model\aiPersona\SynthesisConfig;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\CloudSvDevice;
use app\common\model\sv\SvDevice;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Cache;
use think\facade\Db;

/**
 * 运行模式（手机 / 无手机）自检
 *
 * 用法:
 *   php think run-mode:selfcheck                      只读自检，任何环境都能跑，不改任何数据
 *   php think run-mode:selfcheck --persona=239        追加单人设检查：切模式、存条数、拒仿写、追踪词进文案，跑完自动还原
 *   php think run-mode:selfcheck --persona=239 --generate   再追加一次真实出片（会扣算力、产生任务，仅测试环境用）
 *
 * 退出码 0 全通过，1 有失败项。
 */
class RunModeSelfCheck extends Command
{
    /** 出片自检用的条数，够验证轮转又不至于烧太多算力 */
    private const GENERATE_COUNT = 1;

    /** 自检期间改过的人设与配置，用于在任何退出路径上还原 */
    private ?array $pendingRestore = null;
    private int $pass = 0;
    private int $fail = 0;
    private Output $out;

    protected function configure()
    {
        $this->setName('run-mode:selfcheck')
            ->setDescription('运行模式自检：无手机模式链路是否正常、手机模式是否零回归')
            ->addOption('persona', null, Option::VALUE_OPTIONAL, '人设 id，追加单人设读写检查（跑完自动还原）')
            ->addOption('generate', null, Option::VALUE_NONE, '追加一次真实出片，会扣算力，仅测试环境用');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->out = $output;
        $personaId = (int)$input->getOption('persona');
        $generate = (bool)$input->getOption('generate');

        if ($generate && $personaId <= 0) {
            $output->error('--generate 必须配合 --persona 使用');
            return 1;
        }

        $this->section('一、数据库结构');
        $this->checkSchema();

        $this->section('二、存量数据默认值');
        $this->checkExistingData();

        $this->section('三、常量与归一');
        $this->checkConstants();

        $this->section('四、虚拟设备');
        $this->checkCloudDevice();

        $this->section('五、按条数造槽位');
        $this->checkSlots();

        $this->section('六、手机模式零回归');
        $this->checkPhoneModeUntouched();

        if ($personaId > 0) {
            $this->section('七、单人设读写（跑完还原）');
            $this->checkPersona($personaId, $generate);
        }

        $output->writeln('');
        $output->writeln("==== 通过 {$this->pass} 项，失败 {$this->fail} 项 ====");
        return $this->fail > 0 ? 1 : 0;
    }

    /**
     * 自检会临时把人设切成无手机模式，必须保证任何退出路径都还原。
     * finally 挡不住 Ctrl+C（SIGINT 默认直接终止），所以再加信号与 shutdown 兜底。
     */
    private function guardRestore(): void
    {
        register_shutdown_function(function () {
            $this->restorePersona();
        });
        if (!function_exists('pcntl_signal') || !function_exists('pcntl_async_signals')) {
            return;
        }
        pcntl_async_signals(true);
        foreach ([SIGINT, SIGTERM] as $signal) {
            pcntl_signal($signal, function () {
                $this->restorePersona();
                exit(130);
            });
        }
    }

    /** 把人设与配置还原到自检前；可重复调用 */
    private function restorePersona(): void
    {
        if ($this->pendingRestore === null) {
            return;
        }
        $backup = $this->pendingRestore;
        $this->pendingRestore = null;
        $personaId = (int)$backup['persona_id'];
        try {
            AiPersona::where('id', $personaId)->update([
                'run_mode' => $backup['run_mode'],
                'cloud_synthesis_date' => $backup['cloud_synthesis_date'],
                'cloud_retry_count' => $backup['cloud_retry_count'],
                'cloud_retry_date' => $backup['cloud_retry_date'],
            ]);
            SynthesisConfig::where('persona_id', $personaId)->update([
                'copywriting_source' => $backup['copywriting_source'],
                'daily_video_count' => $backup['daily_video_count'],
            ]);
            Cache::store('material_redis')->delete('command_video_synthesis_' . CloudSvDevice::codeFor($personaId));
            $this->out->writeln('  已还原人设到自检前状态');
        } catch (\Throwable $e) {
            // 退出途中输出流可能已关闭，还原本身成功即可
        }
    }

    private function section(string $title): void
    {
        $this->out->writeln('');
        $this->out->writeln("==== {$title} ====");
    }

    private function chk(string $name, bool $ok, string $detail = ''): void
    {
        if ($ok) {
            $this->pass++;
            $this->out->writeln("  [PASS] {$name}");
            return;
        }
        $this->fail++;
        $this->out->writeln("  [FAIL] {$name}" . ($detail !== '' ? "  {$detail}" : ''));
    }

    private function checkSchema(): void
    {
        $personaTable = Db::name('ai_persona')->getTable();
        $configTable = Db::name('ai_persona_synthesis_config')->getTable();
        foreach (['run_mode', 'cloud_synthesis_date', 'cloud_retry_count', 'cloud_retry_date'] as $col) {
            $this->chk("人设表存在 {$col}", $this->hasColumn($personaTable, $col), '升级 SQL 未执行');
        }
        $this->chk('合成配置表存在 daily_video_count', $this->hasColumn($configTable, 'daily_video_count'), '升级 SQL 未执行');
        $this->chk('人设表存在索引 idx_run_mode_cloud_date', $this->hasIndex($personaTable, 'idx_run_mode_cloud_date'));
    }

    private function hasColumn(string $table, string $column): bool
    {
        return (int)Db::query(
            'SELECT COUNT(1) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',
            [$table, $column]
        )[0]['c'] > 0;
    }

    private function hasIndex(string $table, string $index): bool
    {
        return (int)Db::query(
            'SELECT COUNT(1) AS c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?',
            [$table, $index]
        )[0]['c'] > 0;
    }

    private function checkExistingData(): void
    {
        $bad = AiPersona::whereNotIn('run_mode', [AiPersona::RUN_MODE_PHONE, AiPersona::RUN_MODE_CLOUD])->count();
        $this->chk('没有非法的 run_mode 取值', $bad === 0, "非法={$bad}");
        $cloud = AiPersona::where('run_mode', AiPersona::RUN_MODE_CLOUD)->count();
        $this->out->writeln("  当前无手机模式人设数：{$cloud}");
        $badCount = SynthesisConfig::where('daily_video_count', '>', SynthesisConfig::DAILY_VIDEO_COUNT_MAX)->count();
        $this->chk('没有越界的每日出片条数', $badCount === 0, "越界={$badCount}");
    }

    private function checkConstants(): void
    {
        $this->chk('手机模式常量=1', AiPersona::RUN_MODE_PHONE === 1);
        $this->chk('无手机模式常量=2', AiPersona::RUN_MODE_CLOUD === 2);
        $this->chk('脏值归一为手机模式', AiPersona::normalizeRunMode('x') === 1 && AiPersona::normalizeRunMode(null) === 1 && AiPersona::normalizeRunMode(9) === 1);
        $invalidRejected = false;
        try {
            AiPersona::requireValidRunMode(3);
        } catch (\Throwable $e) {
            $invalidRejected = str_contains($e->getMessage(), '运行模式');
        }
        $this->chk('非法运行模式入参被拒', $invalidRejected);
        $this->chk('条数越界回落未配置', SynthesisConfig::normalizeDailyVideoCount(999) === SynthesisConfig::DAILY_VIDEO_COUNT_UNSET);
        $this->chk(
            '同表两个模型的条数常量一致',
            SynthesisConfig::DAILY_VIDEO_COUNT_MAX === \app\common\model\aiPersona\AiPersonaSynthesisConfig::DAILY_VIDEO_COUNT_MAX
        );
    }

    private function checkCloudDevice(): void
    {
        $this->chk('云端设备号拼接正确', CloudSvDevice::codeFor(1) === 'cloud:1');
        $this->chk('云端设备号识别正确', CloudSvDevice::isCloudCode('cloud:1') && !CloudSvDevice::isCloudCode('SV0001'));
        $this->chk('从云端设备号解析人设', CloudSvDevice::personaIdFromCode('cloud:88') === 88);
        $this->chk('设备表里不存在云端设备号', SvDevice::where('device_code', 'like', CloudSvDevice::CODE_PREFIX . '%')->count() === 0);

        $realCode = SvDevice::where('persona_id', '>', 0)->value('device_code');
        if ($realCode) {
            $resolved = CloudSvDevice::resolve($realCode);
            $this->chk('真设备号仍走设备表', !$resolved->isEmpty() && (string)$resolved->device_code === (string)$realCode);
        }
        $phonePersona = AiPersona::where('run_mode', AiPersona::RUN_MODE_PHONE)->value('id');
        if ($phonePersona) {
            $this->chk(
                '手机模式人设拿不到虚拟设备',
                CloudSvDevice::resolve(CloudSvDevice::codeFor((int)$phonePersona))->isEmpty()
            );
        }
    }

    private function checkSlots(): void
    {
        $m = new \ReflectionMethod(CopywritingAiGenerationLogic::class, 'buildCloudPublishTimeSlots');
        $m->setAccessible(true);
        foreach ([1, 2, 3, 5, SynthesisConfig::DAILY_VIDEO_COUNT_MAX] as $n) {
            $slots = $m->invoke(null, $n);
            $this->chk(
                "条数 {$n} 生成等量且互不相同的时段",
                count($slots) === $n && count(array_unique($slots)) === $n,
                json_encode($slots)
            );
        }
    }

    private function checkPhoneModeUntouched(): void
    {
        $base = $this->deviceQuery()->count();
        $withFilter = $this->deviceQuery()->where('p.run_mode', AiPersona::RUN_MODE_PHONE)->count();
        $cloudDevices = $this->deviceQuery()->where('p.run_mode', AiPersona::RUN_MODE_CLOUD)->count();
        $this->chk(
            '设备维度只少掉无手机模式的那些设备',
            $base - $withFilter === $cloudDevices,
            "全部={$base} 手机={$withFilter} 云端={$cloudDevices}"
        );
        $this->out->writeln("  手机模式待合成设备数：{$withFilter}");
    }

    private function deviceQuery()
    {
        return SvDevice::alias('d')
            ->join('ai_persona p', 'd.persona_id = p.id')
            ->where('d.auto_type', 1)
            ->where('p.status', 1)
            ->where('p.publish_mode', 1)
            ->where('d.persona_id', '>', 0);
    }

    private function checkPersona(int $personaId, bool $generate): void
    {
        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            $this->chk("人设 {$personaId} 存在", false);
            return;
        }
        $config = SynthesisConfig::where('persona_id', $personaId)->findOrEmpty();
        if ($config->isEmpty()) {
            $this->chk("人设 {$personaId} 有合成配置", false, '先在小程序打开一次视频剪辑配置');
            return;
        }

        $this->out->writeln("  人设：{$persona->persona_name}（用户 {$persona->user_id}）");
        $backup = [
            'run_mode' => (int)$persona->run_mode,
            'cloud_synthesis_date' => $persona->cloud_synthesis_date,
            'cloud_retry_count' => (int)$persona->cloud_retry_count,
            'cloud_retry_date' => $persona->cloud_retry_date,
            'copywriting_source' => (int)$config->copywriting_source,
            'daily_video_count' => (int)$config->daily_video_count,
        ];

        $this->pendingRestore = ['persona_id' => $personaId] + $backup;
        $this->guardRestore();

        try {
            SynthesisConfigLogic::$uid = (int)$persona->user_id;

            SynthesisConfigLogic::getByPersonaId(['persona_id' => $personaId]);
            $detail = SynthesisConfigLogic::getReturnData();
            $this->chk('配置详情直出 run_mode', array_key_exists('run_mode', $detail));
            $this->chk('配置详情直出 daily_video_count', array_key_exists('daily_video_count', $detail));

            $saved = SynthesisConfigLogic::update([
                'id' => $config->id,
                'run_mode' => AiPersona::RUN_MODE_CLOUD,
                'daily_video_count' => self::GENERATE_COUNT,
            ]);
            $this->chk('保存无手机模式与条数', $saved, SynthesisConfigLogic::getError());
            $this->chk('运行模式落到人设表', (int)AiPersona::where('id', $personaId)->value('run_mode') === AiPersona::RUN_MODE_CLOUD);
            $this->chk('条数落到配置表', (int)SynthesisConfig::where('persona_id', $personaId)->value('daily_video_count') === self::GENERATE_COUNT);

            $rejected = !SynthesisConfigLogic::update([
                'id' => $config->id,
                'copywriting_source' => SynthesisConfig::COPYWRITING_SOURCE_IMITATE,
            ]);
            $this->chk('无手机模式拒绝爆款仿写', $rejected, '竟然保存成功了');

            $default = SynthesisConfigLogic::buildDefaultConfig($personaId, (int)$persona->user_id, (int)$persona->persona_type, 1);
            $this->chk(
                '默认配置对无手机人设不给仿写',
                (int)$default['copywriting_source'] !== SynthesisConfig::COPYWRITING_SOURCE_IMITATE,
                '实际=' . $default['copywriting_source']
            );

            $this->checkPrompt($persona);

            if ($generate) {
                $this->checkGenerate($personaId);
            }
        } finally {
            $this->restorePersona();
        }
    }

    /** 追踪词要能进 AI 文案 prompt，且没配词时一个字都不多加 */
    private function checkPrompt(AiPersona $persona): void
    {
        $ruleModel = [
            1 => AiPersonaIndividual::class,
            2 => AiPersonaEnterprise::class,
            3 => AiPersonaLocal::class,
        ][(int)$persona->persona_type] ?? null;
        if ($ruleModel === null) {
            return;
        }
        $rule = $ruleModel::where('persona_id', $persona->id)->findOrEmpty();
        if ($rule->isEmpty()) {
            return;
        }

        $m = new \ReflectionMethod(CopywritingImitationLogic::class, 'buildPersonaCopywritingKeywordsText');
        $m->setAccessible(true);
        $marker = '我近期重点关注的选题方向是';

        $words = $rule->hot_words ?: [];
        $withWords = (string)$m->invoke(null, $persona, $rule, (int)$persona->persona_type);
        if (!empty($words)) {
            $this->chk('追踪词进了文案 prompt', str_contains($withWords, $marker));
            $this->chk('prompt 含具体追踪词', str_contains($withWords, (string)$words[0]));
        } else {
            $this->out->writeln('  该人设未配追踪词，跳过"词进 prompt"检查');
        }

        $empty = clone $rule;
        $empty->hot_words = [];
        $withoutWords = (string)$m->invoke(null, $persona, $empty, (int)$persona->persona_type);
        $this->chk('没配追踪词时 prompt 不追加任何内容', !str_contains($withoutWords, $marker));
    }

    /** 真实跑一次无手机模式出片，会扣算力 */
    private function checkGenerate(int $personaId): void
    {
        $code = CloudSvDevice::codeFor($personaId);
        Cache::store('material_redis')->delete('command_video_synthesis_' . $code);
        AiPersona::where('id', $personaId)->update(['cloud_synthesis_date' => null]);

        $before = ShanjianVideoTask::where('device_code', $code)->count();
        $this->out->writeln('  正在跑一次真实出片，可能要一两分钟…');
        try {
            (new AutoVideoSynthesis())->handlePersonaLevel();
        } catch (\Throwable $e) {
            $this->chk('出片过程无异常', false, $e->getMessage());
            return;
        }

        $tasks = ShanjianVideoTask::where('device_code', $code)->order('id', 'desc')->select();
        $this->chk('建出了云端合成任务', count($tasks) > $before, '任务数 ' . $before . ' → ' . count($tasks));
        $task = $tasks[0] ?? null;
        if ($task) {
            $this->out->writeln("  任务 #{$task->id} 状态={$task->status} 类型={$task->shanjian_type}");
            $this->chk('任务挂在云端设备号下', (string)$task->device_code === $code);
            $this->chk('任务是自动任务且非朋友圈', (int)$task->auto_type === 1 && (int)$task->wechat_type === 0);
            $this->chk('任务带了口播文案', trim((string)$task->msg) !== '');
        }
        $persona = AiPersona::where('id', $personaId)->find();
        $this->chk(
            '重试计数写到人设表',
            (int)$persona->cloud_retry_count >= 1 && (string)$persona->cloud_retry_date === date('Y-m-d')
        );
        $this->out->writeln('  提示：这条任务会继续走合成，稍后可在人设「内容记录」里看到成片');
    }
}
