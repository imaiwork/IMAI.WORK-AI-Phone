<?php

namespace app\common\command;

use app\common\model\team\Team;
use app\common\model\team\TeamMember;
use app\common\model\user\User;
use app\common\service\TeamBillingService;
use app\common\service\TeamContextService;
use app\common\service\TeamWalletRefundRegistry;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 团队OEM 计费/退费/隔离 自检脚本(仅人工验证,不进 cron)
 *
 * 用法:
 *   php think team:oem-selfcheck --member=成员用户ID [--guest=散客用户ID]
 *
 * 行为:
 *   - 以传入「成员」推导其所属企业与团队长,在【嵌套事务内】模拟扣费/退费并断言,跑完【整体回滚】,不改动任何数据。
 *   - 覆盖:可用算力口径 / 钱包优先扣 / 回落团队长 / 余额不足拦截 / 退费对称回退 / 散客退回自己。
 *   - 校验 P2「成员消费=消耗团队长算力」与退费权威归属;P3/P4/P5 见《团队OEM实现说明.md》人工清单。
 */
class TeamOemSelfCheck extends Command
{
    private int $pass = 0;
    private int $fail = 0;
    private Output $out;

    protected function configure()
    {
        $this->setName('team:oem-selfcheck')
            ->setDescription('团队OEM 计费/退费自检(事务内模拟+回滚,不改数据)')
            ->addOption('member', null, Option::VALUE_REQUIRED, '团队成员用户ID(team_role=1/3)')
            ->addOption('guest', null, Option::VALUE_OPTIONAL, '散客用户ID(team_role=0,验证退回自己)');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->out = $output;
        $memberId = (int)$input->getOption('member');
        $guestId  = (int)$input->getOption('guest');
        if ($memberId <= 0) {
            $output->error('必须提供 --member=成员用户ID');
            return 1;
        }

        $member = User::where('id', $memberId)->field('id,tokens,team_id,team_role')->findOrEmpty();
        if ($member->isEmpty()) {
            $output->error("成员用户 {$memberId} 不存在");
            return 1;
        }
        $teamId = (int)$member->team_id;
        $role   = (int)$member->team_role;
        if ($teamId <= 0 || !in_array($role, [1, 3], true)) {
            $output->error("用户 {$memberId} 当前不是企业空间内的团队成员/管理员(team_id={$teamId}, team_role={$role})");
            return 1;
        }
        $team = Team::where('id', $teamId)->findOrEmpty();
        if ($team->isEmpty()) {
            $output->error("团队 {$teamId} 不存在");
            return 1;
        }
        $ownerId    = (int)$team->owner_id;
        $ownerTok0  = (float)(User::where('id', $ownerId)->value('tokens') ?? 0);
        $memberTok0 = (float)$member->tokens;
        $wallet0    = (float)(TeamMember::where('team_id', $teamId)->where('user_id', $memberId)->value('team_tokens') ?? 0);

        $output->writeln(str_repeat('=', 78));
        $output->writeln("团队OEM 自检  成员={$memberId} 团队={$teamId} 团队长={$ownerId}");
        $output->writeln("初始: 成员个人算力={$memberTok0}  团队长算力={$ownerTok0}  成员企业钱包={$wallet0}");
        $output->writeln(str_repeat('=', 78));

        // ---- 用例1:可用算力口径 = 企业钱包 + 团队长个人算力 ----
        $spendable = TeamBillingService::spendableTokens($memberId);
        $this->assertEq((string)($wallet0 + $ownerTok0), (string)$spendable,
            '可用算力 = 企业钱包+团队长个人算力');

        // ---- 用例2:钱包充足时,只扣钱包,团队长与成员个人不动 ----
        if ($wallet0 >= 1) {
            $this->inTx(function () use ($memberId, $teamId, $ownerId, $memberTok0, $ownerTok0, $wallet0) {
                $amt = min(1.0, $wallet0);
                TeamBillingService::deduct($memberId, $amt);
                TeamWalletRefundRegistry::confirm($memberId, $amt); // 清理登记,避免污染后续用例
                $this->assertEq((string)($wallet0 - $amt), $this->wallet($teamId, $memberId), '钱包充足:企业钱包减少');
                $this->assertEq((string)$ownerTok0, $this->tok($ownerId), '钱包充足:团队长算力不变');
                $this->assertEq((string)$memberTok0, $this->tok($memberId), '钱包充足:成员个人算力不变');
            });
        } else {
            $this->skip('用例2(钱包充足只扣钱包):成员无企业钱包,跳过');
        }

        // ---- 用例3:钱包不足,超出部分回落扣团队长个人算力,成员个人不动 ----
        if ($ownerTok0 >= 1) {
            $this->inTx(function () use ($memberId, $teamId, $ownerId, $memberTok0, $ownerTok0, $wallet0) {
                $amt = $wallet0 + 1.0; // 超钱包 1
                TeamBillingService::deduct($memberId, $amt);
                TeamWalletRefundRegistry::confirm($memberId, $amt);
                $this->assertEq('0.00', $this->wallet($teamId, $memberId), '钱包不足:企业钱包清零');
                $this->assertEq((string)($ownerTok0 - 1.0), $this->tok($ownerId), '钱包不足:团队长个人算力回落扣1');
                $this->assertEq((string)$memberTok0, $this->tok($memberId), '钱包不足:成员个人算力不变');
            });
        } else {
            $this->skip('用例3(回落扣团队长):团队长算力不足,跳过');
        }

        // ---- 用例4:钱包+团队长都不够,拦截抛异常,不透支 ----
        $this->inTx(function () use ($memberId, $wallet0, $ownerTok0) {
            $tooMuch = $wallet0 + $ownerTok0 + 100.0;
            $threw = false;
            try {
                TeamBillingService::deduct($memberId, $tooMuch);
            } catch (\Throwable $e) {
                $threw = true;
            }
            TeamWalletRefundRegistry::confirm($memberId, $tooMuch);
            $this->assertTrue($threw, '余额不足:超出钱包+团队长时抛异常拦截');
        });

        // ---- 用例5:退费对称 —— 扣(钱包+团队长)后按原企业退回,三方精确复原 ----
        if ($ownerTok0 >= 1) {
            $this->inTx(function () use ($memberId, $teamId, $ownerId, $memberTok0, $ownerTok0, $wallet0) {
                $amt = $wallet0 + 1.0;
                TeamBillingService::deduct($memberId, $amt);           // 扣:钱包清零 + 团队长-1,登记钱包份额
                TeamBillingService::refundToTeam($memberId, $amt, $teamId); // 退:钱包份额回钱包 + 其余回团队长
                $this->assertEq((string)$wallet0, $this->wallet($teamId, $memberId), '退费对称:企业钱包精确复原');
                $this->assertEq((string)$ownerTok0, $this->tok($ownerId), '退费对称:团队长算力精确复原');
                $this->assertEq((string)$memberTok0, $this->tok($memberId), '退费对称:成员个人算力全程不变');
            });
        } else {
            $this->skip('用例5(退费对称):团队长算力不足,跳过');
        }

        // ---- 用例6:异步/无登记场景,成员退费全额回团队长(权威按企业归属),成员不白拿 ----
        $this->inTx(function () use ($memberId, $teamId, $ownerId, $memberTok0, $ownerTok0) {
            TeamWalletRefundRegistry::confirm($memberId, 999999); // 确保无登记(模拟跨进程)
            TeamBillingService::refundToTeam($memberId, 5.0, $teamId);
            $this->assertEq((string)($ownerTok0 + 5.0), $this->tok($ownerId), '无登记退费:全额回团队长');
            $this->assertEq((string)$memberTok0, $this->tok($memberId), '无登记退费:成员个人算力不变(不白拿)');
        });

        // ---- 用例7:散客退回自己(散客无成员关系,消费的是自己算力) ----
        if ($guestId > 0) {
            $guest = User::where('id', $guestId)->field('id,tokens,team_id,team_role')->findOrEmpty();
            $hasMembership = !TeamMember::where('team_id', $teamId)->where('user_id', $guestId)->findOrEmpty()->isEmpty();
            if ($guest->isEmpty()) {
                $this->skip("用例7(散客退自己):散客 {$guestId} 不存在,跳过");
            } elseif ($hasMembership) {
                $this->skip("用例7(散客退自己):用户 {$guestId} 有成员关系,不是散客,跳过");
            } else {
                $guestTok0 = (float)$guest->tokens;
                $ownerTokNow = $this->tok($ownerId);
                $this->inTx(function () use ($guestId, $teamId, $ownerId, $guestTok0, $ownerTokNow) {
                    TeamBillingService::refundToTeam($guestId, 3.0, $teamId);
                    $this->assertEq((string)($guestTok0 + 3.0), $this->tok($guestId), '散客退费:退回散客自己');
                    $this->assertEq((string)$ownerTokNow, $this->tok($ownerId), '散客退费:团队长算力不变');
                });
            }
        } else {
            $this->skip('用例7(散客退自己):未传 --guest,跳过');
        }

        // ---- 附:P3 隔离只读信息(有数据才有意义) ----
        $this->assertEq((string)$teamId, (string)TeamContextService::currentTeamId($memberId),
            'P3:成员当前企业空间 = 其 team_id');
        $robotCnt = (int)Db::name('kb_robot')->where('team_id', $teamId)->whereNull('delete_time')->count();
        $kbCnt    = (int)Db::name('kb_know')->where('team_id', $teamId)->whereNull('delete_time')->count();
        $ragCnt   = (int)Db::name('knowledge')->where('team_id', $teamId)->count();
        $this->out->writeln("  [信息] 企业空间团队资源: 智能体={$robotCnt} 向量库={$kbCnt} RAG库={$ragCnt} (全员共享)");

        $this->out->writeln(str_repeat('=', 78));
        $this->out->writeln("结果: 通过 {$this->pass} 项, 失败 {$this->fail} 项");
        $this->out->writeln('说明: 全程在事务内模拟并已回滚,未改动任何数据。');
        return $this->fail > 0 ? 1 : 0;
    }

