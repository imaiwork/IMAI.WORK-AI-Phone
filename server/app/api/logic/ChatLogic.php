<?php

namespace app\api\logic;

use app\api\logic\kb\KbKnowLogic;
use app\api\logic\qwen\QwenToolsLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\model\chat\Assistants;
use app\common\model\chat\ChatLog;
use app\common\model\chat\ModelsCost;
use app\common\model\chat\ModelsSetting;
use app\common\model\ChatPrompt;
use app\common\model\file\File;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotInstruct;
use app\common\model\kb\KbRobotPublish;
use app\common\model\kb\KbRobotRecord;
use app\common\model\knowledge\Knowledge;
use app\common\model\mindMap\MindMap;
use app\common\model\sv\SvRobotKeyword;
use app\common\model\user\User;
use app\common\service\aiPersona\AgentConfigService;
use app\common\service\chat\ChatBillingService;
use app\common\service\chat\ChatModelsService;
use app\common\service\FileService;
use app\common\service\MemberService;
use app\common\service\WordsService;
use GuzzleHttp\Client;

class ChatLogic extends ApiLogic
{

    /** @deprecated 使用 MemberService::matchModelFamily */
    public static function matchAllowedModel(string $model): string
    {
        return MemberService::matchModelFamily($model);
    }

    const COMMON_CHAT = 'common_chat'; //通用聊天
    const SCENE_CHAT = 'scene_chat'; //场景聊天
    const OPENAI_CHAT = 'openai_chat'; //openai聊天
    const GEMINI_CHAT = 'gemini_chat'; //gemini聊天

    /**
     * 校验分享对话身份:share_id / apiKey 必须对应当前 robot_id 的有效发布记录
     */
    protected static function isValidShareChat(array $params): bool
    {
        $shareId = (int)($params['share_id'] ?? 0);
        $apiKey  = trim((string)($params['apiKey'] ?? ''));
        $robotId = (int)($params['robot_id'] ?? 0);
        if ($robotId <= 0 || ($shareId <= 0 && $apiKey === '')) {
            return false;
        }
        $query = (new KbRobotPublish())->where('robot_id', $robotId);
        if ($shareId > 0) {
            $query->where('id', $shareId);
        } else {
            $query->where('apikey', $apiKey);
        }
        return $query->count() > 0;
    }

