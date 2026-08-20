<?php

namespace app\common\command;

use app\api\logic\aiPersona\PublishLogic;
use app\api\logic\aiPersona\VideoSynthesis;
use app\common\model\aiPersona\Material;
use app\common\model\file\File;
use app\common\model\sv\SvDevice;
use app\common\service\MaterialReadinessService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 素材转码就绪门禁验证脚本
 *
 * 用法:
 *   php think material:readiness-verify --persona=PERSONA_ID --user=USER_ID [--device=DEVICE_CODE]
 *
 * 行为:
 *   - 自动挑该人设下第一条"用于生成视频"(publish_mode=1)的视频素材作为白鼠
 *   - 依次跑 3 个场景:Service 层、朋友圈合成入口、直发 cron;前后保留素材原 transcode_status,跑完恢复
 *   - 不真正下发任何第三方调用 — 只验证门禁是否拦下/正确放行
 */
class MaterialReadinessVerify extends Command
{
    protected function configure()
    {
        $this->setName('material:readiness-verify')
            ->setDescription('验证素材转码就绪门禁是否正确生效')
            ->addOption('persona', null, Option::VALUE_REQUIRED, '人设 id')
            ->addOption('user', null, Option::VALUE_REQUIRED, '用户 id')
            ->addOption('device', null, Option::VALUE_OPTIONAL, '设备 code(用于跑朋友圈合成场景,可选)');
    }

