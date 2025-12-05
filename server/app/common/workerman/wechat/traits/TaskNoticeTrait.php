<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\traits;

use app\api\logic\wechat\sop\StageLogic;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvAddWechatStrategy;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatLog;
use app\common\model\wechat\AiWechatAcceptFriendStrategy;
use app\common\model\sv\SvCrawlingManualTaskRecord;

/**
 * 通用通知特殊处理
 * 
 * @author my
 * @package app\traits
 */
trait TaskNoticeTrait
{
    use CacheTrait;


    /**
     * 语音转文字
     *
     * @param string $deviceId
     * @param array $response
     * @return void
     */
    public function voiceToTextOpt(string $deviceId, array $response): void
    {
        
        if($response['Data']['MsgType'] == 'VoiceTransTextTask'){
            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('VoiceTransTextTask')->withContext([
                'msg' => '语音转文字任务已存在',
                'data' => $response
            ])->log();

            $statusKey = "device:{$deviceId}:voiceToText";
            $isVoiceToText = $this->redis()->get($statusKey);
            if ($isVoiceToText == 1) {
                $_content = $response['Data']['Content'];
                $key = "device:{$deviceId}:voice:{$_content['WeChatId']}:taskid:{$_content['TaskId']}";
                $this->redis()->set($key, $_content['ErrMsg']);
            }
        }
    }

