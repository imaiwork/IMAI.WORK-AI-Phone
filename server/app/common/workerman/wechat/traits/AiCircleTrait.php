<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\traits;

use app\common\model\wechat\AiWechatDevice;
use app\common\model\wechat\AiWechatLog;
use app\common\model\wechat\AiWechatCircleReplyLikeStrategy;
use app\common\model\wechat\AiWechatFriendTag;
use app\common\model\wechat\AiWechatRobot;
use app\common\model\kb\KbRobot;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\api\logic\ChatLogic;
use app\common\model\user\User;
use app\common\logic\AccountLogLogic;

/**
 * 自动微信朋友圈点赞评论
 *
 * @author my
 * @package app\traits
 */
trait AiCircleTrait
{
    public function circleReplyLikeTask(string $deviceId, array $response): void
    {
        try {
            $data = $response['Data'];

            // $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('circleReplyLikeTask')->withContext([
            //     'msg' => 'circleReplyLikeTask',
            //     'response' => $response
            // ])->log();

            if ($data['MsgType'] == 'CirclePushNotice') {
                $device = AiWechatDevice::where('device_code', $deviceId)->findOrEmpty();
                if ($device->isEmpty()) {
                    return;
                }
                $wechatid = $data['Content']['WeChatId'];

                $strategy = AiWechatCircleReplyLikeStrategy::where('user_id', $device->user_id)->findOrEmpty();
                if ($strategy->isEmpty()) {
                    $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Data')->withContext([
                        'msg' => '评论点赞策略为空'
                    ])->log();
                    return;
                }
                $replyTagIds = $strategy->reply_tag_ids;
                $likeTagIds = $strategy->like_tag_ids;
                $likeFriends = [];
                if (!empty($likeTagIds)) {
                    $likeFriends = AiWechatFriendTag::where('wechat_id', $wechatid)->where('tag_id', 'in', $likeTagIds)->group('friend_id')->column('friend_id');
                }

                $replyFriends = [];
                if (!empty($replyTagIds)) {
                    $replyFriends = AiWechatFriendTag::where('wechat_id', $wechatid)->where('tag_id', 'in', $replyTagIds)->group('friend_id')->column('friend_id');
                }

                $circles = $data['Content']['Circles'];
                foreach ($circles as $circle) {
                    $PublishTime = $circle['PublishTime'];
                    if (date('Ymd', time()) != date('Ymd', (int)$PublishTime)) {
                        continue;
                    }
                    $difftime = (time() - (int)$PublishTime) / 60;
                    if ($strategy->is_enable_like === 1) {
                        if ($difftime > $strategy->like_interval_time) {
                            $this->runCircleLikeTask($device, $wechatid, $circle, $strategy, $likeFriends);
                        }
                    }

                    if ($strategy->is_enable_reply === 1) {
                        if ($difftime > $strategy->reply_interval_time) {
                            $this->runCircleReplyTask($device, $wechatid, $circle, $strategy, $replyFriends);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('circleReplyLikeTask')->withContext([
                'msg' => 'circleReplyLikeTask',
                'error' => $e->__toString()
            ])->log();
        }
    }

    private function runCircleReplyTask(AiWechatDevice $device, string $wechatid, array $circle, AiWechatCircleReplyLikeStrategy $strategy, array $replyFriends): void
    {
        try {
            $deviceId = $device->device_code;
            if (!empty($replyFriends) && !in_array($circle['WeChatId'], $replyFriends)) {
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Reply Log')->withContext([
                    'msg' => '非客户微信设定评论好友',
                    'CircleId' => $circle['CircleId'],
                    'wechat_id' => $wechatid,
                    'friend_id' => $circle['WeChatId'],
                ])->log();
                return;
            }

            $comments = $circle['Comments'];
            $commentFriendId = array_column($comments, 'FromWeChatId');
            if (in_array($wechatid, $commentFriendId)) {
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Reply Log')->withContext([
                    'msg' => '已经评论了',
                    'CircleId' => $circle['CircleId'],
                    'wechat_id' => $wechatid,
                    'friend_id' => $circle['WeChatId'],
                ])->log();
                return;
            }
            // $optLog = AiWechatLog::where('user_id', $device->user_id)
            //     ->where('wechat_id', $wechatid)
            //     ->where('friend_id', $circle['WeChatId'])
            //     ->where('log_type', AiWechatLog::TYPE_REPLY_CIRCLE)
            //     ->order('id desc')
            //     ->findOrEmpty();
            // if (!$optLog->isEmpty()) {
            //     $difftime = (time() - strtotime($optLog->create_time)) / 60;
            //     if ($difftime < $strategy->reply_interval_time) {
            //         $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Reply Log')->withContext([
            //             'msg' => '评论间隔时间太短了',
            //             'CircleId' => $circle['CircleId'],
            //             'wechat_id' => $wechatid,
            //             'friend_id' => $circle['WeChatId'],
            //         ])->log();
            //         return;
            //     }
            // }

