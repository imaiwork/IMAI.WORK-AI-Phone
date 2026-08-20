<?php

namespace app\common\command;

use app\common\model\member\MemberUser;
use app\common\service\MemberService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class MemberExpireCheckCron extends Command
{
    protected function configure()
    {
        $this->setName('member_expire_check')
            ->setDescription('会员到期检查 + 软降级冻结超出实体');
    }

    protected function execute(Input $input, Output $output)
    {
        $now = time();
        $processed = 0;
        $errors = 0;

        MemberUser::where('status', MemberUser::STATUS_ACTIVE)
            ->where('end_time', '>', 0)
            ->where('end_time', '<', $now)
            ->chunk(100, function ($rows) use ($now, &$processed, &$errors) {
                foreach ($rows as $m) {
                    try {
                        MemberService::expireAndFreeze($m);
                        $processed++;
                        Log::channel('member')->write('会员到期降级:' . json_encode([
                            'user_id'   => $m->user_id,
                            'member_id' => $m->id,
                            'end_time'  => date('Y-m-d H:i:s', $m->end_time),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::channel('member')->write('会员到期降级失败:' . json_encode([
                            'user_id'   => $m->user_id,
                            'member_id' => $m->id,
                            'error'     => $e->getMessage(),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    }
                }
            });

        $msg = "会员到期检查完成，处理: {$processed}，失败: {$errors}";
        Log::channel('member')->write($msg);
        print_r("\n {$msg}\n");
        return 0;
    }
}
