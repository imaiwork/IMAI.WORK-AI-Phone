<?php

namespace app\api\logic\wechat;

use app\common\model\sv\SvAddWechatRecord;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatAcceptFriendStrategy;
use app\common\model\wechat\AiWechatAddRecord;
use app\common\service\sv\ServiceContactFilter;
use app\common\workerman\wechat\handlers\client\AddFriendsTaskHandler;
use Channel\Client as ChannelClient;
use think\facade\Log;

/**
 * AiWechatAddLeadsLogic
 * @desc AI销售加微-线索定时任务
 * 1. 使用 iw_ai_wechat_accept_friend_strategy(is_enable=1) 组装执行账号
 * 2. 对 iw_sv_add_wechat_record 中 status in (3,4,5) 且 exec_type 为私信类(1私信聊天/3主动私信)的线索执行添加微信
 *    exec_type=2 自动爬取是用户无互动的冷线索,不执行(风控高危、通过率低、白烧配额)
 * 3. 已执行过的线索（本表有记录）不再重复添加
 * 4. 当日配额复用策略 accept_numbers（与自动通过好友配额分开单独统计），
 *    间隔复用策略 interval_time
 *
 * 日志通道: ai_add_wechat (runtime/log/ai_add_wechat/)
 * 注意: 日志必须内联拼接文本,不要传context数组(think Log的context只用于占位符替换,不会输出)
 */
class AiWechatAddLeadsLogic
{
    /** 可执行的线索状态：3账号安全冷却 4频繁执行 5等待队列 */
    const CANDIDATE_STATUS = [3, 4, 5];

    /** 允许AI销售加微的线索类型(exec_type)：1私信聊天 3主动私信；2自动爬取为冷线索(用户无互动)不执行 */
    const ALLOWED_EXEC_TYPES = [1, 3];

    /** 账号冷却判定时长（秒），与加微冷却逻辑保持一致 */
    const COOLING_SECONDS = 7200;

    /** 单账号单轮最多下发条数（策略未配置间隔时的批量保护） */
    const BATCH_LIMIT_PER_RUN = 5;

    /** 无上限时使用的大配额（accept_numbers=0 视为不限） */
    const UNLIMITED_QUOTA = 99999;

    /** 单轮每个策略最多捞取的候选线索数 */
    const CANDIDATE_FETCH_LIMIT = 50;

    /** 默认验证消息 */
    const DEFAULT_GREETING = '您好！';

    /** 按渠道验证消息池 key=iw_sv_add_wechat_record.channel */
    const CHANNEL_REMARKS = [
        1 => ['视频号看到的~ 咨询一下～', '老师，你好，视频号~', '视频号刷到的，通过一下~', '你好～通过一下～', 'hello～想咨询一下～'],
        4 => ['抖音看到的~ 咨询一下~', '老板，你好，抖音~', '抖音刷到的，通过一下~', '你好～通过一下～', 'hello～想咨询一下～'],
    ];

    /** 渠道未在池中时的兜底验证消息池 */
    const DEFAULT_CHANNEL_REMARKS = ['你好～通过一下～', 'hello～想咨询一下～'];

    /** 执行中记录的超时阈值(秒),超时标记失败防止永久执行中 */
    const RUN_TIMEOUT_SECONDS = 86400;

    /** 本轮Channel服务不可用时熔断,剩余下发直接跳过,避免每条线索都等待连接超时 */
    private static bool $channelDown = false;

    public static function runCron(): void
    {
        self::$channelDown = false;

        // 超时兜底：超过阈值的执行中记录标记失败(设备长时间无回调),回调晚到仍会按task_id覆盖终态
        AiWechatAddRecord::where('status', AiWechatAddRecord::STATUS_RUNNING)
            ->where('create_time', '<', time() - self::RUN_TIMEOUT_SECONDS)
            ->update([
                'status' => AiWechatAddRecord::STATUS_FAIL,
                'result' => '执行超时(24小时无回调),系统自动标记失败',
                'update_time' => time(),
            ]);

        // 加微信策略开启才会执行AI销售加微信
        $strategies = AiWechatAcceptFriendStrategy::where('is_enable', 1)->select();
        if ($strategies->isEmpty()) {
            return;
        }

        foreach ($strategies as $strategy) {
            try {
                self::processStrategy($strategy);
            } catch (\Throwable $e) {
                Log::channel('ai_add_wechat')->info('AI销售加微策略执行失败 strategy_id:' . $strategy->id
                    . ' user_id:' . $strategy->user_id
                    . ' error:' . $e->getMessage()
                    . ' at ' . $e->getFile() . ':' . $e->getLine());
                Log::channel('ai_add_wechat')->info('AI销售加微策略执行失败堆栈:' . PHP_EOL . $e->getTraceAsString());
            }
        }
    }