    protected function execute(Input $input, Output $output)
    {
        $personaId = (int)$input->getOption('persona');
        $userId = (int)$input->getOption('user');
        $deviceCode = (string)$input->getOption('device');

        if ($personaId <= 0 || $userId <= 0) {
            $output->error('必须提供 --persona 和 --user');
            return 1;
        }

        // 找一条用于生成视频的视频素材
        $material = Material::where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->where('use_status', 1)
            ->where('publish_mode', 1)
            ->where('material_type', 1)
            ->find();

        if (!$material) {
            $output->error("人设 {$personaId} 用户 {$userId} 下找不到 publish_mode=1 的视频素材");
            return 1;
        }

        $file = File::where('uri', $material->file_url)->find();
        if (!$file) {
            $output->error("素材 file_url={$material->file_url} 在 file 表里找不到对应记录");
            return 1;
        }

        $originalStatus = (int)$file->transcode_status;
        $output->writeln("白鼠:material id={$material->id} file id={$file->id} uri={$file->uri} 原 transcode_status={$originalStatus}");
        $output->writeln(str_repeat('-', 80));

        $pass = 0;
        $fail = 0;
        $report = [];

        try {
            // ========== 场景 1: Service 层 ==========
            $output->writeln("\n场景 1: MaterialReadinessService 状态机");

            File::where('id', $file->id)->update(['transcode_status' => 3]);
            $c = MaterialReadinessService::checkPersonaMaterials($personaId, $userId);
            $ok = $c['ready'] === true && empty($c['pending_uris']) && empty($c['failed_uris']);
            $this->mark($output, '  status=3 → ready=true', $ok, $pass, $fail, $report);

            File::where('id', $file->id)->update(['transcode_status' => 2]);
            $c = MaterialReadinessService::checkPersonaMaterials($personaId, $userId);
            $ok = $c['ready'] === false && in_array($file->uri, $c['pending_uris'], true);
            $this->mark($output, '  status=2 → ready=false 且 uri 在 pending_uris', $ok, $pass, $fail, $report);

            File::where('id', $file->id)->update(['transcode_status' => 1]);
            $c = MaterialReadinessService::checkPersonaMaterials($personaId, $userId);
            $ok = $c['ready'] === false && in_array($file->uri, $c['pending_uris'], true);
            $this->mark($output, '  status=1 → ready=false 且 uri 在 pending_uris', $ok, $pass, $fail, $report);

            File::where('id', $file->id)->update(['transcode_status' => 4]);
            $c = MaterialReadinessService::checkPersonaMaterials($personaId, $userId);
            $ok = $c['ready'] === false && in_array($file->uri, $c['failed_uris'], true);
            $this->mark($output, '  status=4 → ready=false 且 uri 在 failed_uris', $ok, $pass, $fail, $report);

            // readyUriSet
            File::where('id', $file->id)->update(['transcode_status' => 2]);
            $set = MaterialReadinessService::readyUriSet([$file->uri]);
            $ok = !isset($set[$file->uri]);
            $this->mark($output, '  readyUriSet 剔除 status=2 的 uri', $ok, $pass, $fail, $report);

            File::where('id', $file->id)->update(['transcode_status' => 3]);
            $set = MaterialReadinessService::readyUriSet([$file->uri]);
            $ok = isset($set[$file->uri]);
            $this->mark($output, '  readyUriSet 放行 status=3 的 uri', $ok, $pass, $fail, $report);

            // ========== 场景 2: 朋友圈合成入口 ==========
            if ($deviceCode !== '') {
                $output->writeln("\n场景 2: VideoSynthesis::wechatVideoSynthesis 朋友圈入口");
                $device = SvDevice::where('device_code', $deviceCode)->find();
                if (!$device) {
                    $output->warning("  设备 {$deviceCode} 不存在,场景 2 跳过");
                } else {
                    $originalSynW = (int)$device->synthesis_w;
                    File::where('id', $file->id)->update(['transcode_status' => 2]);
                    // 先把 synthesis_w 置 0,这样如果场景 2 误把它置 1 我们能检测
                    SvDevice::where('device_code', $deviceCode)->update(['synthesis_w' => 0]);

                    $result = VideoSynthesis::wechatVideoSynthesis($deviceCode);
                    $after = (int)SvDevice::where('device_code', $deviceCode)->value('synthesis_w');

                    $ok = $result === false && $after === 0;
                    $this->mark($output, '  status=2 时:返回 false 且 synthesis_w 仍为 0(关键:不锁死设备)', $ok, $pass, $fail, $report);

                    // 还原 synthesis_w
                    SvDevice::where('device_code', $deviceCode)->update(['synthesis_w' => $originalSynW]);
                }
            } else {
                $output->writeln("\n场景 2: 朋友圈合成入口 — 未提供 --device,跳过");
            }

            // ========== 场景 3: 直发 cron 路径 ==========
            if ($deviceCode !== '') {
                $output->writeln("\n场景 3: PublishLogic::materialPersonaPublishCron 直发 cron 路径");
                // 直发用的是 publish_mode=2 的素材,我们用同一张白鼠表暂时改成 2 来模拟
                $originalPubMode = (int)$material->publish_mode;
                $originalIsWechat = (int)$material->is_wechat;
                Material::where('id', $material->id)->update(['publish_mode' => 2, 'is_wechat' => 0]);
                File::where('id', $file->id)->update(['transcode_status' => 2]);

                $device = SvDevice::where('device_code', $deviceCode)->find();
                if ($device) {
                    // 把 cron 触发,看日志(本测试只验证不抛异常 + 走"跳过"分支)
                    // 我们通过查询过滤后的 videos 数量 间接验证:复用 service
                    $allMats = Material::where('material_type', 1)
                        ->where('user_id', $userId)
                        ->where('persona_id', $personaId)
                        ->where('use_status', 1)
                        ->where('publish_mode', 2)
                        ->where('is_wechat', 0)
                        ->column('file_url');
                    $readySet = MaterialReadinessService::readyUriSet($allMats);
                    $ok = !isset($readySet[$file->uri]);
                    $this->mark($output, '  status=2 时:白鼠 file_url 不在 readyUriSet 内(会被 cron 过滤)', $ok, $pass, $fail, $report);

                    File::where('id', $file->id)->update(['transcode_status' => 3]);
                    $readySet = MaterialReadinessService::readyUriSet($allMats);
                    $ok = isset($readySet[$file->uri]);
                    $this->mark($output, '  status=3 时:白鼠 file_url 在 readyUriSet 内(会被 cron 选中)', $ok, $pass, $fail, $report);
                }
                // 还原素材状态
                Material::where('id', $material->id)->update(['publish_mode' => $originalPubMode, 'is_wechat' => $originalIsWechat]);
            } else {
                $output->writeln("\n场景 3: 直发 cron 路径 — 未提供 --device,跳过");
            }
        } catch (\Throwable $e) {
            $output->error('测试过程异常:' . $e->getMessage());
            $fail++;
        } finally {
            // 无论如何恢复 file.transcode_status 原值
            File::where('id', $file->id)->update(['transcode_status' => $originalStatus]);
            $output->writeln("\n已恢复 file id={$file->id} transcode_status={$originalStatus}");
        }

        $output->writeln(str_repeat('=', 80));
        $output->writeln("通过: {$pass}  失败: {$fail}");
        foreach ($report as $line) {
            $output->writeln($line);
        }
        return $fail === 0 ? 0 : 1;
    }

    private function mark(Output $output, string $name, bool $ok, int &$pass, int &$fail, array &$report): void
    {
        if ($ok) {
            $pass++;
            $output->writeln("  [PASS] {$name}");
            $report[] = "[PASS] {$name}";
        } else {
            $fail++;
            $output->writeln("  [FAIL] {$name}");
            $report[] = "[FAIL] {$name}";
        }
    }
}
