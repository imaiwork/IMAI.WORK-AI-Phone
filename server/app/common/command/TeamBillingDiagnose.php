<?php

namespace app\common\command;

use app\common\model\user\User;
use app\common\service\TeamBillingService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 排查「团队模式仍扣个人算力」——打印用户/成员/钱包与 resolveSpender 结论。
 *
 * 用法:
 *   php think team:billing-diagnose --mobile=13652847937
 *   php think team:billing-diagnose --user=123
 *   php think team:billing-diagnose --mobile=13652847937 --sync=1
 */
class TeamBillingDiagnose extends Command
{
    protected function configure()
    {
        $this->setName('team:billing-diagnose')
            ->setDescription('诊断团队扣费主体(个人 vs 企业钱包)')
            ->addOption('mobile', null, Option::VALUE_OPTIONAL, '手机号')
            ->addOption('user', null, Option::VALUE_OPTIONAL, '用户ID')
            ->addOption('sync', null, Option::VALUE_OPTIONAL, '为1时同步 user.team_role=team_member.role', '0');
    }

    protected function execute(Input $input, Output $output)
    {
        $mobile = trim((string)$input->getOption('mobile'));
        $userId = (int)$input->getOption('user');
        $doSync = (int)$input->getOption('sync') === 1;

        if ($userId <= 0 && $mobile === '') {
            $output->error('请提供 --mobile=手机号 或 --user=用户ID');
            return 1;
        }

        $prefix = (string)config('database.connections.mysql.prefix');
        $output->writeln("DB prefix = [{$prefix}]  (线上若表是 iw_* 而这里是 la_，配置就不对)");

        $userQuery = User::field('id,sn,mobile,nickname,tokens,team_id,team_role,team_expire_time');
        if ($userId > 0) {
            $user = $userQuery->where('id', $userId)->findOrEmpty();
        } else {
            $user = $userQuery->where('mobile', $mobile)->findOrEmpty();
        }
        if ($user->isEmpty()) {
            $output->error('用户不存在');
            return 1;
        }

        $uid = (int)$user->id;
        $teamId = (int)$user->team_id;
        $output->writeln(str_repeat('=', 72));
        $output->writeln("用户 id={$uid} mobile={$user->mobile} nickname={$user->nickname}");
        $output->writeln("user.tokens(个人)={$user->tokens}");
        $output->writeln("user.team_id={$teamId}  user.team_role={$user->team_role}  team_expire_time={$user->team_expire_time}");

        $memberships = Db::name('team_member')
            ->where('user_id', $uid)
            ->field('id,team_id,user_id,role,team_tokens,expire_time,delete_time,create_time,update_time')
            ->select()
            ->toArray();
        $output->writeln('team_member 行数=' . count($memberships));
        foreach ($memberships as $m) {
            $del = var_export($m['delete_time'], true);
            $active = ($m['delete_time'] === null || (int)$m['delete_time'] === 0) ? 'ACTIVE' : 'DELETED';
            $output->writeln(
                "  - mid={$m['id']} team_id={$m['team_id']} role={$m['role']} team_tokens={$m['team_tokens']}"
                . " delete_time={$del} [{$active}]"
            );
        }

        $active = TeamBillingService::findActiveMembership($teamId, $uid);
        $output->writeln('findActiveMembership(当前 team_id)=' . ($active ? json_encode($active, JSON_UNESCAPED_UNICODE) : 'NULL'));

        if ($teamId > 0) {
            $team = Db::name('team')
                ->where('id', $teamId)
                ->where(function ($q) {
                    $q->whereNull('delete_time')->whereOr('delete_time', 0);
                })
                ->field('id,name,owner_id,status,delete_time')
                ->find();
            $output->writeln('team=' . ($team ? json_encode($team, JSON_UNESCAPED_UNICODE) : 'NULL/已删'));
        }

        if ($doSync && $active) {
            User::where('id', $uid)->update([
                'team_role' => (int)$active['role'],
                'team_expire_time' => (int)(Db::name('team_member')->where('id', (int)$active['id'])->value('expire_time') ?? 0),
            ]);
            $user = User::field('id,tokens,team_id,team_role,team_expire_time')->where('id', $uid)->findOrEmpty();
            $output->writeln("<info>已同步 team_role={$user->team_role}</info>");
        }

        $spender = TeamBillingService::resolveSpender($uid);
        $spendable = TeamBillingService::spendableTokens($uid);
        $output->writeln(str_repeat('-', 72));
        if ($spender === null) {
            $output->writeln('<error>resolveSpender=NULL → 扣费会走「个人算力」</error>');
            $reasons = [];
            if ($teamId <= 0) {
                $reasons[] = 'user.team_id=0(当前不在企业空间；自动化按个人扣)';
            }
            if ($active === null && $teamId > 0) {
                $reasons[] = '当前 team_id 下没有有效 team_member(注意 delete_time；或前缀表名不对)';
            }
            $role = $active !== null ? (int)$active['role'] : (int)$user->team_role;
            if ($teamId > 0 && !in_array($role, [TeamBillingService::ROLE_MEMBER, TeamBillingService::ROLE_ADMIN], true)) {
                $reasons[] = "角色不是成员/管理员(role={$role}; 创始人扣个人算力是预期)";
            }
            if ($teamId > 0 && $active !== null) {
                $ownerId = (int)(Db::name('team')->where('id', $teamId)->value('owner_id') ?? 0);
                if ($ownerId === $uid) {
                    $reasons[] = '该用户是团队主(owner)，扣个人算力是预期';
                }
            }
            if (!$reasons) {
                $reasons[] = '未知原因，请核对代码是否已部署并重启 Workerman';
            }
            foreach ($reasons as $r) {
                $output->writeln('  原因: ' . $r);
            }
        } else {
            $output->writeln('<info>resolveSpender=' . json_encode($spender, JSON_UNESCAPED_UNICODE) . ' → 应扣企业钱包</info>');
        }
        $output->writeln("spendableTokens(当前可用)={$spendable}");
        $output->writeln(str_repeat('=', 72));
        $output->writeln('修复建议:');
        $output->writeln('  1) 若 team_role 与 member.role 不一致: 本命令加 --sync=1，或执行 public/update/v2.12.0_sync_team_role_from_member.sql');
        $output->writeln('  2) 部署含 TeamBillingService 的代码后必须重启 Workerman/相关常驻进程');
        $output->writeln('  3) 自动化扣费时用户必须处于企业空间(user.team_id>0)');

        return $spender === null ? 2 : 0;
    }
}