    /**
     * 按策略处理一个用户
     */
    private static function processStrategy(AiWechatAcceptFriendStrategy $strategy): void
    {
        $wechatIds = array_map('strval', (array)($strategy->wechat_ids ?? []));
        if (empty($wechatIds)) {
            Log::channel('ai_add_wechat')->info('策略wechat_ids为空,跳过 strategy_id:' . $strategy->id . ' user_id:' . $strategy->user_id);
            return;
        }

        // 根据wechat_ids确定需要执行加微信的账号（在线、非冷却）
        $coolingThreshold = time() - self::COOLING_SECONDS;
        $wechats = AiWechat::where('user_id', $strategy->user_id)
            ->where('wechat_id', 'in', $wechatIds)
            ->where('wechat_status', 1)
            ->where(function ($query) use ($coolingThreshold) {
                $query->where('is_cooling', 0)
                    ->whereOr('cooling_time', '<', $coolingThreshold);
            })
            ->order('update_time asc')
            ->select();
        if ($wechats->isEmpty()) {
            Log::channel('ai_add_wechat')->info('策略无可用执行账号(离线/冷却/wechat_ids不匹配),跳过 strategy_id:' . $strategy->id
                . ' user_id:' . $strategy->user_id
                . ' 策略wechat_ids:' . implode(',', $wechatIds));
            return;
        }

        $dailyLimit = (int)$strategy->accept_numbers > 0
            ? (int)$strategy->accept_numbers
            : self::UNLIMITED_QUOTA;
        $intervalSeconds = max(0, (int)$strategy->interval_time) * 60;

        // 捞取本用户候选线索
        $leads = self::fetchCandidates((int)$strategy->user_id);
        if (empty($leads)) {
            Log::channel('ai_add_wechat')->info('策略无候选线索,本轮结束 strategy_id:' . $strategy->id
                . ' user_id:' . $strategy->user_id);
            return;
        }

        $leadIds = [];
        foreach ($leads as $lead) {
            $leadIds[] = $lead->id;
        }
        $accountIds = [];
        foreach ($wechats as $wechat) {
            $accountIds[] = $wechat->wechat_id;
        }
        Log::channel('ai_add_wechat')->info('策略开始执行 strategy_id:' . $strategy->id
            . ' user_id:' . $strategy->user_id
            . ' 当日上限:' . $dailyLimit
            . ' 执行间隔秒:' . $intervalSeconds
            . ' 执行账号[' . count($accountIds) . ']:' . implode(',', $accountIds)
            . ' 候选线索[' . count($leadIds) . ']:' . implode(',', $leadIds));

        $dispatchTotal = 0;
        foreach ($wechats as $wechat) {
            // 当日配额按账号维度独立统计（与自动通过好友的当日数量分开单独计算本次剩余配额）
            $todayCount = AiWechatAddRecord::where('wechat_id', $wechat->wechat_id)
                ->where('create_time', '>=', strtotime(date('Y-m-d 00:00:00')))
                ->count();
            $remaining = $dailyLimit - $todayCount;
            if ($remaining <= 0) {
                Log::channel('ai_add_wechat')->info('执行账号已达当日配额,跳过 wechat_id:' . $wechat->wechat_id
                    . ' 今日已发:' . $todayCount
                    . ' 当日上限:' . $dailyLimit);
                continue;
            }

            // 单账号执行间隔：距上次下发不足 interval_time 分钟则本轮跳过
            $lastExec = (int)AiWechatAddRecord::where('wechat_id', $wechat->wechat_id)
                ->max('exec_time');
            if ($lastExec > 0 && (time() - $lastExec) < $intervalSeconds) {
                Log::channel('ai_add_wechat')->info('执行账号未到执行间隔,跳过 wechat_id:' . $wechat->wechat_id
                    . ' 距上次下发秒:' . (time() - $lastExec)
                    . ' 间隔秒:' . $intervalSeconds);
                continue;
            }

            // 策略配置了执行间隔时，单轮单账号只下发1条，避免短时间连续添加
            $batchLimit = $intervalSeconds > 0 ? 1 : self::BATCH_LIMIT_PER_RUN;
            $dispatchCount = 0;

            foreach ($leads as $lead) {
                if ($dispatchCount >= $batchLimit || $remaining - $dispatchCount <= 0) {
                    break;
                }
                if (self::dispatchAddWechat($strategy, $wechat, $lead)) {
                    $dispatchCount++;
                    $dispatchTotal++;
                }
            }
        }

        Log::channel('ai_add_wechat')->info('策略执行结束 strategy_id:' . $strategy->id
            . ' user_id:' . $strategy->user_id
            . ' 本轮下发:' . $dispatchTotal);
    }