            $optCount =  AiWechatLog::where('user_id', $device->user_id)
                ->where('wechat_id', $wechatid)
                ->where('friend_id', $circle['WeChatId'])
                ->where('log_type', AiWechatLog::TYPE_REPLY_CIRCLE)
                ->where('create_time', 'between', [strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d 23:59:59', time()))])
                ->count();
            if ($optCount >= $strategy->reply_numbers) {
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Reply Log')->withContext([
                    'msg' => '每日评论数超限了',
                    'CircleId' => $circle['CircleId'],
                    'wechat_id' => $wechatid,
                    'friend_id' => $circle['WeChatId'],
                ])->log();

                $key = "circle_reply_max_strategy";
                $this->setRedisStrategy($key, $strategy->id);
                return;
            }

            if (empty($circle['Content']['Text'])) {
                return;
            }

            $replyContent = $this->createReplyContent($circle, $device->user_id, $strategy);
            if (empty($replyContent)) {
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Reply Log')->withContext([
                    'msg' => 'ai生成评论失败',
                    'CircleId' => $circle['CircleId'],
                    'wechat_id' => $wechatid,
                    'friend_id' => $circle['WeChatId'],
                    'strategy_id' => $strategy->id,

                ])->log();
                return;
            }

            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Reply Log')->withContext([
                'replyContent' => $replyContent,
                'CircleId' => $circle['CircleId'],
                'wechat_id' => $wechatid,
                'friend_id' => $circle['WeChatId'],
                'strategy_id' => $strategy->id,
    
            ])->log();

            $this->execReplyCircle($deviceId, $wechatid, $circle, $replyContent);

            AiWechatLog::create([
                'user_id'   => $device->user_id,
                'wechat_id' => $wechatid,
                'friend_id' => $circle['WeChatId'],
                'log_type'      => AiWechatLog::TYPE_REPLY_CIRCLE,
                'create_time' => time()
            ]);
        } catch (\Throwable $e) {
            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('runCircleReplyTask')->withContext([
                'msg' => 'runCircleReplyTask',
                'error' => $e->__toString()
            ])->log();
        }
    }

    private function createReplyContent(array $circle, int $user_id, AiWechatCircleReplyLikeStrategy $strategy): string

    {
        try {
            $replyContent = '';
            $robot = KbRobot::where('id', $strategy->reply_robot_id)->findOrEmpty();
            if ($robot->isEmpty()) {
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('createReplyContent')->withContext([
                    'msg' => '没有设定评论机器人',
                    'user_id' => $user_id,
                    'strategy' => $strategy->id,
                    'reply_robot_id' => $strategy->reply_robot_id
                ])->log();
                return '';
            }
            $knowledge = [];
            if ($robot->kb_type == 1) { //rag
                // 检查是否挂载知识库
                $bind = \app\common\model\knowledge\KnowledgeBind::where('data_id', $robot->id)->where('user_id', $user_id)->where('type', 1)->limit(1)->find();
                if (!empty($bind)) {
                    $bindFind = \app\common\model\knowledge\Knowledge::where('id', $bind['kid'])->limit(1)->find();
                    if (empty($bindFind)) {
                        $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('createReplyContent')->withContext([
                            'type' => 'rag',
                            'msg' => '挂载知识库不存在',
                            'user_id' => $user_id,
                            'strategy' => $strategy->id,
                            'reply_robot_id' => $strategy->reply_robot_id
                        ])->log();
                        return '';
                    } else {
                        $knowledge = $bindFind->toArray();
                    }
                }
            }

            if ($robot->kb_type == 2) { //向量
                // 检查是否挂载知识库
                $bind = \app\common\model\knowledge\KnowledgeBind::where('data_id', $robot->id)->where('user_id', $user_id)->where('type', 1)->limit(1)->find();
                if (!empty($bind)) {
                    $bindFind = \app\common\model\kb\KbKnow::where('id', $bind['kid'])->limit(1)->find();
                    if (empty($bindFind)) {
                        $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('createReplyContent')->withContext([
                            'type' => 'vector',
                            'msg' => '挂载知识库不存在',
                            'user_id' => $user_id,
                            'strategy' => $strategy->id,
                            'reply_robot_id' => $strategy->reply_robot_id
                        ])->log();
                        return '';
                    } else {
                        $knowledge = $bindFind->toArray();
                    }
                }
            }



            $messages = array(
                array(
                    'role' => 'system',
                    'content' => $robot->roles_prompt,
                ),
                array(
                    'role' => 'user',
                    'content' => $circle['Content']['Text'],
                ),

            );

            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Messages List:')->withContext([
                'response' => $messages
            ])->log();

            if (!empty($knowledge) || $robot->kb_type == 2) {
                [$chatStatus, $response] = \app\api\logic\KnowledgeLogic::socketChat([
                    'message' => $circle['Content']['Text'],
                    'messages' => $messages,
                    'indexid' => $knowledge['index_id'] ?? '',
                    'rerank_min_score' => $knowledge['rerank_min_score'] ?? 0.2,
                    'stream' => false,
                    'user_id' => $user_id,
                    'scene' => '评论朋友圈聊天',
                    'model' => $robot->model,
                    'robot' => $robot->toArray(),
                ]);
                if ($chatStatus === false) {
                    $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('队列请求知识库失败:')->withContext([
                        'response' => $response
                    ])->log();
                    return '';
                } else {
                    if (isset($response['choices'][0]) && !empty($response['choices'][0])) {
                        $replyContent =  $response['choices'][0]['message']['content'];
                    }
                }
            } else {
                $request = [
                    'messages' => $messages,
                    'message' => $strategy->reply_prompt,
                    'model' => $robot->model,
                    'stream' => false,
                    'user_id' => $user_id,
                    'task_id' => generate_unique_task_id(),
                    'chat_type' => AccountLogEnum::TOKENS_DEC_AI_WECHAT,
                    'now'       => time(),
                ];
                // 执行微信AI消息处理
                $response = \app\common\service\ToolsService::Wechat()->chat($request);
                if (isset($response['code']) && $response['code'] == 10000) {
                    // 处理响应
                    $replyContent = $this->handleResponse($response, $request);
                } else {
                    // 重试
                    $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('chat 错误')->withContext([
                        'response' => $response,
                        'request' => $request,
                    ])->log();
                    return '';
                }
            }

            return $replyContent;
        } catch (\Throwable $e) {
            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('createReplyContent')->withContext([
                'msg' => 'runCircleReplyTask',
                'error' => $e->__toString()
            ])->log();
            return '';
        }
    }

    private function handleResponse(array $response, array $request)
    {
        $scene = $request['model'] == 'deepseek' ? 'ai_reply_like' : 'openai_chat';

        //检查扣费
        $unit = TokenLogService::checkToken($request['user_id'], $scene);
        // 获取回复内容
        $reply = $response['data']['message'] ?? '';

        //计费
        $tokens = $response['data']['usage']['total_tokens'] ?? 0;
        if (!$reply || $tokens == 0) {
            throw new \Exception('获取内容失败');
        }

        $response = [
            'reply' => $reply,
            'usage_tokens' => $response['data']['usage'] ?? [],
        ];

        // 保存聊天记录
        ChatLogic::saveChatResponseLog($request, $response);

        //计算消耗tokens
        $points = $unit > 0 ? round($tokens / $unit, 2) : 0;
        //token扣除
        User::userTokensChange($request['user_id'], (float)$points);

        $extra = ['总消耗tokens数' => $tokens, '算力单价' => $unit, '实际消耗算力' => $points, '场景' => '朋友圈评论'];
        $desc = $request['model'] == 'deepseek' ? AccountLogEnum::TOKENS_DEC_AI_REPLY_LIKE : AccountLogEnum::TOKENS_DEC_OPENAI_CHAT;
        //扣费记录
        AccountLogLogic::recordUserTokensLog(true, $request['user_id'], $desc, (float)$points, $request['task_id'], $extra);

        return $reply;
    }

    private function runCircleLikeTask(AiWechatDevice $device, string $wechatid, array $circle, AiWechatCircleReplyLikeStrategy $strategy, array $likeFriends): void
    {
        try {
            $deviceId = $device->device_code;
            if (!empty($likeFriends) && !in_array($circle['WeChatId'], $likeFriends)) {
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Like Log')->withContext([
                    'msg' => '非客户微信设定标签好友',
                    'CircleId' => $circle['CircleId'],
                    'wechat_id' => $wechatid,
                    'friend_id' => $circle['WeChatId'],
                ])->log();
                return;
            }

            $likes = $circle['Likes'];
            $likeFriendId = array_column($likes, 'FriendId');
            if (in_array($wechatid, $likeFriendId)) {
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Like Log')->withContext([
                    'msg' => '已经点赞了',
                    'CircleId' => $circle['CircleId'],
                    'wechat_id' => $wechatid,
                    'friend_id' => $circle['WeChatId'],
                ])->log();
                return;
            }
            // $optLog = AiWechatLog::where('user_id', $device->user_id)
            //     ->where('wechat_id', $wechatid)
            //     ->where('friend_id', $circle['WeChatId'])
            //     ->where('log_type', AiWechatLog::TYPE_LIKE_CIRCLE)
            //     ->order('id desc')
            //     ->findOrEmpty();
            // if (!$optLog->isEmpty()) {
            //     $difftime = (time() - strtotime($optLog->create_time)) / 60;
            //     if ($difftime < $strategy->like_interval_time) {
            //         $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Like Log')->withContext([
            //             'msg' => '点赞间隔时间太短了',
            //             'CircleId' => $circle['CircleId'],
            //             'wechat_id' => $wechatid,
            //             'friend_id' => $circle['WeChatId'],
            //         ])->log();
            //         return;
            //     }
            // }

            $optCount =  AiWechatLog::where('user_id', $device->user_id)
                ->where('wechat_id', $wechatid)
                ->where('friend_id', $circle['WeChatId'])
                ->where('log_type', AiWechatLog::TYPE_LIKE_CIRCLE)
                ->where('create_time', 'between', [strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d 23:59:59', time()))])
                ->count();
            if ($optCount >= $strategy->like_numbers) {
                $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('Like Log')->withContext([
                    'msg' => '每日点赞数超限了',
                    'CircleId' => $circle['CircleId'],
                    'wechat_id' => $wechatid,
                    'friend_id' => $circle['WeChatId'],
                ])->log();
                $key = "circle_like_max_strategy";
                $this->setRedisStrategy($key, $strategy->id);
                return;
            }

            $this->execLikeCircle($deviceId, $wechatid, $circle['CircleId']);
            AiWechatLog::create([
                'user_id'   => $device->user_id,
                'wechat_id' => $wechatid,
                'friend_id' => $circle['WeChatId'],
                'log_type'      => AiWechatLog::TYPE_LIKE_CIRCLE,
                'create_time' => time()
            ]);
        } catch (\Throwable $e) {
            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('runCircleLikeTask')->withContext([
                'msg' => 'runCircleLikeTask',
                'error' => $e->__toString()
            ])->log();
        }
    }

    private function execReplyCircle(string $deviceId, string $wechatid, array $circle, string $prompt): void
    {
        try {
            // 3. 构建消息发送请求
            $content = \app\common\workerman\wechat\handlers\client\CircleCommentReplyTaskHandler::handle([
                'WeChatId' => $wechatid,
                'ToWeChatId' => $circle['WeChatId'],
                'CircleId' => $circle['CircleId'],
                'Content' => $prompt,
                'IsResend' => false,
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
        } catch (\Exception $e) {
            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('execReplyCircle')->withContext([
                'msg' => 'execReplyCircle',
                'error' => $e->__toString()
            ])->log();
        }
    }

    private function execLikeCircle(string $deviceId, string $wechatid, string $circleId): void
    {
        try {
            // 3. 构建消息发送请求
            $content = \app\common\workerman\wechat\handlers\client\CircleLikeTaskHandler::handle([
                'WeChatId' => $wechatid,
                'CircleId' => $circleId,
                'IsCancel' => 0,
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
        } catch (\Exception $e) {
            $this->withChannel('wechat_socket')->withLevel('notice')->withTitle('execLikeCircle')->withContext([
                'msg' => 'execLikeCircle',
                'error' => $e->__toString()
            ])->log();
        }
    }

    private function setRedisStrategy(string $key, int $strategy_id): void
    {
        \think\facade\Cache::store('redis')->select(env('redis.WX_SELECT', 8));
        \think\facade\Cache::store('redis')->sAdd($key, $strategy_id);
        // 设置过期时间为到次日凌晨的剩余秒数
        $expireTime = strtotime(date('Y-m-d 23:59:59', time())) - time() + 1; // +1确保跨天
        \think\facade\Cache::store('redis')->expire($key, $expireTime);
    }
}
