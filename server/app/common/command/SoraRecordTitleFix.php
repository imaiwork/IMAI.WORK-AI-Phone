<?php

namespace app\common\command;

use app\common\service\copywriting\VideoPromptService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 一句话生成视频 · 存量记录标题回填。
 *
 * 旧口径把记录名取成 mb_substr(提示词, 0, 10)，而提示词首行是「7秒竖屏9:16，写实风格…」，
 * 于是创作记录里全是「7秒竖屏9:16，写」这类没有主题的名字（真实工单）。
 * 新提交的记录已在 SoraVideoSettingLogic::resolveTitle() 里修好，这里只补历史行。
 *
 * 识别口径是可精确复算的，不靠猜：**name 恰好等于旧公式 mb_substr(msg, 0, 10)**
 * （多任务时还会带 _2 / _3 后缀）。用户自己改过名字的行永远不满足这个条件，不会被覆盖。
 * 默认只预览，追加 --execute 才写库；重复执行不会二次改写。
 */
class SoraRecordTitleFix extends Command
{
    /** 每批读取行数 */
    private const BATCH = 500;

    /** 输出明细条数上限 */
    private const SAMPLE_LIMIT = 20;

    protected function configure()
    {
        $this->setName('sora:record-title-fix')
            ->setDescription('回填一句话生成视频的历史记录标题（旧口径为提示词前 10 字，默认仅预览，--execute 执行）')
            ->addOption('execute', null, Option::VALUE_NONE, '实际写库（默认仅预览统计）')
            ->addOption('limit', null, Option::VALUE_OPTIONAL, '最多处理的行数', 50000);
    }

    protected function execute(Input $input, Output $output)
    {
        $execute = (bool)$input->getOption('execute');
        $limit   = max(1, (int)$input->getOption('limit') ?: 50000);

        $summary = [
            'execute'        => $execute,
            'setting_scanned' => 0,
            'setting_fixed'   => 0,
            'task_scanned'    => 0,
            'task_fixed'      => 0,
            'skipped'         => 0,
            'samples'         => [],
        ];

        try {
            // 先处理任务行：创作记录列表(type=6)读的就是 sora_video_task.name
            $summary = $this->fixTable('sora_video_task', 'msg', $limit, $execute, $summary);
            // 设置行一并修：名称/重试弹窗等处直接读 sora_video_setting.name
            $summary = $this->fixTable('sora_video_setting', 'copywriting', $limit, $execute, $summary);
        } catch (\Throwable $e) {
            $output->writeln(json_encode([
                'code'  => 0,
                'msg'   => '回填异常：' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
            return 1;
        }

        $summary['code'] = 1;
        $summary['msg']  = $execute ? '回填完成' : '预览完成，确认无误后加 --execute 写库';
        $output->writeln(json_encode($summary, JSON_UNESCAPED_UNICODE));
        return 0;
    }

    /**
     * @param string $table    表名（不含前缀）
     * @param string $content  保存提示词正文的列，用来复算旧标题
     */
    private function fixTable(string $table, string $content, int $limit, bool $execute, array $summary): array
    {
        $scannedKey = $table === 'sora_video_task' ? 'task_scanned' : 'setting_scanned';
        $fixedKey   = $table === 'sora_video_task' ? 'task_fixed' : 'setting_fixed';

        $lastId = 0;
        $done   = 0;
        while ($done < $limit) {
            $rows = Db::name($table)
                ->field('id,name,' . $content . ' as content')
                ->where('id', '>', $lastId)
                ->where('delete_time', null)
                ->where('name', '<>', '')
                ->where($content, '<>', '')
                ->order('id', 'asc')
                ->limit(self::BATCH)
                ->select()
                ->toArray();
            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = (int)$row['id'];
                $done++;
                $summary[$scannedKey]++;

                $name = (string)$row['name'];
                $old  = mb_substr((string)$row['content'], 0, 10);
                // 旧名字 = 提示词前 10 字，多任务时带 _2/_3 后缀
                if ($name !== $old && !preg_match('/^' . preg_quote($old, '/') . '_\d+$/u', $name)) {
                    $summary['skipped']++;
                    continue;
                }

                $new = VideoPromptService::fallbackTitle((string)$row['content']);
                if ($new === '') {
                    $summary['skipped']++;
                    continue;
                }
                // 保留多任务后缀
                if (preg_match('/_(\d+)$/u', $name, $m)) {
                    $new .= '_' . $m[1];
                }
                if ($new === $name) {
                    $summary['skipped']++;
                    continue;
                }

                if (count($summary['samples']) < self::SAMPLE_LIMIT) {
                    $summary['samples'][] = [
                        'table' => $table,
                        'id'    => (int)$row['id'],
                        'from'  => $name,
                        'to'    => $new,
                    ];
                }

                if ($execute) {
                    Db::name($table)->where('id', (int)$row['id'])->update([
                        'name'        => $new,
                        'update_time' => time(),
                    ]);
                }
                $summary[$fixedKey]++;
            }

            if (count($rows) < self::BATCH) {
                break;
            }
        }

        return $summary;
    }
}
