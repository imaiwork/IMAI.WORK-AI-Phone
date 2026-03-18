<?php

namespace app\common\workerman\rpa\handlers\wechat;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDeviceCircleLikeReply;
use app\common\model\sv\SvDeviceCircleLikeReplyAccount;
use app\common\model\sv\SvDeviceCircleLikeReplyRecord;
use app\common\model\kb\KbRobot;
use app\common\workerman\rpa\WorkerEnum;
use app\common\enum\user\AccountLogEnum;
use app\api\logic\service\TokenLogService;
use app\api\logic\ChatLogic;
use app\common\model\user\User;
use app\common\logic\AccountLogLogic;
use app\common\model\sv\SvDevice;

use app\common\model\auto\AutoDeviceCircleLikeReplyConfig;
use app\common\model\wechat\AiWechatLog;



class CircleLikeCommentHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;

            $taskId = $content['taskId'] ?? 0;
            $nickname = $content['nickname'] ?? '';
            $message = $content['content'] ?? '';

            $task = SvDeviceCircleLikeReplyAccount::where('id', $taskId)->findOrEmpty();
            if (!$task->isEmpty()) {

                //验证同一客户在任务中互动数量是否上限
                $setting = SvDeviceCircleLikeReply::where('id', $task->circle_like_reply_id)->findOrEmpty();
                if ($setting->isEmpty()) {
                    $this->setLog('任务配置不存在', 'like');
                    $this->payload['reply'] = array(
                        'type' => 1,
                        'content' => [],
                        'link' => '',
                        'isLike' => 0,
                        'isComment' => 0,
                        'msg' => '任务配置不存在',
                        'targetRecipient' => $content['nickname'] ?? '',
                        'lastMessageContent' => $content['content'] ?? ''
                    );
                    $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                    return;
                }
                $autoConfig = null;
                if ($setting->auto_reply_config_id > 0) {
                    $autoConfig = AutoDeviceCircleLikeReplyConfig::where('id', $setting->auto_reply_config_id)->findOrEmpty();
                    if ($autoConfig->isEmpty()) {
                        $this->setLog('自动回复配置不存在', 'like');
                        $this->payload['reply'] = array(
                            'type' => 1,
                            'content' => [],
                            'link' => '',
                            'isLike' => 0,
                            'isComment' => 0,
                            'msg' => '自动回复配置不存在',
                            'targetRecipient' => $content['nickname'] ?? '',
                            'lastMessageContent' => $content['content'] ?? ''
                        );
                        $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                        return;
                    }
                    $setting->action = ($autoConfig->is_like === 1 && $autoConfig->is_comment === 1) ? 3 : ($autoConfig->is_like === 1 ? 1 : ($autoConfig->is_comment === 1 ? 2 : 0));

                    //判断每天的互动次数
                    $count = SvDeviceCircleLikeReplyRecord::where('user_id', $task->user_id)
                        ->where('device_code', $task->device_code)
                        ->where('auto_type', 1)
                        ->whereBetween('create_time', [strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d 23:59:59', time()))])
                        ->count();
                    if($count >= $autoConfig->number){
                        $this->setLog('互动次数已达上限', 'like');
                        $this->payload['reply'] = array(
                            'type' => 1,
                            'content' => [],
                            'link' => '',
                            'isLike' => 0,
                            'isComment' => 0,
                            'msg' => '互动次数已达上限',
                            'targetRecipient' => $content['nickname'] ?? '',
                            'lastMessageContent' => $content['content'] ?? ''
                        );
                        $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                        return;
                    }
                }


                $hash = hash('sha256', $nickname . $message);
                $record = SvDeviceCircleLikeReplyRecord::where('user_id', $task->user_id)
                    ->where('like_reply_account', $task->id)
                    ->where('device_code', $task->device_code)
                    ->where('account', $task->account)
                    ->where('hash', $hash)
                    ->where('type', $setting->action)
                    ->findOrEmpty();
                if (!$record->isEmpty()) {
                    $this->setLog('重复评论', 'like');
                    $this->payload['reply'] = array(
                        'type' => 1,
                        'content' => [],
                        'link' => '',
                        'isLike' => 0,
                        'isComment' => 0,
                        'msg' => '重复评论',
                        'targetRecipient' => $content['nickname'] ?? '',
                        'lastMessageContent' => $content['content'] ?? ''
                    );
                    $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                    return;
                }

                $count =  SvDeviceCircleLikeReplyRecord::where('user_id', $task->user_id)
                    ->where('like_reply_account', $task->id)
                    ->where('device_code', $task->device_code)
                    ->where('nickname', $nickname)
                    ->where('type', $setting->action)
                    ->count();
                if ($count >= $setting->number) {
                    $this->setLog('互动次数已达上限', 'like');
                    $this->payload['reply'] = array(
                        'type' => 1,
                        'content' => [],
                        'link' => '',
                        'isLike' => 0,
                        'isComment' => 0,
                        'msg' => '互动次数已达上限',
                        'targetRecipient' => $content['nickname'] ?? '',
                        'lastMessageContent' => $content['content'] ?? ''
                    );
                    $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                    return;
                }

                $request_id = generate_unique_task_id();
                $comment = $this->getCircleComment($content['content'], $task, $request_id, $autoConfig);

                SvDeviceCircleLikeReplyRecord::create([
                    'user_id' => $task->user_id,
                    'like_reply_account' => $task->id,
                    'auto_type' => $task->auto_type,
                    'device_code' => $task->device_code,
                    'account' => $task->account,
                    'nickname' => $content['nickname'] ?? '',
                    'content' => $content['content'] ?? '',
                    'comment' => implode(",", $comment),
                    'hash' =>  $hash,
                    'image' => $this->saveBase64ToImage($content['image'] ?? '', $hash, 'wechat'),
                    'task_id' => $request_id,
                    'type' => $setting->action,
                    'create_time' => time(),
                ]);

                $task->status = 1;
                $task->update_time = time();
                $task->save();



                $this->payload['reply'] = array(
                    'type' => 1,
                    'content' => $comment,
                    'link' => '',
                    'isLike' => in_array($setting->action, [1, 3]) ? 1 : 0,
                    'isComment' => in_array($setting->action, [2, 3]) ? 1 : 0,
                    'msg' => '互动成功',
                    'targetRecipient' => $content['nickname'] ?? '',
                    'lastMessageContent' => $content['content'] ?? ''
                );
            } else {
                $this->payload['reply'] = array(
                    'type' => 1,
                    'content' => [],
                    'link' => '',
                    'isLike' => 0,
                    'isComment' => 0,
                    'msg' => '互动失败',
                    'targetRecipient' => $content['nickname'] ?? '',
                    'lastMessageContent' => $content['content'] ?? ''
                );
            }

            $this->payload['code'] =  WorkerEnum::SUCCESS_CODE;
            $this->payload['type'] = 6;
            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'like');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        } finally {
            unset($content);
        }
    }

    private function getCircleComment(string $circleContent, SvDeviceCircleLikeReplyAccount $task, string $request_id, AutoDeviceCircleLikeReplyConfig $autoConfig = null)
    {
        try {
            if (empty($circleContent)) {
                $this->setLog('朋友圈内容为空', 'like');
                return [];
            }

            $option = SvDeviceCircleLikeReply::where('id', $task->circle_like_reply_id)->findOrEmpty();
            if ($option->isEmpty()) {
                $this->setLog('点赞回复选项不存在', 'like');
                return [];
            }


            $replyContent = '';
            if (!is_null($autoConfig) && $option->robot_id == 0) {
                $replyContent = $this->getReplyContentByAuto($autoConfig, $task, $circleContent, $request_id);

                if ($autoConfig->is_like === 1) {
                    $request = [
                        'user_id' => $task->user_id,
                        'task_id' => $request_id,
                        'chat_type' => AccountLogEnum::TOKENS_DEC_AI_WECHAT,
                        'now'       => time(),
                        'action' => 'chat',
                        'is_like' => $autoConfig->is_like,
                        'is_comment' => $autoConfig->is_comment,
                    ];
                    // 处理响应
                    $response = \app\common\service\ToolsService::Automation()->friendsCirclePraise($request);
                    if (isset($response['code']) && $response['code'] == 10000) {
                        $unit = TokenLogService::checkToken($request['user_id'], 'automation_friends_circle_praise');
                        $extra = ['算力单价' => $unit, '实际消耗算力' => $unit, '场景' => '朋友圈点赞'];
                        $desc = AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_PRAISE;
                        //扣费记录
                        AccountLogLogic::recordUserTokensLog(true, $request['user_id'], $desc, (float)$unit, $request_id, $extra);
                    }
                }
            } else {
                if ($option->action !== 1) {
                    $replyContent = $this->getReplyContentByRobbot($option, $task, $circleContent, $request_id);
                }
            }
            return [
                $replyContent
            ];
        } catch (\Throwable $e) {
            $this->setLog('异常信息' . $e->__toString(), 'like');
            return [];
        }
    }

    private function getReplyContentByAuto(AutoDeviceCircleLikeReplyConfig $autoConfig, SvDeviceCircleLikeReplyAccount $task, string $circleContent, string $request_id)
    {

        try {
            TokenLogService::checkToken($task->user_id, '');
            $replyContent = '';
            if ($autoConfig->comment_method === 2 && $autoConfig->is_comment === 1) {
                $replyContent = $autoConfig->comment_speech[array_rand($autoConfig->comment_speech)];

                $tokens = mb_strlen($replyContent) * 5;
                $unit = TokenLogService::checkToken($task->user_id, 'automation_friends_circle_comments');
                //计算消耗tokens
                $points = $unit > 0 ? round($tokens / $unit, 2) : 0;
                //token扣除
                User::userTokensChange($task->user_id, (float)$points);

                $extra = ['总消耗tokens数' => $tokens, '算力单价' => $unit, '实际消耗算力' => $points, '场景' => '朋友圈评论'];
                $desc = AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS;
                //扣费记录
                AccountLogLogic::recordUserTokensLog(true, $task->user_id, $desc, (float)$points, $task->id, $extra);
            }

            if ($autoConfig->comment_method === 1 && $autoConfig->is_comment === 1) {
                $messages = array(
                    array(
                        'role' => 'system',
                        'content' => empty($autoConfig->comment_robot_prompt) ? '你是一个乐意助人的助手' : $autoConfig->comment_robot_prompt,
                    ),
                    array(
                        'role' => 'user',
                        'content' => $circleContent,
                    ),
                );
                $option = $autoConfig->robot_params ?? [];
                $request = [
                    'messages' => $messages,
                    'message' => $autoConfig->comment_robot_prompt,
                    'model' => $option['model'] ?? 'gpt-4o',
                    'top_p' => $option['top_p'] ?? 0.8,
                    'stream' => $option['stream'] ?? false,
                    'max_tokens' => $option['max_tokens'] ?? 4096,
                    'context_num' => $option['context_num'] ?? 0,
                    'temperature' => $option['temperature'] ?? 0.3,
                    'presence_penalty' => $option['presence_penalty'] ?? 0,
                    'frequency_penalty' => $option['frequency_penalty'] ?? 0,
                    'user_id' => $task->user_id,
                    'task_id' => $request_id,
                    'chat_type' => AccountLogEnum::TOKENS_DEC_AI_WECHAT,
                    'now'       => time(),
                    'action' => 'chat',
                    'is_like' => $autoConfig->is_like,
                    'is_comment' => $autoConfig->is_comment,
                ];
                $this->setLog('自动参数: ' . json_encode($request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'like');
                $response = \app\common\service\ToolsService::Automation()->friendsCircleComments($request);
                if (isset($response['code']) && $response['code'] == 10000) {
                    // 处理响应
                    $replyContent = $this->handleResponse($response, $request, 1);
                } else {
                    $this->setLog('队列请求知识库失败: ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'like');
                    return '';
                }
            }

            return $replyContent;
        } catch (\Throwable $th) {
            $this->setLog('getReplyContentByAuto异常信息' . $th->__toString(), 'like');
            return '';
        }
    }

    private function getReplyContentByRobbot(SvDeviceCircleLikeReply $option, SvDeviceCircleLikeReplyAccount $task, string $circleContent, string $request_id)
    {
        try {
            $robot = KbRobot::where('id', $option->robot_id)->findOrEmpty();

            $replyContent = '';
            if ($robot->isEmpty()) {
                $this->setLog('点赞回复机器人不存在', 'like');
                return '';
            }
            $knowledge = [];
            if ($robot->kb_type == 1) { //rag
                // 检查是否挂载知识库
                $bind = \app\common\model\knowledge\KnowledgeBind::where('data_id', $robot->id)->where('user_id', $task->user_id)->where('type', 1)->limit(1)->find();
                if (!empty($bind)) {
                    $bindFind = \app\common\model\knowledge\Knowledge::where('id', $bind['kid'])->limit(1)->find();
                    if (empty($bindFind)) {
                        $this->setLog('挂载知识库不存在', 'like');
                        return '';
                    } else {
                        $knowledge = $bindFind->toArray();
                    }
                }
            }

            if ($robot->kb_type == 2) { //向量
                // 检查是否挂载知识库
                $bind = \app\common\model\knowledge\KnowledgeBind::where('data_id', $robot->id)->where('user_id', $task->user_id)->where('type', 1)->limit(1)->find();
                if (!empty($bind)) {
                    $bindFind = \app\common\model\kb\KbKnow::where('id', $bind['kid'])->limit(1)->find();
                    if (empty($bindFind)) {
                        $this->setLog('挂载知识库不存在', 'like');
                        return '';
                    } else {
                        $knowledge = $bindFind->toArray();
                    }
                }
            }

            $messages = array(
                array(
                    'role' => 'system',
                    'content' => empty($robot->roles_prompt) ? '你是一个乐意助人的助手' : $robot->roles_prompt,
                ),
                array(
                    'role' => 'user',
                    'content' => $circleContent,
                ),

            );

            $this->setLog(json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'like');

            if (!empty($knowledge) || $robot->kb_type == 2) {
                [$chatStatus, $response] = \app\api\logic\KnowledgeLogic::socketChat([
                    'message' => $circleContent,
                    'messages' => $messages,
                    'indexid' => $knowledge['index_id'] ?? '',
                    'rerank_min_score' => $knowledge['rerank_min_score'] ?? 0.2,
                    'stream' => false,
                    'user_id' => $task->user_id,
                    'scene' => '评论朋友圈聊天',
                    'model' => $robot->model,
                    'robot' => $robot->toArray(),
                    'kb_id' => $robot->kb_ids ? explode(',', $robot->kb_ids) : [],
                ]);
                if ($chatStatus === false) {
                    $this->setLog('队列请求知识库失败: ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'like');
                    return '';
                } else {
                    if (isset($response['choices'][0]) && !empty($response['choices'][0])) {
                        $replyContent =  $response['choices'][0]['message']['content'];
                    }
                }
            } else {
                $request = [
                    'messages' => $messages,
                    'message' => $robot->roles_prompt,
                    'model' => $robot->model,
                    'stream' => false,
                    'user_id' => $task->user_id,
                    'task_id' => $request_id,
                    'chat_type' => AccountLogEnum::TOKENS_DEC_AI_WECHAT,
                    'now'       => time(),
                    'action' => 'chat',
                    'is_like' => ($option->action === 1 || $option->action === 3) ? 1 : 0,
                    'is_comment' => ($option->action === 2 || $option->action === 3) ? 1 : 0,
                ];
                $this->setLog('请求参数: ' . json_encode($request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'like');
                $autoType = SvDevice::where('device_code', $task->device_code)->value('auto_type') ?? 0;
                if ($autoType == 0) {
                    // 执行微信AI消息处理
                    $response = \app\common\service\ToolsService::Wechat()->chat($request);
                } else {
                    // 执行自动化消息处理
                    $response = \app\common\service\ToolsService::Automation()->friendsCircleComments($request);
                }
                if (isset($response['code']) && $response['code'] == 10000) {
                    // 处理响应
                    $replyContent = $this->handleResponse($response, $request, $autoType);
                } else {
                    // 重试

                    $this->setLog('队列请求知识库失败: ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'like');
                    return '';
                }
            }
            return $replyContent;
        } catch (\Throwable $th) {
            $this->setLog('getReplyContentByRobbot异常信息' . $th->__toString(), 'like');
            return '';
        }
    }

    private function handleResponse(array $response, array $request, $autoType)
    {
        try {
            if ($autoType == 0) {
                $scene = $request['model'] == 'deepseek' ? 'ai_reply_like' : 'openai_chat';
            } else {
                $scene = 'automation_friends_circle_comments';
            }

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
            if ($autoType == 0) {
                $desc = $request['model'] == 'deepseek' ? AccountLogEnum::TOKENS_DEC_AI_REPLY_LIKE : AccountLogEnum::TOKENS_DEC_OPENAI_CHAT;
            } else {
                $desc = AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS;
            }
            //扣费记录
            AccountLogLogic::recordUserTokensLog(true, $request['user_id'], $desc, (float)$points, $request['task_id'], $extra);

            return $reply;
        } catch (\Throwable $th) {
            $this->setLog('handleResponse异常信息' . $th->__toString(), 'like');
            return '';
        }
    }
}
