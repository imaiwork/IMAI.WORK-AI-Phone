<?php

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 团队OEM 测试数据播种(仅用于测试环境,可一键清理)
 *
 * 用法:
 *   php think team:seed-test           # 造一套完整样本(团队+团队长+成员+管理员+散客+资源)
 *   php think team:seed-test --clean   # 清理本命令造的全部测试数据(硬删,不留痕)
 *
 * 造出:
 *   - 团队长(role=2,大额个人算力)、成员(role=1,有企业钱包)、管理员(role=3,有企业钱包)、散客(role=0,有个人算力)
 *   - 已开通OEM(oem_status=2)的团队;成员/管理员的 team_member 钱包
 *   - 企业空间内 1 个智能体 + 1 个向量库 + 1 个个人智能体(用于验证列表隔离)
 *
 * 全部数据用 account 前缀 `oemtest_` / 名称前缀 `【OEMTEST】` 打标,--clean 精确清除。
 */
class TeamSeedTest extends Command
{
    const MARK_ACCOUNT = 'oemtest_';
    const MARK_NAME    = '【OEMTEST】';

    protected function configure()
    {
        $this->setName('team:seed-test')
            ->setDescription('团队OEM 测试数据播种(测试环境用,--clean 可清理)')
            ->addOption('clean', null, Option::VALUE_NONE, '清理本命令造的全部测试数据');
    }

    protected function execute(Input $input, Output $output)
    {
        if ($input->getOption('clean')) {
            return $this->clean($output);
        }
        return $this->seed($output);
    }

