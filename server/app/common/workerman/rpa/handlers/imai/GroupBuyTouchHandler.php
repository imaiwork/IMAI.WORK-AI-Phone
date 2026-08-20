<?php

namespace app\common\workerman\rpa\handlers\imai;

use app\common\model\sv\SvGroupBuyRecord;
use app\common\model\sv\SvGroupBuyTask;
use app\common\model\sv\SvGroupBuyTaskAccount;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;

use app\common\enum\AutomationEnum;
use app\common\service\AutomationBillingService;

/**
 * GroupBuyHandler
 * @desc 团购截流获客任务消息处理器
 */
class GroupBuyTouchHandler extends BaseMessageHandler
{
    /**
     * 当前设备的 App 类型（平台类型）
     * @var int
     */
    protected $appType = 0;

    /**
     * 消息入口
     */
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content'])
            ? json_decode($payload['content'], true)
            : $payload['content'];

        try {
            $this->msgType    = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->appType    = $payload['appType'] ?? 0;
            $this->uid        = $uid;
            $this->payload    = $payload;
            $this->userId     = $content['userId'] ?? 0;
            $this->connection = $connection;

            $this->payload['reply'] = $this->savePost($content);
            $this->payload['type']  = WorkerEnum::RPA_SEND_MESSAGE;
            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('团购截流获客任务异常：' . $e->getMessage(), 'group_buy');
            $this->payload['reply']   = $e->getMessage();
            $this->payload['code']    = WorkerEnum::RPA_GROUP_BUY_TOUCH_FAIL;
            $this->payload['type']    = WorkerEnum::RPA_GROUP_BUY_TOUCH;
            $this->payload['content'] = [
                'code'     => WorkerEnum::RPA_GROUP_BUY_TOUCH_FAIL,
                'msg'      => '异常信息：' . $e->getMessage(),
                'deviceId' => $payload['deviceId'] ?? '',
            ];
            $this->sendError($this->connection, $this->payload);
        } finally {
            unset($content);
        }
    }

    /**
     * 保存团购截流记录
     *
     * @param array $content 设备上报的消息体
     * @return array ['ok' => 1|0]
     */
    private function savePost(array $content): array
    {
        try {
            // 无效 task_id 直接跳过
            if ((int)($content['task_id'] ?? 0) === 0) {
                return ['ok' => 0];
            }

            // 昵称或类型缺失时跳过
            if (!isset($content['nickName']) || (int)($content['type'] ?? 0) === 0) {
                return ['ok' => 0];
            }

            // 获取子任务的 task_type
            $taskType = $this->getTaskTypeId((int)$content['task_id']);

            // 查询子任务（账号维度）
            $task = SvGroupBuyTaskAccount::where('id', $content['task_id'])
                ->where('device_code', $this->payload['deviceId'])
                ->where('task_type', $taskType)
                ->where('account_type', $this->appType)
                ->findOrEmpty();

            if ($task->isEmpty()) {
                throw new \Exception(
                    ($this->platform[$this->appType] ?? '') .
                        '团购截流获客任务不存在: ' . \think\facade\Db::getLastSql()
                );
            }

            // 查询主任务配置
            $setting = SvGroupBuyTask::where('id', $task->group_buy_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                throw new \Exception(
                    ($this->platform[$this->appType] ?? '') . '团购截流获客任务配置不存在'
                );
            }

            $account = str_replace(WorkerEnum::getAccountTypeDesc($this->payload['appType'] ?? 3) . '号：', '', $content['account'] ?? '');

            // 按目标账号 + 任务类型 + 系统用户去重（跨主任务/时间段也生效）
            if ($account !== '') {
                $exists = SvGroupBuyRecord::where('user_id', $task->user_id)
                    ->where('account', $account)
                    ->where('task_type', $taskType)
                    ->where('status', '<>', 4)
                    ->findOrEmpty();
                if (!$exists->isEmpty()) {
                    $this->setLog(
                        '团购截流重复跳过写入：user_id=' . (int)$task->user_id
                        . '，account=' . $account
                        . '，task_type=' . $taskType
                        . '，已有记录id=' . (int)$exists->id
                        . '，当前子任务id=' . (int)$content['task_id'],
                        'group_buy'
                    );
                    return ['ok' => 1];
                }
            } else {
                $this->setLog('团购截流目标账号为空，无法按 userid 去重，继续写入', 'group_buy');
            }

            // 生成去重 hash（优先用目标账号；为空则回退为昵称+评论内容）
            $hash = $account !== ''
                ? hash('sha256', $account)
                : hash('sha256', $content['nickName'] . ($content['commentStr'] ?? ''));

            // 写入截流记录（对应 iw_sv_group_buy_record 表）
            $insert = [
                'user_id'              => $task->user_id,
                'task_type'            => $taskType,
                'group_buy_id'         => $task->group_buy_id,        // 主任务 ID
                'group_buy_account_id' => $task->id,                  // 子任务 ID
                'status'               => 3,                          // 发送中
                'account'              => $account,
                'account_name'         => $content['nickName'],
                'account_type'         => $this->appType,
                'platform'             => $this->appType,
                'device_code'          => $this->payload['deviceId'],
                'task_id'              => $content['task_id'],
                'content'              => $content['commentStr'] ?? '',
                'address'              => $content['area'] ?? '',
                'pusher_timer'         => $content['pusherTimer'] ?? 0,
                'exec_time'            => time(),
                'hash'                 => $hash,
                'image'                => $this->toolUtil->saveBase64ToImage(
                    $content['image'] ?? '',
                    $hash,
                    'group_buy'
                ),
                'avatar'               => $this->toolUtil->saveBase64ToImage(
                    $content['avatar'] ?? '',
                    time() . rand(1000, 9999),
                    'group_buy'
                ),
                'likes'                => $content['thumbsUpAndcollect'] ?? 0,
                'fans'                 => $content['numberFans'] ?? 0,
                'follows'              => $content['numberFollowers'] ?? 0,
                'industry_keyword'     => $content['industryKeywords'] ?? '',
                'note_title'           => $content['title'] ?? '',
                'notes'                => $content['notes'] ?? '',
                'filter_keyword'       => $content['targetKeywords'] ?? '',
                'comment_content'      => $content['replyCommentStr'] ?? '',
                'touch_content'        => $content['touch_content'] ?? '',
            ];

            SvGroupBuyRecord::create($insert);
            $scene = AutomationEnum::GROUP_BUY;
            self::requestUrl($insert, $scene, $task->user_id, $content['task_id'],  $this->payload['deviceId']);
            return ['ok' => 1];
        } catch (\Exception $e) {
            $this->setLog('团购截流获客任务 savePost 异常：' . $e, 'group_buy');
            $this->payload['reply']   = $e->getMessage();
            $this->payload['code']    = WorkerEnum::RPA_GROUP_BUY_TOUCH_FAIL;
            $this->payload['type']    = WorkerEnum::RPA_GROUP_BUY_TOUCH;
            $this->payload['content'] = [
                'code'     => WorkerEnum::RPA_GROUP_BUY_TOUCH_FAIL,
                'msg'      => '异常信息：' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId'],
            ];
            $this->sendError($this->connection, $this->payload);

            return ['ok' => 0];
        } finally {
            unset($content);
        }
    }

    /**
     * 根据子任务 ID 获取 task_type
     *
     * @param int $taskId  SvGroupBuyTaskAccount.id
     * @return int
     */
    private function getTaskTypeId(int $taskId): int
    {
        try {
            $task = SvGroupBuyTaskAccount::where('id', $taskId)->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception(
                    ($this->platform[$this->appType] ?? '') .
                        '团购截流获客任务不存在: ' . \think\facade\Db::getLastSql()
                );
            }

            $setting = SvGroupBuyTask::where('id', $task->group_buy_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                throw new \Exception(
                    ($this->platform[$this->appType] ?? '') . '团购截流获客任务配置不存在'
                );
            }

            return (int)$task->task_type;
        } catch (\Throwable $th) {
            $this->setLog('getTaskTypeId 异常：' . $th, 'group_buy');
        }

        return 0;
    }

    private static function requestUrl(array $request, string $scene, int $userId, string $taskId, string $device_code)
    {
        return AutomationBillingService::requestAndCharge($request, $scene, $userId, $taskId, $device_code);
    }
}