    /**
     * 捞取候选线索（已排除：无可加微信号、已被本任务执行过、已被其他记录加过/处理中）
     */
    private static function fetchCandidates(int $userId): array
    {
        $result = [];
        $leads = SvAddWechatRecord::where('user_id', $userId)
            ->where('reg_wechat', '<>', '')
            ->where('status', 'in', self::CANDIDATE_STATUS)
            ->where('exec_type', 'in', self::ALLOWED_EXEC_TYPES)
            ->order('update_time asc, id asc')
            ->limit(self::CANDIDATE_FETCH_LIMIT)
            ->select();

        // 被过滤的非私信类线索数量(冷线索烧配额+风控高危),单轮汇总打一条便于核对
        $excludedCount = SvAddWechatRecord::where('user_id', $userId)
            ->where('reg_wechat', '<>', '')
            ->where('status', 'in', self::CANDIDATE_STATUS)
            ->where('exec_type', 'not in', self::ALLOWED_EXEC_TYPES)
            ->count();
        if ($excludedCount > 0) {
            Log::channel('ai_add_wechat')->info('过滤非私信类线索,不执行加微 user_id:' . $userId
                . ' 排除条数:' . $excludedCount
                . ' 允许类型:' . implode(',', self::ALLOWED_EXEC_TYPES)
                . '(1私信聊天3主动私信,2自动爬取等不在其列)');
        }

        if ($leads->isEmpty()) {
            return $result;
        }

        $leadIds = $leads->column('id');
        // 已被本任务执行过的线索不重复添加
        $execRecordIds = array_map('intval', AiWechatAddRecord::where('user_id', $userId)
            ->where('record_id', 'in', $leadIds)
            ->limit(count($leadIds))
            ->column('record_id'));

        foreach ($leads as $lead) {
            if (in_array((int)$lead->id, $execRecordIds)) {
                Log::channel('ai_add_wechat')->info('线索已被本任务执行过,跳过 user_id:' . $userId
                    . ' record_id:' . $lead->id
                    . ' 待加微账号:' . $lead->reg_wechat);
                continue;
            }

            // 同用户下同微信号已有成功/处理中的记录，跳过避免重复添加
            $regWechat = trim((string)$lead->reg_wechat);
            // 官方服务号兜底：历史漏放的存量线索在消费入口闭环置无效，避免每轮反复被选中
            if (ServiceContactFilter::isServiceContact($regWechat)) {
                SvAddWechatRecord::where('id', $lead->id)
                    ->where('status', 'in', self::CANDIDATE_STATUS)
                    ->update([
                        'status' => 0,
                        'result' => '官方服务号，派发前拦截',
                        'update_time' => time(),
                    ]);
                Log::channel('ai_add_wechat')->info('AI销售加微派发拦截官方服务号 user_id:' . $userId
                    . ' record_id:' . $lead->id
                    . ' 待加微账号:' . $regWechat);
                continue;
            }
            $exists = SvAddWechatRecord::where('user_id', $userId)
                ->where('reg_wechat', $regWechat)
                ->where('status', 'in', [1, 2])
                ->where('id', '<>', $lead->id)
                ->count();
            if ($exists > 0) {
                Log::channel('ai_add_wechat')->info('线索微信号已有其他记录添加,跳过 user_id:' . $userId
                    . ' record_id:' . $lead->id
                    . ' 待加微账号:' . $regWechat);
                continue;
            }

            $result[] = $lead;
        }
        return $result;
    }

    /**
     * 按线索渠道取验证消息
     * 先根据 iw_sv_add_wechat_record.channel 检索对应池,未命中用兜底池,池内随机取一条
     */
    private static function pickGreeting(SvAddWechatRecord $lead): string
    {
        $channel = (int)($lead->channel ?? 0);
        $pool = self::CHANNEL_REMARKS[$channel] ?? self::DEFAULT_CHANNEL_REMARKS;
        if (!empty($pool)) {
            return (string)$pool[array_rand($pool)];
        }

        // 兜底池为空时退回线索行remark/默认文案
        $remark = trim((string)($lead->remark ?? ''));
        return $remark !== '' ? $remark : self::DEFAULT_GREETING;
    }