    public static function generalChat(array $params)
    {
        ini_set('max_execution_time', 0);
        $params['scene']             = '通用聊天';
        $params['stream']            = true;
        $params['assistant_id']      = $params['assistant_id'] ?? 0;                                                    //默认0为通用助手
        $params['temperature']       = isset($params['temperature']) ? (float)$params['temperature'] : 1.0;             //温度
        $params['top_p']             = isset($params['top_p']) ? (float)$params['top_p'] : 0.5;                         //多样性范围
        $params['presence_penalty']  = isset($params['presence_penalty']) ? (float)$params['presence_penalty'] : 0.2;   //避免重复力度
        $params['frequency_penalty'] = isset($params['frequency_penalty']) ? (float)$params['frequency_penalty'] : 0.3; //避免重复用词力度
        $params['max_tokens']        = isset($params['max_tokens']) ? (int)$params['max_tokens'] : 4096;                //token上限
        $params['context_num']       = isset($params['context_num']) ? (int)$params['context_num'] : 5;                 //上下文数
        $params['model']             = $params['model'] ?? 'deepseek';                                                  //默认deepseek模型
        $params['file_info']         = $params['file_info'] ?? [];                                                      //文件信息
        $params['user_id']           = self::$uid ?? 0;
        $params['quotes']            = $params['quotes'] ?? '';
        $modelId = (int)($params['model_id'] ?? 0);
        $modelSubId = (int)($params['model_sub_id'] ?? 0);
        if ($modelId > 0) {
            $params['model'] = ModelsCost::where('model_id', $modelId)->where('status', 1)->value('alias') ?? $params['model'];
            if ($modelSubId <= 0) {
                $modelSubId = (int)(ModelsCost::where('model_id', $modelId)->where('status', 1)->order('sort asc, id desc')->value('id') ?? 0);
            }
        } elseif (!empty($params['model'])) {
            $costRow = ModelsCost::where('alias', $params['model'])->where('status', 1)->findOrEmpty();
            if (!$costRow->isEmpty()) {
                $modelId = (int)$costRow['model_id'];
                $modelSubId = (int)$costRow['id'];
            } else {
                $modelId = (int)(ModelsCost::where('alias', $params['model'])->value('model_id') ?? 0);
                $modelSubId = (int)(ModelsCost::where('alias', $params['model'])->value('id') ?? 0);
            }
        }

        // 发布分享对话(v1/chat/commonChat):公开链路,不校验访问者/创建者当前团队空间
        // 安全:share_id/apiKey 为客户端可控字段,必须与 kb_robot_publish 中该机器人的发布记录匹配才认定为分享对话,
        // 否则任何登录用户传 share_id=1 即可绕过空间/团队/模型白名单校验
        $isShareChat = self::isValidShareChat($params);
        if ($isShareChat) {
            $params['skip_team_check'] = true;
        } else {
            unset($params['share_id'], $params['apiKey']);
        }

        if (!empty($params['robot_id']) && empty($params['indexid']) && empty($params['kb_id'])) {
            $robot = KbRobot::where('id', $params['robot_id'])->findOrEmpty();
            if ($robot->isEmpty()) {
                throw new \Exception('机器人信息变动，请刷新后重试');
            }
            // 分享出去的智能体人人可用;站内对话仍校验当前企业空间
            if (!$isShareChat
                && !AgentConfigService::isAgentUsableInCurrentSpace((int)self::$uid, $robot->toArray())
            ) {
                throw new \Exception('当前空间不可用该智能体（创建者已离开团队或空间不匹配）');
            }
            if ($robot['kb_type'] == 1) {
                $params['indexid'] = Knowledge::where('id', $robot['kb_ids'])->value('index_id');
            }

            if ($robot['kb_type'] == 2 && !empty($robot['kb_ids'])) {
                $params['kb_id'] = explode(',', $robot['kb_ids']);
            }
            //当选择智能体时，重置所有预设条件
            $params['temperature']       = (float)$robot['temperature'];       //温度
            $params['top_p']             = (float)$robot['top_p'];             //多样性范围
            $params['presence_penalty']  = (float)$robot['presence_penalty'];  //避免重复力度
            $params['frequency_penalty'] = (float)$robot['frequency_penalty']; //避免重复用词力度
            $params['context_num']       = $robot['context_num'];       //上下文数
            $modelId = (int)$robot['model_id'];
            $modelSubId = (int)$robot['model_sub_id'];
            $params['model_id'] = $modelId;
            $params['model_sub_id'] = $modelSubId;
            $params['model'] = ModelsCost::where('id', $modelSubId)->value('alias');  //模型

            // 分享对话按创建者会员能力;站内团队共享仍按能力主体解析
            if ($isShareChat) {
                $checkUid = (int)($robot['user_id'] ?? 0);
            } else {
                $checkUid = \app\common\service\TeamContextService::resolveCapabilityUserId(
                    (int)self::$uid,
                    (int)($robot['user_id'] ?? 0),
                    (int)($robot['team_id'] ?? 0)
                );
            }
            try {
                ChatModelsService::assertChatModelUsable($modelId, $modelSubId, $checkUid > 0 ? $checkUid : null);
            } catch (\Exception $e) {
                self::setError($e->getMessage());
                return false;
            }

            //coze智能体回复
            if ($robot['flow_status'] == 1) {
                $flow_config = $robot['flow_config'];
                $task_id = $params['task_id'] ?? uniqid('eq') . time();
                $params['task_id'] = $task_id;
                if (isset($params['unique_id'])) {
                    // 发布聊天的task_id使用前端传过来的unique_id
                    $task_id            = $params['unique_id'];
                    $params['question'] = $params['message'];
                    $params['messages'] = [];
                }
                $flow_reply = self::requestFlow($flow_config['bot_id'], $flow_config['api_token'], ['user_id' => $_SERVER['HTTP_HOST'] . self::$uid, 'content' => $params['message']]);
                header('Content-type: text/event-stream');
                header('Cache-Control: no-cache');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');
                $str1 = 'data:{"object":"loading","created":' . time() . ',"content":"' . $flow_reply . '","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                $str  = 'data:{"object":"finished","created":' . time() . ',"content":"","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                echo $str1;
                ob_flush();
                flush();
                echo $str;
                ob_flush();
                flush();
                //记录日志
                ChatLogic::saveChatResponseLog($params, [
                    'reply'             => $flow_reply ?? '',
                    'reasoning_content' => null,
                    'usage_tokens'      => 0,
                    'extra'             => [
                        'file' => [], //文件信息
                    ]
                ]);
                exit;
            }

            if (isset($params['unique_id'])) {
                $publish_keywords = KbRobotInstruct::where('robot_id', $params['robot_id'])->select()->toArray();
                if (!empty($publish_keywords)) {
                    $params['messages'] = [];
                    $params['question'] = $params['message'];
                    $task_id = $params['unique_id'];
                    foreach ($publish_keywords as $publish_keyword) {
                        if ($params['message'] == $publish_keyword['keyword']) {
                            header('Content-type: text/event-stream');
                            header('Cache-Control: no-cache');
                            header('Connection: keep-alive');
                            header('X-Accel-Buffering: no');
                            $str1 = 'data:{"object":"loading","created":' . time() . ',"content":"' . self::escapeSpecialChars($publish_keyword['content']) . '","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                            $str = 'data:{"object":"finished","created":' . time() . ',"content":"","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                            echo $str1;
                            ob_flush();
                            flush();
                            echo $str;
                            ob_flush();
                            flush();
                            //记录日志
                            ChatLogic::saveChatResponseLog($params, [
                                'reply'             => $publish_keyword['content'] ?? '',
                                'reasoning_content' => null,
                                'usage_tokens'      => 0,
                                'extra'             => [
                                    'file' => [], //文件信息
                                ]
                            ]);
                            exit;
                        }
                    }
                }
            }

            // 固定回复话术
            $robot_keywords = SvRobotKeyword::where('robot_id', $params['robot_id'])->select()->toArray();
            if (!empty($robot_keywords)) {
                $task_id = $params['task_id'] ?? uniqid('eq') . time();
                foreach ($robot_keywords as $robot_keyword) {
                    if ($robot_keyword['match_type'] == 1 && $params['message'] == $robot_keyword['keyword']) {
                        if (isset($params['unique_id'])) {
                            // 发布聊天的task_id使用前端传过来的unique_id
                            $task_id            = $params['unique_id'];
                            $params['question'] = $params['message'];
                            $params['messages'] = [];
                        }
                        header('Content-type: text/event-stream');
                        header('Cache-Control: no-cache');
                        header('Connection: keep-alive');
                        header('X-Accel-Buffering: no');
                        $str1 = 'data:{"object":"loading","created":' . time() . ',"content":"' . self::escapeSpecialChars($robot_keyword['reply'][0]['content']) . '","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                        $str  = 'data:{"object":"finished","created":' . time() . ',"content":"","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                        echo $str1;
                        ob_flush();
                        flush();
                        echo $str;
                        ob_flush();
                        flush();
                        //记录日志
                        ChatLogic::saveChatResponseLog($params, [
                            'reply'             => $robot_keyword['reply'] ?? '',
                            'reasoning_content' => null,
                            'usage_tokens'      => 0,
                            'extra'             => [
                                'file' => [], //文件信息
                            ]
                        ]);
                        exit;
                    }
                    else if ($robot_keyword['match_type'] == 0 && strpos($params['message'], $robot_keyword['keyword']) !== false){
                        if (isset($params['unique_id'])) {
                            // 发布聊天的task_id使用前端传过来的unique_id
                            $task_id            = $params['unique_id'];
                            $params['question'] = $params['message'];
                            $params['messages'] = [];
                        }
                        header('Content-type: text/event-stream');
                        header('Cache-Control: no-cache');
                        header('Connection: keep-alive');
                        header('X-Accel-Buffering: no');
                        $str1 = 'data:{"object":"loading","created":' . time() . ',"content":"' . self::escapeSpecialChars($robot_keyword['reply'][0]['content']) . '","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                        $str  = 'data:{"object":"finished","created":' . time() . ',"content":"","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                        echo $str1;
                        ob_flush();
                        flush();
                        echo $str;
                        ob_flush();
                        flush();
                        //记录日志
                        ChatLogic::saveChatResponseLog($params, [
                            'reply'             => $robot_keyword['reply'] ?? '',
                            'reasoning_content' => null,
                            'usage_tokens'      => 0,
                            'extra'             => [
                                'file' => [], //文件信息
                            ]
                        ]);
                        exit;
                    }
                }
            }
        } else {
            try {
                ChatModelsService::assertChatModelUsable(
                    $modelId,
                    $modelSubId,
                    !empty(self::$uid) ? (int)self::$uid : null,
                    (string)($params['model'] ?? '')
                );
            } catch (\Exception $e) {
                self::setError($e->getMessage());
                return false;
            }
        }

        try {
            $params['preprocess_usage'] = QwenToolsLogic::emptyUsage();
            $params['preprocess_reasoning'] = (string)($params['preprocess_reasoning'] ?? '');
            $needFile = isset($params['file_info']['url']) && !empty($params['file_info']['url']);
            $needNet = isset($params['is_network_search']) && (int)$params['is_network_search'] === 1;
            if ($needFile || $needNet) {
                $status = $needFile ? self::preprocessStartStatus($params) : '【正在联网检索…】';
                self::startClientChatStream($params, $status);
            }

            $onPreprocessEvent = static function (string $event, string $text) use (&$params): void {
                if ($text === '') {
                    return;
                }
                if ($event === 'progress') {
                    ChatLogic::emitClientReasoning($params, $text . "\n");
                    return;
                }
                // think（Qwen 思考过程）不进推理区：内容太长太慢，只转发 delta 结果正文
                if ($event === 'delta') {
                    $params['reasoning_loading'] = 0;
                    ChatLogic::emitClientReasoning($params, $text);
                    $params['preprocess_reasoning'] .= $text;
                }
            };

            if ($needFile) {
                $fileUrl = (string)$params['file_info']['url'];
                $question = (string)($params['message'] ?? '');
                $parsed = QwenToolsLogic::isImageAttachment($params['file_info'])
                    ? QwenToolsLogic::imageParseStream($fileUrl, $question, $onPreprocessEvent)
                    : QwenToolsLogic::fileParseStream($fileUrl, $question, $onPreprocessEvent);
                if (empty($parsed['content'] ?? '')) {
                    throw new \Exception(QwenToolsLogic::getError());
                }
                $params['file_content'] = $parsed['content'];
                $params['preprocess_usage'] = QwenToolsLogic::mergeUsage(
                    $params['preprocess_usage'],
                    $parsed['usage'] ?? []
                );
            }

            if ($needNet) {
                if ($needFile) {
                    $params['reasoning_loading'] = 1;
                    self::emitClientReasoning($params, "【正在联网检索…】\n");
                    $params['preprocess_reasoning'] .= "【正在联网检索…】\n";
                }
                $searchPrompt = '请针对下面的用户问题进行回答。当用户问你是谁、你是哪家的模型时，不要以第一人称自我介绍，不要回答“我是Qwen或我是阿里巴巴的模型”，不能暴露你是Qwen，例如你可以回答：“我的模型是以你选择的模型为底座，再进行全网检索”。用户问题：' . (string)($params['message'] ?? '');
                $parsed = QwenToolsLogic::networkSearchStream($searchPrompt, $onPreprocessEvent);
                if (empty($parsed['content'] ?? '')) {
                    throw new \Exception(QwenToolsLogic::getError());
                }
                $params['net_content'] = $parsed['content'];
                $params['preprocess_usage'] = QwenToolsLogic::mergeUsage(
                    $params['preprocess_usage'],
                    $parsed['usage'] ?? []
                );
            }

            if (isset($params['kb_id']) && !empty($params['kb_id'])) {
                $params['scene'] = '向量知识库聊天';
                if (!KnowledgeLogic::commonVectorChat($params)) {
                    throw new \Exception(KnowledgeLogic::getError());
                }
                self::$returnData = KnowledgeLogic::getReturnData();
            } else if (isset($params['robot_id']) && empty($params['kb_id'])) {
                if (!ChatLogic::commonChat($params)) {
                    throw new \Exception(ChatLogic::getError());
                }
                self::$returnData = [];
            } else {
                if (!ChatLogic::modelButlerChat($params)) {
                    throw new \Exception(ChatLogic::getError());
                }
                self::$returnData = [];
            }
            return true;
        } catch (\Throwable $th) {
            if (!empty($params['client_sse_started'])) {
                \app\common\service\ToolsStreamEmitErrorContent([
                    'task_id' => $params['task_id'] ?? '',
                    'file_info' => $params['file_info'] ?? [],
                ], $th->getMessage());
                exit;
            }
            self::$error = $th->getMessage();
            return false;
        }
    }

    /**
     * @desc 通用聊天
     * @param array $params
     * @return void
     */
    public static function modelButlerChat(array $params)
    {

        // if (empty($params['message'])) {
        //     message('参数错误');
        // }
        if (!empty($params['message'])) {
            WordsService::sensitive($params['message']);
            // 问题审核(百度)
            WordsService::askCensor($params['message']);
        }


        $request['message'] = $params['message'];
        $request['open_reasoning'] = $params['open_reasoning'] ?? 0;
        $request['stream'] = true;
        $request['model'] = $params['model'] ?? 'deepseek'; //默认deepseek模型
        $request['temperature'] = $params['temperature'] ?? 1.0; //温度
        $request['top_p'] = $params['top_p'] ?? 0.5; //多样性范围
        $request['presence_penalty'] = $params['presence_penalty'] ?? 0.2; //避免重复力度
        $request['frequency_penalty'] = $params['frequency_penalty'] ?? 0.3; //避免重复用词力度
        $request['max_tokens'] = $params['max_tokens'] ?? 4096; //token上限
        $request['context_num'] = $params['context_num'] ?? 5; //上下文数
        $request['file_info'] = $params['file_info'] ?? []; //文件信息
        $request['robot_id'] = $params['robot_id'] ?? 0; //机器人id
        $request['quotes'] = $params['quotes'] ?? ''; //引用内容

        if (!empty($params['quotes'])) {
            $request['message'] = '引用的内容：' . $params['quotes'] . "。引用结束>>" . $request['message'];
        }

        if (isset($params['unique_id'])) {
            $request['unique_id'] = $params['unique_id'];
            $request['apiKey']    = $params['apiKey'];
            $request['identity']  = $params['identity'];
            $request['share_id']  = $params['share_id'];
            $request['question']  = $params['question'];
        }
        if (!empty($params['skip_team_check'])) {
            $request['skip_team_check'] = true;
        }

        if (empty($params['message']) && empty($request['file_info'])) {
            message('参数错误');
        }

        $logs = [];

        //模型大管家检索
        $systemRoleCheck = KbKnowLogic::embModelButlerSearch($params['user_id'], $request['message'], $checkRobotId);
        $systemRole[] = [
            'role' => 'system',
            'content' => "你的角色是模型大管家，帮助用户检索出合适的智能体。当检索到合适的智能体时，不能虚构内容，只需要告诉用户找到了对应的智能体，例如：'关于这个问题，我找到了更适合的智能体为你解答，建议你寻找 【@xxx】 的帮助。' \n 当没有检索到合适智能体时，你的角色转换成常规对话机器人，恢复成正常对话模式。\n" . $systemRoleCheck,
        ];
        $request['check_robot_id'] = $checkRobotId ?? 0;

        if (!isset($params['unique_id'])) {
            if (isset($params['task_id']) && $params['task_id']) {
                $request['task_id'] = $params['task_id'];
                if (empty($params['task_id_is_new'])) {
                    $logs = self::chatLog($request['task_id'], 0, self::$uid);
                    if (!$logs) {
                        message('对话记录ID错误');
                    }
                }
            } else {
                $request['task_id'] = generate_unique_task_id();
            }
        } else {
            $ids = KbRobotRecord::where('unique_id', $params['unique_id'])
                ->column('id');
            if (count($ids) > $params['context_num']) {
                $ids = array_slice($ids, count($ids) - $params['context_num'], $params['context_num']);
            }
            KbRobotRecord::whereIn('id', $ids)
                ->order('id asc')
                ->select()
                ->each(function ($item) use (&$logs) {
                    $logs[] = [
                        'role'    => 'user',
                        'content' => $item->ask
                    ];
                    $logs[] = [
                        'role'    => 'assistant',
                        'content' => $item->reply
                    ];
                })
                ->toArray();
            $request['task_id'] = $params['unique_id'];
        }

        if (isset($params['robot_id']) && $params['robot_id'] != 0 && $params['robot_id'] != '0') {
            $robot_set = KbRobot::where('id', $params['robot_id'])->value('roles_prompt');
            if (!empty($robot_set)) {
                $text   = "你的角色设定是：" . $robot_set . "\n";
                $logs[] = [
                    'role'    => 'user',
                    'content' => str_replace('"', "'", $text),
                ];
            }
        }


        $userContent = self::wrapUserMessageWithPreprocess((string)($params['message'] ?? ''), $params);
        if ($userContent !== '') {
            $messages = array_merge($logs, [
                [
                    'role' => 'user',
                    'content' => $userContent
                ]
            ]);
        } else {
            $messages = $logs;
        }
        $messages = array_merge($systemRole, $messages);

        $gptModels = [
            'gpt-4',
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-4o-2024-08-06',
            'gpt-3.5-turbo',
            'gpt-5.4',
            'gpt-5.4-mini',
            'gpt-5',
            'gpt-5-mini',
            'claude-sonnet-4-5',
            'claude-sonnet-4-6',
            'claude-sonnet-4-6-think'
        ];
        $geminiModels = [
            'gemini-2.5-pro',
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemma-3-4b-it',
            'gemini-3.1-pro-preview',
            'gemma-4-31b-it'
        ];
        $request['messages'] = $messages;
        if (in_array($request['model'], $gptModels)) {
            $scene = self::OPENAI_CHAT;
        } else if (in_array($request['model'], $geminiModels)) {
            $scene = self::GEMINI_CHAT;
        } else {
            $scene = self::COMMON_CHAT;
        }
        //print_r($request);die;
        $uid = self::$uid;
        if ($uid == 0 && isset($params['unique_id'])) {
            $uid = KbRobot::where('id', $params['robot_id'])->value('user_id');
        }
        self::applyPreprocessUsageToRequest($request, $params);
        self::applyPreprocessReasoningToRequest($request, $params);
        self::requestChatUrl($request, $scene, $uid);

        exit;
    }

    /**
     * @desc 通用聊天
     * @param array $params
     * @return void
     */
    public static function commonChat(array $params)
    {

        if (!empty($params['message'])) {
            WordsService::sensitive($params['message']);
            // 问题审核(百度)
            WordsService::askCensor($params['message']);
        }

        $request['message'] = $params['message'];
        $request['open_reasoning'] = $params['open_reasoning'] ?? 0;
        $request['stream'] = true;
        $request['model'] = $params['model'] ?? 'deepseek'; //默认deepseek模型
        $request['temperature'] = $params['temperature'] ?? 1.0; //温度
        $request['top_p'] = $params['top_p'] ?? 0.5; //多样性范围
        $request['presence_penalty'] = $params['presence_penalty'] ?? 0.2; //避免重复力度
        $request['frequency_penalty'] = $params['frequency_penalty'] ?? 0.3; //避免重复用词力度
        $request['max_tokens'] = $params['max_tokens'] ?? 4096; //token上限
        $request['context_num'] = $params['context_num'] ?? 5; //上下文数
        $request['file_info'] = $params['file_info'] ?? []; //文件信息
        $request['robot_id'] = $params['robot_id'] ?? 0; //机器人id
        $request['quotes'] = $params['quotes'] ?? ''; //引用内容

        if (!empty($params['quotes'])) {
            $request['message'] = '引用的内容：' . $params['quotes'] . "。引用结束>>" . $request['message'];
        }

        if (isset($params['unique_id'])) {
            $request['unique_id'] = $params['unique_id'];
            $request['apiKey']    = $params['apiKey'];
            $request['identity']  = $params['identity'];
            $request['share_id']  = $params['share_id'];
            $request['question']  = $params['question'];
        }
        if (!empty($params['skip_team_check'])) {
            $request['skip_team_check'] = true;
        }

        if (empty($params['message']) && empty($request['file_info'])) {
            message('参数错误');
        }

        $logs = [];

        $request['check_robot_id'] = 0;

        if (!isset($params['unique_id'])) {
            if (isset($params['task_id']) && $params['task_id']) {
                $request['task_id'] = $params['task_id'];
                if (empty($params['task_id_is_new'])) {
                    $logs = self::chatLog($request['task_id'], 0, self::$uid);
                    if (!$logs) {
                        message('对话记录ID错误');
                    }
                }
            } else {
                $request['task_id'] = generate_unique_task_id();
            }
        } else {
            $ids = KbRobotRecord::where('unique_id', $params['unique_id'])
                ->column('id');
            if (count($ids) > $params['context_num']) {
                $ids = array_slice($ids, count($ids) - $params['context_num'], $params['context_num']);
            }
            KbRobotRecord::whereIn('id', $ids)
                ->order('id asc')
                ->select()
                ->each(function ($item) use (&$logs) {
                    $logs[] = [
                        'role'    => 'user',
                        'content' => $item->ask
                    ];
                    $logs[] = [
                        'role'    => 'assistant',
                        'content' => $item->reply
                    ];
                })
                ->toArray();
            $request['task_id'] = $params['unique_id'];
        }

        if (isset($params['robot_id']) && $params['robot_id'] != 0 && $params['robot_id'] != '0') {
            $robot_set = KbRobot::where('id', $params['robot_id'])->value('roles_prompt');
            if (!empty($robot_set)) {
                $text   = "你的角色设定是：" . $robot_set . "\n";
                $logs[] = [
                    'role'    => 'user',
                    'content' => str_replace('"', "'", $text),
                ];
            }
        }


        $userContent = self::wrapUserMessageWithPreprocess((string)($params['message'] ?? ''), $params);
        if ($userContent !== '') {
            $messages = array_merge($logs, [
                [
                    'role' => 'user',
                    'content' => $userContent
                ]
            ]);
        } else {
            $messages = $logs;
        }

        $gptModels = [
            'gpt-4',
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-4o-2024-08-06',
            'gpt-3.5-turbo',
            'gpt-5.4',
            'gpt-5.4-mini',
            'gpt-5',
            'gpt-5-mini',
            'claude-sonnet-4-5',
            'claude-sonnet-4-6',
            'claude-sonnet-4-6-think'
        ];
        $geminiModels = [
            'gemini-2.5-pro',
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemma-3-4b-it',
            'gemini-3.1-pro-preview',
            'gemma-4-31b-it'
        ];
        $request['messages'] = $messages;
        if (in_array($request['model'], $gptModels)) {
            $scene = self::OPENAI_CHAT;
        } else if (in_array($request['model'], $geminiModels)) {
            $scene = self::GEMINI_CHAT;
        } else {
            $scene = self::COMMON_CHAT;
        }

        $uid = self::$uid;
        if ($uid == 0 && isset($params['unique_id'])) {
            $uid = KbRobot::where('id', $params['robot_id'])->value('user_id');
        }
        self::applyPreprocessUsageToRequest($request, $params);
        self::applyPreprocessReasoningToRequest($request, $params);
        self::requestChatUrl($request, $scene, $uid);

        exit;
    }

    /**
     * @desc 获取通用聊天助手信息
     * @return bool
     */
    public static function commonChatInfo(): bool
    {
        try {
            $assistant = Assistants::where('id', 1)->findOrEmpty();

            if ($assistant->isEmpty()) {
                throw new \Exception("助手不存在");
            }
            $preliminary_ask = json_decode($assistant->preliminary_ask, true) ?? [];
            $extra           = json_decode($assistant->extra ?? '', true) ?? [];

            foreach ($preliminary_ask as $key => $value) {

                if (isset($value['logo'])) {

                    $preliminary_ask[$key]['logo'] = FileService::getFileUrl($value['logo']);
                }
            }

            if (isset($extra['banner'])) {
                $extra['banner'] = FileService::getFileUrl($extra['banner']);
            }

            $assistant->preliminary_ask     = $preliminary_ask;
            $assistant->logo                = FileService::getFileUrl($assistant['logo']);
            $assistant->banner              = $extra['banner'] ?? '';
            $assistant->new_chat_prompt     = $extra['new_chat_prompt'] ?? '';
            $assistant->file_prompt         = $extra['file_prompt'] ?? '';
            $assistant->extra               = $extra;
            self::$returnData = $assistant->toArray();
            return true;
        } catch (\Throwable $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @desc 获取场景聊天 - 助理信息
     * @param array $params
     * @return bool
     */
    public static function sceneChatInfo(array $params): bool
    {
        try {
            $assistant = Assistants::where('id', $params['assistant_id'])->findOrEmpty();

            if ($assistant->isEmpty()) {
                throw new \Exception("助手不存在");
            }

            $assistant->template_info = json_decode($assistant->template_info, true) ?? [];
            $preliminary_ask = json_decode($assistant->preliminary_ask, true) ?? [];

            foreach ($preliminary_ask as $key => $value) {

                if (isset($value['logo'])) {

                    $preliminary_ask[$key]['logo'] = FileService::getFileUrl($value['logo']);
                }
            }

            $assistant->preliminary_ask = $preliminary_ask;
            self::$returnData = $assistant->toArray();
            return true;
        } catch (\Throwable $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @desc 场景聊天
     * @param array $params
     * @return true
     */
    public static function sceneChat(array $params): bool
    {

        if (empty($params['message']) && empty($params['message_ext'])) {
            message('参数错误');
        }
        WordsService::sensitive($params['message']);
        // 问题审核(百度)
        WordsService::askCensor($params['message']);

        // 获取 场景聊天 - 助理信息
        $assistant = Assistants::where('id', $params['assistant_id'])->findOrEmpty();

        if ($assistant->isEmpty()) {
            message('助手不存在');
        }

        $message = $params['message'];

        // 表单变量替换
        $message_ext = $params['message_ext'] ?? '';

        if ($message_ext) {
            $message_ext_text = self::parseMsg($message_ext, $assistant['form_info']);
            $message = $message_ext_text . $message;
        }

        $logs = [];

        if (isset($params['task_id']) && $params['task_id']) {
            $taskId = $params['task_id'];

            // 对话记录
            $logs = self::chatLog($taskId, $assistant->id, self::$uid);

            if (!$logs) {

                message('对话记录ID错误');
            }
        } else {

            $taskId = generate_unique_task_id();
        }

        $request = self::assembleAssistantRequest($assistant->toArray(), $message, $logs);

        $request['message'] = $message;
        $request['task_id'] = $taskId;

        self::requestChatUrl($request, self::SCENE_CHAT, self::$uid);

        exit;
    }


    /**
     * @desc 提示词聊天
     * @param array $params
     * @return true
     */
    public static function promptChat(array $params): bool
    {
        $model = $params['model'] ?? 'deepseek';

        if (empty($params['message'])) {

            message('参数错误');
        }

        //获取提示词
        $prompt = ChatPrompt::where('id', $params['prompt_id'])->value('prompt_text') ?? '';

        if (!$prompt) {

            message("提示词不存在");
        }

        //获取场景
        switch ($params['prompt_id']) {
            case 1: //数字人口播
                $scene = 'human_prompt';
                $scene_type = AccountLogEnum::TOKENS_DEC_HUMAN_PROMPT;
                break;
            case 2: //思维导图
                $scene = 'mind_map';
                $scene_type = AccountLogEnum::TOKENS_DEC_MIND_MAP;
                break;
            case 3: //AI画图 - 文生图
            case 4: //AI画图 - 图生图
            case 5: //AI画图 - 商品图
                $scene = 'image_prompt';
                $scene_type = AccountLogEnum::TOKENS_DEC_IMAGE_PROMPT;
                break;
            case 20:
                $scene = 'ai_draw_video_prompt';
                $scene_type = AccountLogEnum::TOKENS_DEC_VOLC_VIDEO_PROMPT;
                break;
        }

        $request = [
            "messages" => [
                [
                    'role'    => "system",
                    'content' => $prompt
                ],
                [
                    'role'    => "user",
                    'content' => $params['message']
                ]
            ],
            "model" => $model,
            'stream' => false,
            'message' => $params['message'],
            'task_id' => generate_unique_task_id(),
            'user_id' => self::$uid,
            'assistant_id' => 0,
            'chat_type' => $scene_type,
            'now' => time(),
        ];

        $modelAlias = $model ?? 'deepseek';
        ChatBillingService::checkBalance(self::$uid, $modelAlias);

        $response = \app\common\service\ToolsService::Chat()->message($request);

        $reply = $response['data']['choices'][0]['message']['content'] ?? '';

        $usage = $response['data']['usage'] ?? [];
        if (!$reply || empty($usage['total_tokens'])) {
            message('获取内容失败');
        }

        $response = [
            'reply' => $reply,
            'usage_tokens' => $usage,
        ];

        self::saveChatResponseLog($request, $response);

        ChatBillingService::charge(
            self::$uid,
            $modelAlias,
            $usage,
            $scene_type,
            $request['task_id']
        );

        if ($scene_type == AccountLogEnum::TOKENS_DEC_MIND_MAP) {

            self::$returnData = MindMap::create([
                'user_id'   => self::$uid,
                'task_id'   => $request['task_id'],
                'ask'       => $request['message'],
                'reply'     => $reply,
                'task_time' => time() - $request['now'],
            ])->toArray();
        } else {

            self::$returnData = [
                'reply' => $reply,
            ];
        }

        return true;
    }

    /**
     * @desc 聊天记录
     * @param array $params
     * @return true
     */
    public static function chatLogs(array $params)
    {
        try {

            $logList = [];

            ChatLog::where('user_id', self::$uid)
                ->where('assistant_id', $params['assistant_id'])
                ->whereIn('chat_type', [AccountLogEnum::TOKENS_DEC_COMMON_CHAT, AccountLogEnum::TOKENS_DEC_SCENE_CHAT, AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT, AccountLogEnum::TOKENS_DEC_OPENAI_CHAT])
                ->where('task_id', $params['task_id'])
                ->field('id,user_id,task_id,robot_id,assistant_id,message,reasoning_content,usage_tokens,reply,file_ids,create_time,extra,quotes')
                ->order('id asc')->select()
                ->each(function ($item) use (&$logList) {

                    // 文件处理
                    $files = [];
                    if (!empty($item['file_ids'])) {
                        $ids = json_decode($item['file_ids'], true);
                        foreach ($ids as $id) {
                            $file = File::where('id', $id)->value('uri') ?? '';
                            if ($file) {
                                $files[] = FileService::getFileUrl($file);
                            }
                        }
                    }

                    $user_avatar = User::where('id', $item['user_id'])->value('avatar') ?? '';
                    $assistants_avatar = Assistants::where('id', $item['assistant_id'] ?: 1)->value('logo') ?? '';
                    if (!empty($item['robot_id'])) {
                        $robot = KbRobot::field('name,image')->where('id', $item['robot_id'])->findOrEmpty();
                    }
                    $robot_avatar = !empty($robot['image']) ? $robot['image'] : '';
                    $robot_name = !empty($robot['name']) ? $robot['name'] : '';

                    if (mb_strpos($item['message'], '请根据以下知识库内容回答问题：', 0, 'UTF-8') !== false) {
                        $lastSepPos      = mb_strrpos($item['message'], '问题：', 0, 'UTF-8');
                        $startPos        = $lastSepPos + mb_strlen('问题：', 'UTF-8');
                        $item['message'] = mb_substr($item['message'], $startPos, null, 'UTF-8');;
                    }
                    $extra = json_decode($item['extra'], true) ?? [];
                    $logList[] = [
                        'avatar' => FileService::getFileUrl($user_avatar),
                        'message' => $item['message'],
                        'type' => 1,
                        'create_time' => $item['create_time'],
                        'file_urls' => $files,
                        'tokens_info' => $item['usage_tokens'],
                        'extra' => json_decode($item['extra'], true) ?? [], //预留扩展字段
                        'file_info' => $extra['file'] ?? [],
                        'quotes' => $item['quotes'],
                    ];

                    $logList[] = [
                        'avatar' => !empty($robot_avatar) ? FileService::getFileUrl($robot_avatar) : FileService::getFileUrl($assistants_avatar),
                        'robot_name' => $robot_name,
                        'reply' => $item['reply'],
                        'reasoning_content' => $item['reasoning_content'],
                        'type' => 2,
                        'create_time' => $item['create_time'],
                        'tokens_info' => $item['usage_tokens']
                    ];
                });

            self::$returnData = $logList;
            return true;
        } catch (\Throwable $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @desc 删除聊天记录
     * @param array $params
     * @return true
     */
    public static function deleteChat(array $params): bool
    {
        try {
            if (isset($params['robot_id']) && !isset($params['task_id'])) {
                $chat_type = [9006, 1001, 1003, 1004];
                ChatLog::where(['robot_id' => $params['robot_id'], 'user_id' => self::$uid])->whereIn('chat_type', $chat_type)->select()->delete();
            } else {
                if (is_numeric($params['task_id'])) {
                    $task_id = ChatLog::where('id', $params['task_id'])->value('task_id');
                    ChatLog::where('task_id', $task_id)->where('user_id', self::$uid)->select()->delete();
                }
                if (is_string($params['task_id'])) {
                    ChatLog::where('task_id', $params['task_id'])->where('user_id', self::$uid)->select()->delete();
                }
            }
            return true;
        } catch (\Throwable $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @desc 编辑聊天记录
     * @param array $params
     * @return true
     */
    public static function editChat(array $params): bool
    {
        try {
            $log = ChatLog::where('id', $params['id'])->findOrEmpty();
            if ($log->isEmpty()) {
                throw new \Exception('聊天记录不存在');
            }
            $log->message = !empty($params['message']) ? $params['message'] : $log->message;
            $log->reply   = !empty($params['reply']) ? $params['reply'] : $log->reply;
            $log->save();
            return true;
        } catch (\Throwable $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @desc 保存聊天记录
     * @return void
     * @date 2024/6/27 9:30
     * @author dagouzi
     */
    public static function saveChatResponseLog(array $request, array $response = [], $scene = 'chat')
    {
        try {
            if (isset($request['unique_id'])) {
                KbRobotRecord::create([
                    'user_id'        => $request['user_id'] ?? 0,
                    'robot_id'       => $request['robot_id'] ?? 0,
                    'category_id'    => 0,
                    'square_id'      => 0,
                    'chat_model_id'  => 0,
                    'emb_model_id'   => 0,
                    'ask'            => self::cutAfterQuotesEnd($request['question']),
                    'reply'          => $response['reply'],
                    'reasoning'      => self::mergeReasoningContent($request['preprocess_reasoning'] ?? '', $response['reasoning_content'] ?? null),
                    'images'         => '',
                    'video'          => '',
                    'files'          => '',
                    'quotes'         => $request['quotes'] ?? '',
                    'context'        => json_encode($request['messages'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'correlation'    => null,
                    'flows'          => '',
                    'files_plugin'   => '',
                    'model'          => '',
                    'tokens'         => (float)$response['usage_tokens'] ?? [],
                    'share_id'       => $request['share_id'],
                    'share_apikey'   => $request['apiKey'],
                    'share_identity' => $request['identity'],
                    'is_flow'        => 0,
                    'unique_id'      => $request['unique_id'],
                ]);
                (new KbRobotPublish())
                    ->where(['id' => $request['share_id']])
                    ->where(['robot_id' => $request['robot_id']])
                    ->update([
                        'use_count' => ['inc', 1],
                        'use_time'  => time()
                    ]);
            } else {
                $chatLogData = [
                    'user_id'           => $request['user_id'],
                    'task_id'           => $request['task_id'],
                    'assistant_id'      => $request['assistant_id'] ?? 0,
                    'robot_id'          => $request['robot_id'] ?? 0,
                    'message'           => isset($request['message']) ? self::cutAfterQuotesEnd($request['message']) : (isset($request['prompt']) ? self::cutAfterQuotesEnd($request['prompt']) : ''),
                    'reply'             => $response['reply'],
                    'chat_type'         => $request['chat_type'] ?? 9006,
                    'usage_tokens'      => $response['usage_tokens'] ?? [],
                    'reasoning_content' => self::mergeReasoningContent($request['preprocess_reasoning'] ?? '', $response['reasoning_content'] ?? null),
                    'file_ids'          => !empty($request['file_id']) ? json_encode($request['file_id']) : '',
                    'task_time'         => isset($request['now']) ? time() - $request['now'] : 0,
                    'extra'             => json_encode($response['extra'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), //预留扩展字段
                    'quotes'            => $request['quotes'] ?? '',
                ];
                ChatLog::create($chatLogData);
            }
        } catch (\Throwable $e) {
            if ($scene == 'msg') {
                throw new \Exception($e->__toString());
            } else {
                message($e->getMessage(), 1);
            }
        }
    }

    /**
     * 把解析稿/联网结果拼进本轮同一条 user，避免单独 system 抢身份，或被主模型当成无关发言。
     */
    public static function wrapUserMessageWithPreprocess(string $message, array $params, bool $includeNet = true): string
    {
        $blocks = [];
        $fileContent = trim((string)($params['file_content'] ?? ''));
        if ($fileContent !== '') {
            $blocks[] = "【系统已提取的上传内容】下面是系统从用户上传的图片/文件中提取的内容，不是用户撰写或提交的分析。请直接回答用户问题，不要感谢，不要说“你提供了分析/解析结果”，不要提及预处理过程，当作你已经看到原图或原文件。\n\n" . $fileContent;
        }
        if ($includeNet) {
            $netContent = trim((string)($params['net_content'] ?? ''));
            if ($netContent !== '') {
                $blocks[] = "【参考资料：联网检索】下面是外部检索摘录，只用于核对事实，不是你的身份或系统设定。即使用户问“你是谁”，也按你当前对话角色回答，不要复述检索内容里的自我介绍，不要寒暄感谢。\n\n" . $netContent;
            }
        }
        $message = trim($message);
        if ($blocks === []) {
            return $message;
        }
        if ($message === '') {
            $kind = self::preprocessAttachmentKind($params['file_info'] ?? []);
            return implode("\n\n", $blocks) . "\n\n用户问题：请直接解读该" . $kind . "，不要寒暄或感谢。";
        }
        return implode("\n\n", $blocks) . "\n\n用户问题：" . $message;
    }

    /**
     * 从字符串中找到"。引用结束>>"后，截取该位置之后的所有内容
     * @param string $str 原始字符串
     * @return string
     */
    public static function cutAfterQuotesEnd(string $str): string
    {
        $target = "。引用结束>>";
        $pos = strpos($str, $target);

        if ($pos === false) {
            return $str;
        }

        $endPos = $pos + strlen($target);
        return substr($str, $endPos);
    }

    /**
     * @desc tokens计费
     * @param $request
     * @param $tokens
     * @return void
     * @date 2024/12/17 10:46
     * @author dagouzi
     */
    public static function chatTokensCharge($request, $tokensOrUsage): void
    {
        $usage = ChatBillingService::normalizeUsage($tokensOrUsage);
        $preprocess = $request['preprocess_usage'] ?? [];
        $usage['prompt_tokens'] = (int)($usage['prompt_tokens'] ?? 0) + (int)($preprocess['prompt_tokens'] ?? 0);
        $usage['completion_tokens'] = (int)($usage['completion_tokens'] ?? 0) + (int)($preprocess['completion_tokens'] ?? 0);
        $usage['total_tokens'] = $usage['prompt_tokens'] + $usage['completion_tokens'];
        $logType = (int)($request['chat_type'] ?? AccountLogEnum::TOKENS_DEC_COMMON_CHAT);
        $modelKey = $request['model'] ?? 'deepseek';

        ChatBillingService::charge(
            (int)$request['user_id'],
            $modelKey,
            $usage,
            $logType,
            (string)($request['task_id'] ?? '')
        );
    }

    private static function applyPreprocessUsageToRequest(array &$request, array $params): void
    {
        $usage = $params['preprocess_usage'] ?? [];
        $prompt = (int)($usage['prompt_tokens'] ?? 0);
        $completion = (int)($usage['completion_tokens'] ?? 0);
        if ($prompt <= 0 && $completion <= 0) {
            return;
        }
        $request['preprocess_usage'] = [
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
            'total_tokens' => $prompt + $completion,
        ];
        $request['extra_usage'] = [
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
        ];
    }

    private static function applyPreprocessReasoningToRequest(array &$request, array $params): void
    {
        $text = trim((string)($params['preprocess_reasoning'] ?? ''));
        if ($text !== '') {
            $request['preprocess_reasoning'] = $text;
        }
    }

    public static function startClientChatStream(array &$params, string $status = ''): void
    {
        if (!empty($params['client_sse_started'])) {
            return;
        }
        if (empty($params['task_id'])) {
            $params['task_id'] = generate_unique_task_id();
            $params['task_id_is_new'] = true;
        }
        if (!headers_sent()) {
            header('Content-type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');
        }
        $params['client_sse_started'] = true;
        if ($status !== '') {
            $params['reasoning_loading'] = 1;
            self::emitClientReasoning($params, $status . "\n");
            $params['preprocess_reasoning'] = (string)($params['preprocess_reasoning'] ?? '') . $status . "\n";
        }
    }

    public static function emitClientReasoning(array $params, string $delta): void
    {
        if ($delta === '') {
            return;
        }
        echo 'data:' . json_encode([
            'object' => 'loading',
            'created' => time(),
            'content' => '',
            'file_info' => $params['file_info'] ?? [],
            'reasoning_content' => $delta,
            'reasoning_loading' => !empty($params['reasoning_loading']) ? 1 : 0,
            'usage' => [],
            'task_id' => $params['task_id'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private static function preprocessStartStatus(array $params): string
    {
        return '【正在解析' . self::preprocessAttachmentKind($params['file_info'] ?? []) . '…】';
    }

    private static function preprocessAttachmentKind(array $fileInfo): string
    {
        $name = (string)($fileInfo['name'] ?? $fileInfo['uri'] ?? $fileInfo['url'] ?? '');
        $path = parse_url((string)($fileInfo['url'] ?? $name), PHP_URL_PATH) ?: $name;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4', 'avi', 'mkv', 'mov', 'flv', 'wmv'], true)) {
            return '视频';
        }
        if (QwenToolsLogic::isImageAttachment($fileInfo)) {
            return '图片';
        }
        return '文件';
    }

    public static function mergeReasoningContent(mixed $preprocess, mixed $main): ?string
    {
        $left = trim((string)$preprocess);
        $right = trim((string)$main);
        if ($left === '' && $right === '') {
            return null;
        }
        if ($left === '') {
            return $right;
        }
        if ($right === '') {
            return $left;
        }
        return $left . "\n\n" . $right;
    }

    /**
     * 获取聊天记录
     * @param string $taskId
     * @param int $assistantId
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public static function chatLog(string $taskId, int $assistantId, int $userId, int $limit = 10): array
    {
        $logs = [];

        // 获取指定 taskId 的所有记录，按 id 升序排序
        $ids = ChatLog::where('task_id', $taskId)
            ->where('assistant_id', $assistantId)
            ->where('user_id', $userId)
            ->order('id', 'desc')
            ->limit($limit)
            ->column('id');

        ChatLog::whereIn('id', $ids)
            ->order('id', 'asc')
            ->field('message,reply')
            ->select()
            ->each(function ($item) use (&$logs) {
                $logs[] = [
                    'role' => 'user',
                    'content' => $item->message
                ];

                $logs[] = [
                    'role' => 'assistant',
                    'content' => $item->reply
                ];
            });

        return $logs;
    }

    /**
     * 助手参数
     * @param array $assistant
     * @return array
     */
    private static function assembleAssistantRequest(array $assistant, string $message, array $logs = []): array
    {

        // 系统提示词
        $messages = [
            [
                'role' => 'system',
                'content' => $assistant['instructions']
            ],
        ];

        // 对话轮数
        $messages = array_merge($messages, $logs, [
            [
                'role' => 'user',
                'content' => $message
            ]
        ]);

        return [
            'temperature' => $assistant['temperature'] ?? 1.0,
            'top_p'       => $assistant['top_p'] ?? 0.5,
            'stream' => true,
            'assistant_id' => $assistant['id'],
            'messages' => $messages,
        ];
    }



    /**
     * @desc 解析表单变量
     * @param $message_ext
     * @param $form_info
     * @return array|string|string[]
     * @date 2024/7/2 10:14
     * @author dagouzi
     */
    private static function parseMsg($message_ext, $form_info)
    {
        $message_ext = json_decode($message_ext, true);
        if (empty($message_ext)) {
            return '';
        }
        preg_match_all('/\${([^\}]+)}/u', $form_info, $matches);
        $keys = $matches[1];
        if (empty($keys)) {
            return '';
        }
        foreach ($message_ext as $key => $value) {
            foreach ($keys as $keyword) {
                if ($keyword == $key) {
                    if (!empty($value) && is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $form_info = str_replace('${' . $keyword . '}', $value, $form_info);
                }
            }
        }
        return $form_info;
    }


    /**
     * 请求上游接口与计费
     * @param array $request
     * @param string $scene
     * @param int $userId
     * @return void
     * @throws \Exception
     */
    private static function requestChatUrl(array $request, string $scene, int $userId): void
    {

        [$tokenScene, $tokenCode] = match ($scene) {
            self::COMMON_CHAT => ['common_chat', AccountLogEnum::TOKENS_DEC_COMMON_CHAT],
            self::SCENE_CHAT  => ['scene_chat', AccountLogEnum::TOKENS_DEC_SCENE_CHAT],
            self::OPENAI_CHAT => ['openai_chat', AccountLogEnum::TOKENS_DEC_OPENAI_CHAT],
            self::GEMINI_CHAT => ['gemini_chat', AccountLogEnum::TOKENS_DEC_GEMINI_CHAT],
        };

        $modelAlias = $request['model'] ?? 'deepseek';
        // 分享智能体公开使用:只校验算力,不校验团队到期/停用
        ChatBillingService::checkBalance($userId, $modelAlias, !empty($request['skip_team_check']));

        $requestService = \app\common\service\ToolsService::Chat();


        $request['user_id']     = $userId;
        $request['chat_type']   = $tokenCode;
        $request['now']         = time();

        if ($scene == self::COMMON_CHAT) {
            $requestService->message($request);
        } else if ($scene == self::OPENAI_CHAT) {
            $requestService->openaiMessage($request);
        } else if ($scene == self::GEMINI_CHAT) {
            $requestService->geminiMessage($request);
        } else {
            $requestService->sceneMessage($request);
        }
    }

    /**
     * 获取用户模型设置
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    public static function getUserModelsSetting(array $params, int $userId): bool
    {
        try {
            $where[] = ['user_id', '=', $userId];
            if (!empty($params['model_id']) && !empty($params['model_sub_id'])) {
                $where[] = ['model_id', '=', $params['model_id']];
                $where[] = ['model_sub_id', '=', $params['model_sub_id']];
                $result  = ModelsSetting::field('id, model_id, model_sub_id, top_p, temperature, presence_penalty, frequency_penalty, max_tokens, context_num, logprobs,top_logprobs,is_default')
                    ->where($where)
                    ->findOrEmpty();
                if ($result->isEmpty()) {
                    $where[0] = ['user_id', '=', 0];
                    $result   = ModelsSetting::field('id, model_id, model_sub_id, top_p, temperature, presence_penalty, frequency_penalty, max_tokens, context_num, logprobs,top_logprobs,is_default')
                        ->where($where)
                        ->findOrEmpty();
                }
                // 用户与系统都无该模型配置时，回落默认值，避免 null->toArray()
                if ($result->isEmpty()) {
                    self::$returnData = [
                        'model_id'          => $params['model_id'],
                        'model_sub_id'      => $params['model_sub_id'],
                        'top_p'             => 0.5,
                        'temperature'       => 1,
                        'presence_penalty'  => 0.1,
                        'frequency_penalty' => 2,
                        'max_tokens'        => 4096,
                        'context_num'       => 3,
                        'logprobs'          => 0,
                        'top_logprobs'      => 10,
                        'is_default'        => 1,
                    ];
                    return true;
                }
                self::$returnData = $result->toArray();
                return true;
            }

            $result            = [];
            $userModelsSetting = ModelsSetting::field('id, model_id, model_sub_id, top_p, temperature, presence_penalty, frequency_penalty, max_tokens, context_num, logprobs,top_logprobs,is_default')
                ->where($where)
                ->select()
                ->toArray();
            $where[0]          = ['user_id', '=', 0];
            $modelsSetting     = ModelsSetting::field('id, model_id, model_sub_id, top_p, temperature, presence_penalty, frequency_penalty, max_tokens, context_num, logprobs,top_logprobs, is_default')
                ->where($where)
                ->select()
                ->toArray();
            if (count($userModelsSetting) == 0) {
                $result = $modelsSetting;
            }
            if (count($userModelsSetting) == 1) {
                foreach ($modelsSetting as $value) {
                    if ($value['model_id'] == $userModelsSetting[0]['model_id'] && $value['model_sub_id'] == $userModelsSetting[0]['model_sub_id']) {
                        $result[] = $userModelsSetting[0];
                    } else {
                        $result[] = $value;
                    }
                }
            }
            if (count($userModelsSetting) == 2) {
                $result = $userModelsSetting;
            }
            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * 用户修改模型设置
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    public static function editUserModelsSetting(array $params, int $userId): bool
    {
        try {
            $where[]      = ['user_id', '=', $userId];
            $where[]      = ['model_id', '=', $params['model_id']];
            $where[]      = ['model_sub_id', '=', $params['model_sub_id']];
            $subModel     = ModelsCost::where(['model_id' => $params['model_id'], 'id' => $params['model_sub_id']])->findOrEmpty();
            if ($subModel->isEmpty()) {
                throw new \Exception('模型不存在，请传入正确的模型id');
            }
            if ($params['top_p'] > 1 || $params['top_p'] <= 0) {
                throw new \Exception('词汇多样性取值范围 0.01到1');
            }
            if ($params['temperature'] > 2 || $params['temperature'] < 0) {
                throw new \Exception('结果相似性取值范围 0到2');
            }
            if ($params['model_id'] == 2 && ($params['temperature'] > 1 || $params['temperature'] < 0)) {
                throw new \Exception('gpt-4o结果相似性取值范围 0到1');
            }
            if ($params['presence_penalty'] > 1 || $params['presence_penalty'] < 0) {
                throw new \Exception('特定词重复率取值范围 0到1');
            }
            if ($params['frequency_penalty'] > 2 || $params['frequency_penalty'] < -2) {
                throw new \Exception('重复词频率取值范围 -2到2');
            }
            if ($params['context_num'] > 5 || $params['context_num'] < 1) {
                throw new \Exception('上下文数量取值范围 1到5');
            }
            //            if ($params['max_tokens'] > 4096 || $params['max_tokens'] < 1) {
            //                throw new \Exception('字数上限取值范围 1到4096');
            //            }
            $params['is_default'] = 1;
            $modelSetting = ModelsSetting::where($where)->findOrEmpty();
            if ($modelSetting->isEmpty()) {
                $params['user_id'] = $userId;
                $result            = ModelsSetting::create($params);
            } else {
                ModelsSetting::where($where)->update($params);
                $result = ModelsSetting::where($where)->findOrEmpty();
            }
            self::$returnData = $result->toArray();
            return true;
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    private static function requestFlow($bot_id, $token, $params): string
    {
        $url      = 'https://api.coze.cn/v3/chat';
        $body     = [
            'bot_id'              => $bot_id,
            'user_id'             => $params['user_id'],
            'stream'              => false,
            'additional_messages' => [
                [
                    'role'         => 'user',
                    'content'      => $params['content'],
                    'content_type' => 'text'
                ]
            ]
        ];
        $request  = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $body
        ];
        $client   = new Client(['timeout' => 6000, 'verify' => false]);
        $rsp      = $client->post($url, $request);
        $contents = $rsp->getBody()->getContents();
        $data     = json_decode($contents, true);
        //        Log::channel('sora')->write('发起对话'.$contents);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'coze返回异常';
        }
        if (($data['code'] ?? -1) !== 0) {
            return 'coze返回异常';
        }
        if (isset($data['data']['id']) && isset($data['data']['conversation_id'])) {
            $count = 0;
            while ($count < 15) {
                $res = self::requestFlowStatus($data['data']['conversation_id'], $data['data']['id'], $token);
                if ($res) {
                    break;
                }
                sleep(2);
                $count++;
            }
            return self::requestFlowMessage($data['data']['conversation_id'], $data['data']['id'], $token);
        }
        return 'coze返回异常';
    }

    private static function escapeSpecialChars($str): string
    {
        // 清除零宽字符
        $str = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $str);
        // 转义所有特殊字符（可扩展）
        return addcslashes($str, "\0\n\r\t\v\\'\"");
    }

    private static function requestFlowStatus($conversation_id, $chat_id, $token): bool
    {
        $url      = 'https://api.coze.cn/v3/chat/retrieve';
        $body     = [
            'conversation_id'     => $conversation_id,
            'chat_id'             => $chat_id,
        ];
        $request  = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'query'    => $body
        ];
        $client   = new Client(['timeout' => 6000, 'verify' => false]);
        $rsp      = $client->get($url, $request);
        $contents = $rsp->getBody()->getContents();
        $data     = json_decode($contents, true);
        //        Log::channel('sora')->write('聊天结果返回'.$contents);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        if (($data['code'] ?? -1) !== 0) {
            return false;
        }
        if (isset($data['data']['status']) && $data['data']['status'] == 'completed') {
            return true;
        }
        return false;
    }

    private static function requestFlowMessage($conversation_id, $chat_id, $token): string
    {
        $url      = 'https://api.coze.cn/v3/chat/message/list';
        $body     = [
            'conversation_id'     => $conversation_id,
            'chat_id'             => $chat_id,
        ];
        $request  = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'query'    => $body
        ];
        $client   = new Client(['timeout' => 6000, 'verify' => false]);
        $rsp      = $client->get($url, $request);
        $contents = $rsp->getBody()->getContents();
        $data     = json_decode($contents, true);
        //        Log::channel('sora')->write('聊天内容返回'.$contents);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'coze返回异常';
        }
        if (($data['code'] ?? -1) !== 0) {
            return 'coze返回异常';
        }

        foreach ($data['data'] as $item) {
            if ($item['type'] == 'answer') {
                return $item['content'];
            }
        }

        return 'coze返回异常';
    }
}
