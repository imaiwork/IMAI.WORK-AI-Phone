<?php

namespace app\common\command;

use app\common\model\member\MemberUser;
use app\common\service\MemberService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class MemberDailyGrantCron extends Command
{
    protected function configure()
    {
        $this->setName('member_daily_grant')
            ->setDescription('会员周期算力发放');
    }

    protected function execute(Input $input, Output $output)
    {
        $now = time();
        MemberUser::where('status', MemberUser::STATUS_ACTIVE)
            ->where(function ($q) use ($now) {
                $q->whereOr([['end_time', '=', 0], ['end_time', '>', $now]]);
            })
            ->chunk(200, function ($rows) {
                foreach ($rows as $m) {
                    try {
                        MemberService::grantTokensIfDue($m);
                    } catch (\Throwable $e) {
                        Log::channel('member')->error(
                            '会员周期赠送失败 user_id=' . $m->user_id . ': ' . $e->getMessage()
                        );
                    }
                }
            });
        return 0;
    }
}