    /**
     * 原子占位线索并下发添加好友
     * @return bool 是否成功下发
     */
    private static function dispatchAddWechat(
        AiWechatAcceptFriendStrategy $strategy,
        AiWechat $wechat,
        SvAddWechatRecord $lead
    ): bool {
        // proto AddFriendsTaskMessage.TaskId 为 int64,必须用纯数字任务id
        $taskId = (string)(time() . mt_rand(100000, 999999));
        // 验证消息先按线索渠道取池,再随机取一条
        $greeting = self::pickGreeting($lead);
        $originalTaskId = (string)($lead->task_id ?? '');

        // Channel服务不可用熔断：避免并发场景下还去做无谓的占位/回滚
        if (self::$channelDown) {
            Log::channel('ai_add_wechat')->info('Channel服务不可用已熔断,跳过线索 record_id:' . $lead->id
                . ' 待加微账号:' . $lead->reg_wechat);
            return false;
        }

        // 原子占位：仅当线索仍处于候选状态时更新成功
        $claimed = SvAddWechatRecord::where('id', $lead->id)
            ->where('status', 'in', self::CANDIDATE_STATUS)
            ->update([
                'status' => AiWechatAddRecord::STATUS_RUNNING,
                'task_id' => $taskId,
                'wechat_no' => $wechat->wechat_id,
                'wechat_name' => $wechat->wechat_nickname,
                'wechat_avatar' => $wechat->wechat_avatar,
                'result' => '执行中',
                'update_time' => time(),
            ]);
        if ($claimed != 1) {
            Log::channel('ai_add_wechat')->info('线索占位失败(状态已被其他进程变更),跳过 record_id:' . $lead->id
                . ' 待加微账号:' . $lead->reg_wechat);
            return false;
        }

        try {
            $content = AddFriendsTaskHandler::handle([
                'DeviceId' => $wechat->device_code,
                'WeChatId' => $wechat->wechat_id,
                'Phones' => [$lead->reg_wechat],
                'Message' => $greeting,
                'TaskId' => $taskId,
                'Remark' => (string)($lead->Remark ?? ''),
            ]);

            $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
            $message->setMsgType($content['MsgType']);
            $any = new \Google\Protobuf\Any();
            $any->pack($content['Content']);
            $message->setContent($any);
            $pushMessage = $message->serializeToString();

            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish("socket.{$wechat->device_code}.message", [
                'data' => is_array($pushMessage) ? json_encode($pushMessage) : $pushMessage,
            ]);

            $execRecord = AiWechatAddRecord::create([
                'user_id' => (int)$strategy->user_id,
                'record_id' => (int)$lead->id,
                'task_id' => $taskId,
                'wechat_id' => $wechat->wechat_id,
                'device_code' => $wechat->device_code,
                'friend_wechat' => $lead->reg_wechat,
                'channel' => $lead->channel,
                'message' => $greeting,
                'status' => AiWechatAddRecord::STATUS_RUNNING,
                'result' => '执行中',
                'exec_time' => time(),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            Log::channel('ai_add_wechat')->info('AI销售加微下发成功'
                . ' exec_id:' . $execRecord->id
                . ' user_id:' . $strategy->user_id
                . ' strategy_id:' . $strategy->id
                . ' record_id:' . $lead->id
                . ' task_id:' . $taskId
                . ' 执行账号:' . $wechat->wechat_id
                . ' 执行账号昵称:' . $wechat->wechat_nickname
                . ' 执行设备:' . $wechat->device_code
                . ' 待加微账号:' . $lead->reg_wechat
                . ' 验证消息:' . $greeting);
            return true;
        } catch (\Throwable $e) {
            // Channel连接异常时熔断本轮,剩余线索不再重复等待连接
            if (str_contains($e->getMessage(), 'stream_socket_client') || str_contains($e->getMessage(), 'Channel服务不可用')) {
                self::$channelDown = true;
            }

            // 下发失败：回滚线索到等待队列,还原原task_id,删除执行记录以便下轮重试
            // 条件带task_id精确匹配,避免误覆盖其他进程刚占位的线索
            SvAddWechatRecord::where('id', $lead->id)
                ->where('status', AiWechatAddRecord::STATUS_RUNNING)
                ->where('task_id', $taskId)
                ->update([
                    'status' => 5,
                    'task_id' => $originalTaskId,
                    'result' => '下发失败:' . $e->getMessage(),
                    'update_time' => time(),
                ]);
            AiWechatAddRecord::where('task_id', $taskId)->delete();

            Log::channel('ai_add_wechat')->info('AI销售加微下发失败'
                . ' record_id:' . $lead->id
                . ' task_id:' . $taskId
                . ' 执行账号:' . $wechat->wechat_id
                . ' 执行设备:' . $wechat->device_code
                . ' 待加微账号:' . $lead->reg_wechat
                . ' error:' . $e->getMessage()
                . ' at ' . $e->getFile() . ':' . $e->getLine());
            return false;
        }
    }
}