    /** 嵌套事务内执行并回滚(不留痕) */
    private function inTx(callable $fn): void
    {
        Db::startTrans();
        try {
            $fn();
        } catch (\Throwable $e) {
            $this->fail++;
            $this->out->writeln("  <error>[异常] " . $e->getMessage() . "</error>");
        } finally {
            Db::rollback();
        }
    }

    private function tok(int $userId): string
    {
        return number_format((float)(User::where('id', $userId)->value('tokens') ?? 0), 2, '.', '');
    }

    private function wallet(int $teamId, int $userId): string
    {
        return number_format((float)(TeamMember::where('team_id', $teamId)->where('user_id', $userId)->value('team_tokens') ?? 0), 2, '.', '');
    }

    private function assertEq(string $expect, string $actual, string $name): void
    {
        $expect = number_format((float)$expect, 2, '.', '');
        $actual = number_format((float)$actual, 2, '.', '');
        if (bccomp($expect, $actual, 2) === 0) {
            $this->pass++;
            $this->out->writeln("  <info>[PASS]</info> {$name}");
        } else {
            $this->fail++;
            $this->out->writeln("  <error>[FAIL]</error> {$name}  期望={$expect} 实际={$actual}");
        }
    }

    private function assertTrue(bool $cond, string $name): void
    {
        if ($cond) {
            $this->pass++;
            $this->out->writeln("  <info>[PASS]</info> {$name}");
        } else {
            $this->fail++;
            $this->out->writeln("  <error>[FAIL]</error> {$name}");
        }
    }

    private function skip(string $msg): void
    {
        $this->out->writeln("  <comment>[SKIP]</comment> {$msg}");
    }
}
