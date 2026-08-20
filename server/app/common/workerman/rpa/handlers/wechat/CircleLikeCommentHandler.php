<?php

namespace app\common\workerman\rpa\handlers\wechat;

use app\api\logic\ChatLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersonaWechatInteractionConfig;
use app\common\model\kb\KbRobot;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceCircleLikeReply;
use app\common\model\sv\SvDeviceCircleLikeReplyAccount;
use app\common\model\sv\SvDeviceCircleLikeReplyRecord;
use app\common\model\user\User;
use app\common\service\chat\ChatBillingService;
use app\common\service\sv\CircleInteractionActionService;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;

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
                    $this->payload['reply'] = $this->emptyReply($content, '任务配置不存在');
                    $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                    return;
                }

                $autoConfig = null;
                if ((int)$setting->auto_reply_config_id > 0) {
                    $autoConfig = AiPersonaWechatInteractionConfig::where('id', $setting->auto_reply_config_id)->findOrEmpty();
                    if ($autoConfig->isEmpty()) {
                        $liveFlags = CircleInteractionActionService::loadLiveFlags(
                            (int)$setting->auto_reply_config_id,
                            (int)($setting->persona_id ?? 0)
                        );
                        if ($liveFlags === null) {
                            $this->setLog('自动回复配置不存在', 'like');
                            $this->payload['reply'] = $this->emptyReply($content, '自动回复配置不存在');
                            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                            return;
                        }
                        $autoConfig = null;
                    } else {
                        //判断每天的互动次数（人设互动管家）
                        $count = SvDeviceCircleLikeReplyRecord::where('user_id', $task->user_id)
                            ->where('device_code', $task->device_code)
                            ->where('auto_type', 1)
                            ->whereBetween('create_time', [strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d 23:59:59', time()))])
                            ->count();
                        if ($count >= $autoConfig->number) {
                            $this->setLog('互动次数已达上限', 'like');
                            $this->payload['reply'] = $this->emptyReply($content, '互动次数已达上限');
                            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                            return;
                        }
                    }
                }

                // 优先 live 配置，回退任务快照 action（口径：1仅点赞 2仅评论 3点赞+评论）
                $deviceFlags = CircleInteractionActionService::resolveDeviceFlagsFromOption($setting);
                $action = (int)$deviceFlags['action'];
                if ($action === CircleInteractionActionService::ACTION_NONE) {
                    $this->setLog('未开启点赞或评论', 'like');
                    $this->payload['reply'] = $this->emptyReply($content, '未开启点赞或评论');
                    $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                    return;
                }

                $hash = hash('sha256', $nickname . $message);
                $record = SvDeviceCircleLikeReplyRecord::where('user_id', $task->user_id)
                    ->where('like_reply_account', $task->id)
                    ->where('device_code', $task->device_code)
                    ->where('account', $task->account)
                    ->where('hash', $hash)
                    ->where('type', $action)
                    ->findOrEmpty();
                if (!$record->isEmpty()) {
                    $this->setLog('重复评论', 'like');
                    $this->payload['reply'] = $this->emptyReply($content, '重复评论');
                    $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                    return;
                }

                $count = SvDeviceCircleLikeReplyRecord::where('user_id', $task->user_id)
                    ->where('like_reply_account', $task->id)
                    ->where('device_code', $task->device_code)
                    ->where('nickname', $nickname)
                    ->where('type', $action)
                    ->count();
                if ($count >= $setting->number) {
                    $this->setLog('互动次数已达上限', 'like');
                    $this->payload['reply'] = $this->emptyReply($content, '互动次数已达上限');
                    $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                    return;
                }

                $request_id = generate_unique_task_id();
                $circleContent = (string)($content['content'] ?? '');
                // 仅评论且正文为空：无法生成评论，整单失败且不扣费
                if ($deviceFlags['hasComment'] === 1 && $deviceFlags['hasLiked'] !== 1 && trim($circleContent) === '') {
                    $this->setLog('朋友圈内容为空，仅评论无法执行', 'like');
                    $this->payload['reply'] = $this->emptyReply($content, '朋友圈内容为空');
                    $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                    return;
                }

                $comment = $this->getCircleComment(
                    $circleContent,
                    $task,
                    $request_id,
                    $autoConfig,
                    $deviceFlags,
                    $setting
                );

                // 评论开启但未产出内容：仅评论失败；双开则降级为仅点赞回包
                if ($deviceFlags['hasComment'] === 1 && empty($comment)) {
                    if ($deviceFlags['hasLiked'] !== 1) {
                        $this->setLog('评论内容生成失败', 'like');
                        $this->payload['reply'] = $this->emptyReply($content, '评论内容生成失败');
                        $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
                        return;
                    }
                    $deviceFlags['hasComment'] = 0;
                    $deviceFlags['is_comment'] = 0;
                    $action = CircleInteractionActionService::ACTION_LIKE;
                    $deviceFlags['action'] = $action;
                }

                SvDeviceCircleLikeReplyRecord::create([
                    'user_id' => $task->user_id,
                    'like_reply_account' => $task->id,
                    'auto_type' => $task->auto_type,
                    'device_code' => $task->device_code,
                    'account' => $task->account,
                    'nickname' => $content['nickname'] ?? '',
                    'content' => $content['content'] ?? '',
                    'comment' => implode(",", $comment),
                    'hash' => $hash,
                    'image' => $this->toolUtil->saveBase64ToImage($content['image'] ?? '', $hash, 'wechat'),
                    'task_id' => $request_id,
                    'type' => $action,
                    'create_time' => time(),
                ]);

                $task->status = 1;
                $task->update_time = time();
                $task->save();

                $this->payload['reply'] = array(
                    'type' => 1,
                    'content' => $comment,
                    'link' => '',
                    'isLike' => $deviceFlags['hasLiked'],
                    'isComment' => $deviceFlags['hasComment'],
                    'msg' => '互动成功',
                    'targetRecipient' => $content['nickname'] ?? '',
                    'lastMessageContent' => $content['content'] ?? ''
                );
            } else {
                // 任务不存在时不演示双开，避免误触发点赞/评论
                $this->payload['reply'] = $this->emptyReply($content, '任务不存在');
            }

            $this->payload['code'] = WorkerEnum::SUCCESS_CODE;
            $this->payload['type'] = 6;
            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'like');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] = WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection, $this->payload);
        } finally {
            unset($content);
        }
    }

    /**
     * @param array{hasLiked:int,hasComment:int,is_like:int,is_comment:int,action?:int} $deviceFlags
     */
    private function getCircleComment(
        string $circleContent,
        SvDeviceCircleLikeReplyAccount $task,
        string $request_id,
        ?AiPersonaWechatInteractionConfig $autoConfig,
        array $deviceFlags,
        SvDeviceCircleLikeReply $option
    ): array {
        try {
            if ($deviceFlags['hasComment'] !== 1 && $deviceFlags['hasLiked'] !== 1) {
                return [];
            }

            $canComment = $deviceFlags['hasComment'] === 1 && trim($circleContent) !== '';
            if ($deviceFlags['hasComment'] === 1 && !$canComment) {
                $this->setLog('朋友圈内容为空，跳过评论生成', 'like');
            }

            $replyContent = '';
            $commentType = (int)($option->comment_type ?? 0);
            $robotId = (int)($option->robot_id ?? 0);
            $personaId = (int)($option->persona_id ?? 0);

            // 人设新任务：comment_type=2 固定话术，或 robot_id>0 智能体（来自 agent_config.moments_*）
            if ($personaId > 0 && ($commentType === 2 || $robotId > 0)) {
                if ($canComment) {
                    if ($commentType === 2) {
                        $replyContent = $this->getReplyContentByMomentsSpeech($option, $task);
                    } else {
                        $replyContent = $this->getReplyContentByRobbot($option, $task, $circleContent, $request_id, $deviceFlags);
                    }
                }
                if ($deviceFlags['hasLiked'] === 1) {
                    $this->chargeFriendsCirclePraise(
                        (int)$task->user_id,
                        $request_id,
                        $canComment && $replyContent !== ''
                    );
                }
            } elseif ($personaId > 0 && $deviceFlags['hasComment'] !== 1 && $deviceFlags['hasLiked'] === 1) {
                // 人设仅点赞（无评论、无 robot）：不走旧 prompt，只记点赞费
                $this->chargeFriendsCirclePraise((int)$task->user_id, $request_id, false);
            } elseif (!is_null($autoConfig) && $robotId === 0) {
                // 历史任务回退：旧 interaction prompt / 固定话术路径
                if ($canComment) {
                    $replyContent = $this->getReplyContentByAuto($autoConfig, $task, $circleContent, $request_id);
                }
                if ($deviceFlags['hasLiked'] === 1) {
                    $this->chargeFriendsCirclePraise(
                        (int)$task->user_id,
                        $request_id,
                        $canComment && $replyContent !== ''
                    );
                }
            } else {
                if ($canComment) {
                    $replyContent = $this->getReplyContentByRobbot($option, $task, $circleContent, $request_id, $deviceFlags);
                }
            }

            return $replyContent === '' ? [] : [$replyContent];
        } catch (\Throwable $e) {
            $this->setLog('异常信息' . $e->__toString(), 'like');
            return [];
        }
    }

    private function chargeFriendsCirclePraise(int $userId, string $requestId, bool $hasComment): void
    {
        $request = [
            'user_id' => $userId,
            'task_id' => $requestId,
            'chat_type' => AccountLogEnum::TOKENS_DEC_AI_WECHAT,
            'now' => time(),
            'action' => 'chat',
            'is_like' => 1,
            'is_comment' => $hasComment ? 1 : 0,
        ];
        $response = \app\common\service\ToolsService::Automation()->friendsCirclePraise($request);
        if (isset($response['code']) && $response['code'] == 10000) {
            $unit = TokenLogService::checkToken($request['user_id'], 'automation_friends_circle_praise');
            if ($unit > 0) {
                User::userTokensChange($request['user_id'], (float)$unit);
                $extra = ['算力单价' => $unit, '实际消耗算力' => $unit, '场景' => '朋友圈点赞'];
                $desc = AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_PRAISE;
                AccountLogLogic::recordUserTokensLog(true, $request['user_id'], $desc, (float)$unit, $requestId, $extra);
            }
        }
    }

    /**
     * 固定话术：优先任务快照 comment(JSON)，否则 live moments_speech
     */
    private function getReplyContentByMomentsSpeech(
        SvDeviceCircleLikeReply $option,
        SvDeviceCircleLikeReplyAccount $task
    ): string {
        try {
            $speech = CircleInteractionActionService::normalizeMomentsSpeech($option->comment ?? '');
            if (empty($speech) && (int)($option->persona_id ?? 0) > 0) {
                $agentConfig = CircleInteractionActionService::loadPersonaMomentsConfig((int)$option->persona_id);
                if ($agentConfig !== null) {
                    $speech = CircleInteractionActionService::normalizeMomentsSpeech($agentConfig->moments_speech);
                }
            }
            if (empty($speech)) {
                $this->setLog('朋友圈固定话术为空', 'like');
                return '';
            }

            $replyContent = $speech[array_rand($speech)];
            $tokens = mb_strlen($replyContent) * 5;
            $unit = TokenLogService::checkToken($task->user_id, 'automation_friends_circle_comments');
            $points = $unit > 0 ? round($tokens / $unit, 2) : 0;
            User::userTokensChange($task->user_id, (float)$points);

            $extra = ['总消耗tokens数' => $tokens, '算力单价' => $unit, '实际消耗算力' => $points, '场景' => '朋友圈评论'];
            $desc = AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS;
            AccountLogLogic::recordUserTokensLog(true, $task->user_id, $desc, (float)$points, $task->id, $extra);

            return $replyContent;
        } catch (\Throwable $th) {
            $this->setLog('getReplyContentByMomentsSpeech异常信息' . $th->__toString(), 'like');
            return '';
        }
    }

    private function getReplyContentByAuto(
        AiPersonaWechatInteractionConfig $autoConfig,
        SvDeviceCircleLikeReplyAccount $task,
        string $circleContent,
        string $request_id
    ): string {
        try {
            TokenLogService::checkToken($task->user_id, '');
            $replyContent = '';
            $flags = CircleInteractionActionService::normalizeFlags($autoConfig->is_like, $autoConfig->is_comment);
            if ($flags['is_comment'] !== 1) {
                return '';
            }

            if ((int)$autoConfig->comment_method === 2) {
                $speech = $autoConfig->comment_speech;
                if (!is_array($speech) || empty($speech)) {
                    return '';
                }
                $replyContent = $speech[array_rand($speech)];

                $tokens = mb_strlen($replyContent) * 5;
                $unit = TokenLogService::checkToken($task->user_id, 'automation_friends_circle_comments');
                $points = $unit > 0 ? round($tokens / $unit, 2) : 0;
                User::userTokensChange($task->user_id, (float)$points);

                $extra = ['总消耗tokens数' => $tokens, '算力单价' => $unit, '实际消耗算力' => $points, '场景' => '朋友圈评论'];
                $desc = AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS;
                AccountLogLogic::recordUserTokensLog(true, $task->user_id, $desc, (float)$points, $task->id, $extra);
            }

            if ((int)$autoConfig->comment_method === 1) {
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
                    'now' => time(),
                    'action' => 'chat',
                    'is_like' => $flags['is_like'],
                    'is_comment' => 1,
                ];
                $this->setLog('自动参数: ' . json_encode($request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'like');
                $response = \app\common\service\ToolsService::Automation()->friendsCircleComments($request);
                if (isset($response['code']) && $response['code'] == 10000) {
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

    /**
     * @param array{hasLiked:int,hasComment:int,is_like:int,is_comment:int,action?:int} $deviceFlags
     */
    private function getReplyContentByRobbot(
        SvDeviceCircleLikeReply $option,
        SvDeviceCircleLikeReplyAccount $task,
        string $circleContent,
        string $request_id,
        array $deviceFlags
    ): string {
        try {
            if ($deviceFlags['hasComment'] !== 1) {
                return '';
            }

            $robot = KbRobot::where('id', $option->robot_id)->findOrEmpty();

            $replyContent = '';
            if ($robot->isEmpty()) {
                $this->setLog('点赞回复机器人不存在', 'like');
                return '';
            }
            try {
                \app\common\service\chat\ChatModelsService::assertChatModelUsable(
                    (int)$robot->model_id,
                    (int)$robot->model_sub_id,
                    (int)$task->user_id > 0 ? (int)$task->user_id : null,
                    (string)($robot->model ?? '')
                );
            } catch (\Throwable $e) {
                $this->setLog('对话模型不可用: ' . $e->getMessage(), 'like');
                return '';
            }
            $knowledge = [];
            if ($robot->kb_type == 1) { //rag
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
                        $replyContent = $response['choices'][0]['message']['content'];
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
                    'now' => time(),
                    'action' => 'chat',
                    'is_like' => $deviceFlags['hasLiked'],
                    'is_comment' => $deviceFlags['hasComment'],
                ];
                $this->setLog('请求参数: ' . json_encode($request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'like');
                $autoType = SvDevice::where('device_code', $task->device_code)->value('auto_type') ?? 0;
                if ($autoType == 0) {
                    $response = \app\common\service\ToolsService::Wechat()->chat($request);
                } else {
                    $response = \app\common\service\ToolsService::Automation()->friendsCircleComments($request);
                }
                if (isset($response['code']) && $response['code'] == 10000) {
                    $replyContent = $this->handleResponse($response, $request, $autoType);
                } else {
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

    private function handleResponse(array $response, array $request, int $autoType)
    {
        try {
            $modelAlias = $request['model'] ?? 'gpt-4o';
            ChatBillingService::checkBalance((int)$request['user_id'], $modelAlias);

            $reply = $response['data']['message'] ?? '';
            $usage = $response['data']['usage'] ?? [];

            if (!$reply || empty($usage['total_tokens'])) {
                throw new \Exception('获取内容失败');
            }

            ChatLogic::saveChatResponseLog($request, [
                'reply' => $reply,
                'usage_tokens' => $usage,
            ]);

            if ($autoType == 0) {
                $logType = $request['model'] == 'deepseek'
                    ? AccountLogEnum::TOKENS_DEC_AI_REPLY_LIKE
                    : AccountLogEnum::TOKENS_DEC_OPENAI_CHAT;
            } else {
                $logType = AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS;
            }

            ChatBillingService::charge(
                (int)$request['user_id'],
                $modelAlias,
                $usage,
                $logType,
                (string)$request['task_id'],
                ['场景' => '朋友圈评论']
            );

            return $reply;
        } catch (\Throwable $th) {
            $this->setLog('handleResponse异常信息' . $th->__toString(), 'like');
            return '';
        }
    }

    private function emptyReply(array $content, string $msg): array
    {
        return [
            'type' => 1,
            'content' => [],
            'link' => '',
            'isLike' => 0,
            'isComment' => 0,
            'msg' => $msg,
            'targetRecipient' => $content['nickname'] ?? '',
            'lastMessageContent' => $content['content'] ?? '',
        ];
    }
}