    public function AcceptFriendAddRequestTaskOpt(string $deviceId, array $response): void
    {
        $data = $response['Data'];
        if ($data['MsgType'] == 'FriendAddReqeustNotice') {
            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Data')->withContext([
                'msg' => 'AcceptFriendAddRequestTaskOpt',
                'response' => $response
            ])->log();
            $payload = $response['Data']['Content'];
            $payload['FriendInfo']['Source'] = 1000000 + (int)$payload['FriendInfo']['Source'];
            $friend_id = $payload['FriendInfo']['FriendId'];

            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('payload')->withContext($payload)->log();
            try {
                // 获取微信账号信息
                $wechat = AiWechat::where('wechat_id', $payload['WeChatId'])->findOrEmpty();
                if ($wechat->isEmpty()) {
                    $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Error')->withContext([
                        'msg' => '设备账号信息不存在'
                    ])->log();
                    return;
                }
                // 获取自动通过好友策略
                $strategy = AiWechatAcceptFriendStrategy::where('user_id', $wechat->user_id)->findOrEmpty();
                if ($strategy->isEmpty()) {
                    $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Error')->withContext([
                        'msg' => '请先设置自动通过好友策略'
                    ])->log();
                    return;
                }
                if ($strategy->is_enable == 0) {
                    $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Error')->withContext([
                        'msg' => '自动通过好友策略已禁用'
                    ])->log();
                    return;
                }
                // 检查微信号是否存在执行集合中
                if (!in_array($wechat->wechat_id, $strategy->wechat_ids)) {
                    $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Error')->withContext([
                        'msg' => '此微信号未在自动通过好友策略中'
                    ])->log();
                    return;
                }
                // 检查好友来源是否符合
                if (!empty($strategy->accept_source) && !in_array($payload['FriendInfo']['Source'], $strategy->accept_source)) {
                    $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Error')->withContext([
                        'msg' => '此好友来源未在自动通过好友策略中'
                    ])->log();
                    return;
                }
                // 当日已接受好友数量
                $todayStart = date('Y-m-d 00:00:00');
                $todayEnd = date('Y-m-d 23:59:59');
                $todayAcceptCount = AiWechatLog::where('user_id', $wechat->user_id)
                    ->where('log_type', AiWechatLog::TYPE_ACCEPT_FRIEND)
                    ->whereBetween('create_time', [$todayStart, $todayEnd])
                    ->count();
                // 超出添加上限
                if ($todayAcceptCount >= $strategy->accept_numbers) {
                    $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Error')->withContext([
                        'msg' => '今日已达到自动通过好友数量上限'
                    ])->log();
                    return;
                }
                // 获取最近一条接受时间
                $lastAcceptTime = AiWechatLog::where('user_id', $wechat->user_id)
                    ->where('log_type', AiWechatLog::TYPE_ACCEPT_FRIEND)
                    ->where('friend_id', $friend_id)
                    ->order('create_time', 'desc')
                    ->value('create_time', 0);
                // 计算当前时间 与 上一条接受时间 间隔 单位分钟
                $interval = 0;
                if ($lastAcceptTime) {
                    $interval = (time() - $lastAcceptTime) / 60;
                }
                $taskId = generate_unique_task_id();

                // 任务数据
                $data = [
                    'device_code'   => $wechat->device_code,
                    'wechat_id'     => $payload['WeChatId'],
                    'friend_id'     => $friend_id,
                    'task_id'       => $taskId,
                    'user_id'           => $wechat->user_id,
                ];
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Send AcceptFriendAddRequestTask')->withContext($data)->log();
                // 推送到队列
                $this->acceptFriendJob($data);

                AiWechatLog::create([
                    'user_id'   => $data['user_id'],
                    'wechat_id' => $data['wechat_id'],
                    'friend_id' => $data['friend_id'],
                    'log_type'      => AiWechatLog::TYPE_ACCEPT_FRIEND,
                    'create_time' => time()
                ]);

                StageLogic::sopActionStagetrigger([
                    'friend_id' => $data['friend_id'],
                    'wechat_id' => $data['wechat_id']
                ]);
            } catch (\Exception $e) {
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Error')->withContext([
                    'msg' => $e->getMessage()
                ])->log();
                return;
            }
        }
    }

    public function AddFriendsTaskOpt(string $deviceId, array $response): void
    {
        $data = $response['Data'];
        if ($data['MsgType'] == 'AddFriendsTask') {
            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('AddFriendsTaskOpt')->withContext([
                'msg' => 'AddFriendsTaskOpt',
                'response' => $response
            ])->log();

            $record = SvAddWechatRecord::where('task_id', $data['Content']['TaskId'])->limit(1)->findOrEmpty();
            $isSvAddWechat = true;
            if($record->isEmpty()){
                $record = SvCrawlingManualTaskRecord::where('exec_task_id', $data['Content']['TaskId'])->limit(1)->findOrEmpty();
                $isSvAddWechat = false;
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('AddFriendsTaskOpt')->withContext([
                    'msg' => 'AddFriendsTaskOpt',
                    'record' => $record->toArray()
                ])->log();
            }
            $errMsg = $data['Content']['ErrMsg'];
            if (!$record->isEmpty()) {
                $record->status = (int)$data['Content']['Success'] === 1 ? 1 : 0;
                $record->result = $record->status === 1 ? '添加请求成功!' : $data['Content']['ErrMsg'];

                $errorHandlers = [
                    'already_friend' => [
                        'keyword' => '已经是好友',
                        'handler' => function () use ($record) {
                            $record->status = 1;
                        }
                    ],
                    'operation_frequent' => [
                        'keyword' => '操作过于频繁，请稍后再试',
                        'handler' => function () use ($record) {
                            $record->status = 4;
                        }
                    ],
                    'security_risk' => [
                        'keyword' => ['当前账号存在安全风险', '你的账号暂时无法添加朋友'],
                        'handler' => function () use ($record, $isSvAddWechat) {
                            $record->status = 0;
                            $this->_setColingWechat($record);
                            if($isSvAddWechat){
                                $this->_resetAddFriendWechat($record);
                            }
                        }
                    ]
                ];

                // 遍历处理所有错误类型
                foreach ($errorHandlers as $handler) {
                    $keywords = is_array($handler['keyword']) ? $handler['keyword'] : [$handler['keyword']];
                    foreach ($keywords as $keyword) {
                        if (strpos($errMsg, $keyword) !== false) {
                            $handler['handler']();
                            break 2; // 匹配后跳出双层循环
                        }
                    }
                }

                $record->update = time();
                $record->save();

                $wechat = AiWechat::where('wechat_id', '=', $record->wechat_no)->limit(1)->find();
                if (!$wechat->isEmpty()) {
                    $wechat->update_time = time();
                    $wechat->save();
                }

                if($record->status === 1){
                    AiWechatLog::create([
                        'user_id'   => $record['user_id'],
                        'wechat_id' => $record['wechat_id'],
                        'friend_id' => $record['reg_wechat'],
                        'log_type'      => AiWechatLog::TYPE_THROUGH_FRIEND,
                        'create_time' => time()
                    ]);
                }
            }
        }
    }

    private function _resetAddFriendWechat(SvAddWechatRecord &$record)
    {
        $strategy = SvAddWechatStrategy::where('device_code', $record['deviceId'])->where('account', $record['account'])->limit(1)->findOrEmpty();
        if ($strategy->isEmpty()) {
            return;
        }

        $currentTime = time(); // 获取当前时间戳
        $coolingThreshold = $currentTime - 7200; // 2小时前的时间戳（7200秒）
        $wechat = AiWechat::field('*')
            ->where('user_id', $record['user_id'])
            ->where('wechat_id', 'in', explode(',', $strategy['wechat_id']))
            ->where(function ($query) use ($coolingThreshold) {
                $query->where('is_cooling', 0)
                    ->whereOr('cooling_time', '<', $coolingThreshold);
            })
            ->order('update_time asc')->limit(1)->findOrEmpty();

        if ($wechat->isEmpty()) {
            $record->status = 3;
            $record->result = '当前账号存在安全风险，暂停添加';
            return;
        }
        $record->status = 2;
        $record->wechat_no = $wechat->wechat_id;
        $record->wechat_name = $wechat->wechat_nickname;
        $record->result = '';

        $message = [
            'DeviceId' => $wechat['device_code'],
            'WeChatId' => $wechat['wechat_id'],
            'Phones' => [$record['reg_wechat']],
            'Message' => $strategy['remark'],
            'TaskId' => $record['task_id'],
            'Remark' => $record['Remark'] ?? '',
        ];

        $content = \app\common\workerman\wechat\handlers\client\AddFriendsTaskHandler::handle($message);
        $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
        $message->setMsgType($content['MsgType']);
        $any = new \Google\Protobuf\Any();
        $any->pack($content['Content']);
        $message->setContent($any);
        $pushMessage = $message->serializeToString();

        $channel = "socket.{$wechat['device_code']}.message";
        $this->setLog('channel: ' . $channel, 'msg');

        \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        \Channel\Client::publish($channel, [
            'data' => is_array($pushMessage) ? json_encode($pushMessage) : $pushMessage
        ]);
        //$wechat->add_num += 1;
        $wechat->is_cooling = 0;
        $wechat->cooling_time = 0;
        $wechat->update_time = time();
        $wechat->save();
    }

    private function _setColingWechat(SvAddWechatRecord $record)
    {
        $wechat = AiWechat::where('wechat_id', '=', $record->wechat_no)->limit(1)->find();
        if (!$wechat->isEmpty()) {
            $wechat->is_cooling = 1;
            $wechat->cooling_time = time();
            $wechat->update_time = time();
            $wechat->save();
        }
    }

    private function acceptFriendJob(array $payload)
    {
        $deviceId = $payload['device_code'];
        // 3. 构建消息发送请求
        $content = \app\common\workerman\wechat\handlers\client\AcceptFriendAddRequestTaskHandler::handle([
            'WeChatId' => $payload['wechat_id'],
            'FriendId' => $payload['friend_id'],
            'Operation' => 1,
            'TaskId' => time(),
        ]);

        // 4. 构建protobuf消息
        $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
        $message->setMsgType($content['MsgType']);
        $any = new \Google\Protobuf\Any();
        $any->pack($content['Content']);
        $message->setContent($any);
        $data = $message->serializeToString();

        // 5. 发送到设备端
        $channel = "socket.{$deviceId}.message";
        \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        \Channel\Client::publish($channel, [
            'data' => $data
        ]);
    }
}