    private function seed(Output $output): int
    {
        // 已存在则先提示清理,避免重复
        $exist = Db::name('user')->where('account', 'like', self::MARK_ACCOUNT . '%')->count();
        if ($exist > 0) {
            $output->warning("检测到已有测试数据({$exist} 个账号),请先执行: php think team:seed-test --clean");
            return 1;
        }

        Db::startTrans();
        try {
            $now = time();
            $mkUser = function (string $tag, float $tokens, int $teamId, int $role) use ($now) {
                return (int)Db::name('user')->insertGetId([
                    'sn'          => mt_rand(10000000, 99999999),
                    'account'     => self::MARK_ACCOUNT . $tag,
                    'nickname'    => self::MARK_NAME . $tag,
                    'mobile'      => '',
                    'avatar'      => '',
                    'tokens'      => $tokens,
                    'team_id'     => $teamId,
                    'team_role'   => $role,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
            };

            // 1) 团队长(先建,拿到 id 后建团队,再回填 team_id)
            $ownerId = $mkUser('owner', 100000, 0, 2);
            // 2) 团队(已开通OEM)
            $teamId = (int)Db::name('team')->insertGetId([
                'name'         => self::MARK_NAME . '测试企业',
                'owner_id'     => $ownerId,
                'seat_limit'   => 10,
                'member_count' => 3,
                'status'       => 1,
                'oem_status'   => 2,
                'oem_pay_tokens' => 0,
                'create_time'  => $now,
                'update_time'  => $now,
            ]);
            Db::name('user')->where('id', $ownerId)->update(['team_id' => $teamId]);

            // 3) 成员(role=1,个人算力=0,企业钱包=50)、管理员(role=3,钱包=20)、散客(role=0,个人算力=500)
            $memberId = $mkUser('member', 0, $teamId, 1);
            $adminId  = $mkUser('admin', 0, $teamId, 3);
            $guestId  = $mkUser('guest', 500, $teamId, 0);

            // 4) team_member 关系(团队长/成员/管理员;散客无成员关系)
            $mkMember = function (int $uid, int $role, float $wallet) use ($teamId, $now) {
                Db::name('team_member')->insert([
                    'team_id'     => $teamId,
                    'user_id'     => $uid,
                    'role'        => $role,
                    'team_tokens' => $wallet,
                    'expire_time' => 0,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
            };
            $mkMember($ownerId, 2, 0);
            $mkMember($memberId, 1, 50);
            $mkMember($adminId, 3, 20);

            // 5) 资源(用于验证 P3 列表隔离):企业空间智能体+向量库,以及一个个人智能体
            $this->tryInsert('kb_robot', [
                'user_id' => $memberId, 'team_id' => $teamId,
                'name' => self::MARK_NAME . '团队智能体', 'intro' => '团队共享', 'code' => 'oemtest_' . mt_rand(1000, 9999),
                'create_time' => $now, 'update_time' => $now,
            ], $output);
            $this->tryInsert('kb_robot', [
                'user_id' => $memberId, 'team_id' => 0,
                'name' => self::MARK_NAME . '个人智能体', 'intro' => '仅个人', 'code' => 'oemtest_' . mt_rand(1000, 9999),
                'create_time' => $now, 'update_time' => $now,
            ], $output);
            $this->tryInsert('kb_know', [
                'user_id' => $memberId, 'create_uid' => $memberId, 'team_id' => $teamId,
                'name' => self::MARK_NAME . '团队知识库', 'intro' => '团队共享',
                'create_time' => $now, 'update_time' => $now,
            ], $output);

            Db::commit();

            $output->writeln(str_repeat('=', 70));
            $output->writeln('<info>测试数据已生成:</info>');
            $output->writeln("  团队ID       = {$teamId}  (【OEMTEST】测试企业, oem_status=2)");
            $output->writeln("  团队长 owner = {$ownerId} (role=2, 个人算力=100000)");
            $output->writeln("  成员 member  = {$memberId} (role=1, 个人算力=0, 企业钱包=50)");
            $output->writeln("  管理员 admin = {$adminId} (role=3, 个人算力=0, 企业钱包=20)");
            $output->writeln("  散客 guest   = {$guestId} (role=0, 个人算力=500)");
            $output->writeln(str_repeat('-', 70));
            $output->writeln('下一步验证计费/退费:');
            $output->writeln("  <comment>php think team:oem-selfcheck --member={$memberId} --guest={$guestId}</comment>");
            $output->writeln('清理测试数据:');
            $output->writeln('  <comment>php think team:seed-test --clean</comment>');
            $output->writeln(str_repeat('=', 70));
            return 0;
        } catch (\Throwable $e) {
            Db::rollback();
            $output->error('播种失败: ' . $e->getMessage());
            return 1;
        }
    }

    private function clean(Output $output): int
    {
        $userIds = Db::name('user')->where('account', 'like', self::MARK_ACCOUNT . '%')->column('id');
        $teamIds = Db::name('team')->where('name', 'like', self::MARK_NAME . '%')->column('id');

        $delUsers   = $userIds ? Db::name('user')->whereIn('id', $userIds)->delete() : 0;
        $delMembers = $teamIds ? Db::name('team_member')->whereIn('team_id', $teamIds)->delete() : 0;
        $delTeams   = $teamIds ? Db::name('team')->whereIn('id', $teamIds)->delete() : 0;
        $delRobots  = Db::name('kb_robot')->where('name', 'like', self::MARK_NAME . '%')->delete();
        $delKnows   = Db::name('kb_know')->where('name', 'like', self::MARK_NAME . '%')->delete();

        $output->writeln("已清理: 用户 {$delUsers}, 成员关系 {$delMembers}, 团队 {$delTeams}, 智能体 {$delRobots}, 向量库 {$delKnows}");
        return 0;
    }

    /** 资源表字段较多,best-effort 插入,失败仅告警不阻断核心样本 */
    private function tryInsert(string $table, array $data, Output $output): void
    {
        try {
            Db::name($table)->insert($data);
        } catch (\Throwable $e) {
            $output->writeln("  <comment>[跳过]</comment> {$table} 资源播种失败(不影响计费测试): " . $e->getMessage());
        }
    }
}
