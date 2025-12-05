<?php

namespace app\api\logic;

use app\api\logic\coze\CozeToolsLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
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
use app\common\service\FileService;
use app\common\service\WordsService;

class ChatLogic extends ApiLogic
{

    const COMMON_CHAT = 'common_chat'; //通用聊天
    const SCENE_CHAT = 'scene_chat'; //场景聊天
    const OPENAI_CHAT = 'openai_chat'; //openai聊天
    const GEMINI_CHAT = 'gemini_chat'; //gemini聊天

    public static function generalChat(array $params)
    {
        ini_set('max_execution_time', 0);
        $params['scene'] = '通用聊天';
        $params['stream'] = true;
        $params['assistant_id'] = $params['assistant_id'] ?? 0; //默认0为通用助手
//        $params['channel'] = $params['channel'] ?? 0;
        $params['temperature'] = $params['temperature'] ?? 1.0; //温度
        $params['top_p']       = $params['top_p'] ?? 0.5;       //多样性范围
        $params['presence_penalty'] = $params['presence_penalty'] ?? 0.2; //避免重复力度
        $params['frequency_penalty'] = $params['frequency_penalty'] ?? 0.3; //避免重复用词力度
        $params['max_tokens'] = $params['max_tokens'] ?? 4096; //token上限
        $params['context_num'] = $params['context_num'] ?? 5; //上下文数
        $params['model'] = $params['model'] ?? 'deepseek'; //默认deepseek模型
        $params['file_info'] = $params['file_info'] ?? []; //文件信息
        $params['user_id'] = self::$uid ?? 0;
        if (isset($params['model_id']) && !empty($params['model_id'])) {
            $params['model'] = ModelsCost::where('model_id', $params['model_id'])->value('alias') ?? $params['model'];
        }

        if (!empty($params['robot_id']) && empty($params['indexid']) && empty($params['kb_id'])) {
            $robot = KbRobot::where('id', $params['robot_id'])->findOrEmpty();
            if ($robot->isEmpty()) {
                throw new \Exception('机器人信息变动，请刷新后重试');
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
            $params['model']             = ModelsCost::where('id', $robot['model_sub_id'])->value('alias');             //模型

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
                            $str1 = 'data:{"object":"loading","created":' . time() . ',"content":"' . $publish_keyword['content'] . '","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                            $str = 'data:{"object":"finished","created":' . time() . ',"content":"' . $publish_keyword['content'] . '","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
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
                        $str1 = 'data:{"object":"loading","created":' . time() . ',"content":"' . $robot_keyword['reply'][0]['content'] . '","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
                        $str  = 'data:{"object":"finished","created":' . time() . ',"content":"' . $robot_keyword['reply'][0]['content'] . '","file_info":[],"reasoning_content":null,"usage":{"prompt_tokens":0,"completion_tokens":0,"total_tokens":0,"knowledge_tokens":0},"task_id":"' . $task_id . '"}' . "\n\n";
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
        }

        try {
            if (isset($params['file_info']['url']) && !empty($params['file_info']['url'])) {
                $content = CozeToolsLogic::fileParse($params['file_info']['url']);
                if (empty($content)) {
                    throw new \Exception(CozeToolsLogic::getError());
                }
                $params['file_content'] = $content;
            }

            if (isset($params['is_network_search']) && (int)$params['is_network_search'] === 1) {
                $content = CozeToolsLogic::networkSearch($params['message']);
                if (empty($content)) {
                    throw new \Exception(CozeToolsLogic::getError());
                }
                $params['net_content'] = $content;
            }

            //print_r($params);die;
            if (isset($params['indexid']) && !empty($params['indexid'])) {
                $params['scene'] = 'RAG知识库聊天';
                if (!KnowledgeLogic::chat($params)) {
                    throw new \Exception(KnowledgeLogic::getError());
                }
                self::$returnData = KnowledgeLogic::getReturnData();
            } else if (isset($params['kb_id']) && !empty($params['kb_id'])) {
                $params['scene'] = '向量知识库聊天';
                if (!KnowledgeLogic::commonVectorChat($params)) {
                    throw new \Exception(KnowledgeLogic::getError());
                }
                self::$returnData = KnowledgeLogic::getReturnData();
            } else {
                if (!ChatLogic::commonChat($params)) {
                    throw new \Exception(ChatLogic::getError());
                }
                self::$returnData = [];
            }
            return true;
        } catch (\Throwable $th) {
            self::$error = $th->getMessage();
            return false;
        }
    }

    /**
     * @desc 通用聊天
     * @param array $params
     * @return void
     */
    public static function commonChat(array $params)
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

        if (isset($params['unique_id'])) {
            $request['unique_id'] = $params['unique_id'];
            $request['apiKey']    = $params['apiKey'];
            $request['identity']  = $params['identity'];
            $request['share_id']  = $params['share_id'];
            $request['question']  = $params['question'];
        }

        if (empty($params['message']) && empty($request['file_info'])) {
            message('参数错误');
        }


        $logs = [];
        if (!isset($params['unique_id'])) {
            if (isset($params['task_id']) && $params['task_id']) {
                $request['task_id'] = $params['task_id'];

                // 对话记录
                $logs = self::chatLog($request['task_id'], 0, self::$uid);

                if (!$logs) {

                    message('对话记录ID错误');
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

        if (isset($params['file_content']) && !empty($params['file_content'])) {
            $logs[] = [
                'role' => 'user',
                'content' => $params['file_content']
            ];
        }

        if (isset($params['net_content']) && !empty($params['net_content'])) {
            $logs[] = [
                'role' => 'user',
                'content' => $params['net_content']
            ];
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


        if (!empty($params['message'])) {
            $messages = array_merge($logs, [
                [
                    'role' => 'user',
                    'content' => $params['message']
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
        ];
        $geminiModels = [
            'gemini-2.5-pro',
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemma-3-4b-it',
        ];
        $request['messages'] = $messages;
        if (in_array($request['model'], $gptModels)){
            $scene = self::OPENAI_CHAT;
        }else if (in_array($request['model'], $geminiModels)){
            $scene = self::GEMINI_CHAT;
        }else{
            $scene = self::COMMON_CHAT;
        }
        //print_r($request);die;
        $uid = self::$uid;
        if ($uid == 0 && isset($params['unique_id'])) {
            $uid = KbRobot::where('id', $params['robot_id'])->value('user_id');
        }
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

        // 存在文件 TODO
        if (isset($params['file_id'])) {
        }

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
            'stream' => false,
            'message' => $params['message'],
            'task_id' => generate_unique_task_id(),
            'user_id' => self::$uid,
            'assistant_id' => 0,
            'chat_type' => $scene_type,
            'now' => time(),
        ];

        $unit = TokenLogService::checkToken(self::$uid, $scene);

        if ($scene == 'human_prompt') {
            $request['open_reasoning'] = 5;
        }

        $response = \app\common\service\ToolsService::Chat()->message($request);

        $reply = $response['data']['choices'][0]['message']['content'] ?? '';

        //计费
        $tokens = $response['data']['usage']['total_tokens'] ?? 0;

        if (!$reply || $tokens == 0) {

            message('获取内容失败');
        }

        $response = [
            'reply' => $reply,
            'usage_tokens' => $response['data']['usage'] ?? [],
        ];

        // 保存聊天记录
        self::saveChatResponseLog($request, $response);

        //计算消耗tokens
        $points = $unit > 0 ? round($tokens / $unit, 2) : 0;
        $extra = ['总消耗tokens数' => $tokens, '算力单价' => $unit . 'tokens/算力', '实际消耗算力' => $points];

        //token扣除
        User::userTokensChange(self::$uid, $points);

        //扣费记录
        AccountLogLogic::recordUserTokensLog(true, self::$uid, $scene_type, $points, $request['task_id'], $extra);

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
                ->field('id,user_id,task_id,assistant_id,message,reasoning_content,usage_tokens,reply,file_ids,create_time,extra')
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
                    ];

                    $logList[] = [
                        'avatar' => FileService::getFileUrl($assistants_avatar),
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
                if (is_numeric($params['task_id'])){
                    $task_id = ChatLog::where('id',$params['task_id'])->value('task_id');
                    ChatLog::where('task_id', $task_id)->where('user_id', self::$uid)->select()->delete();
                }
                if (is_string($params['task_id'])){
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
     * @desc 保存聊天记录
     * @return void
     * @date 2024/6/27 9:30
     * @author dagouzi
     */
    public static function saveChatResponseLog(array $request, array $response = [])
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
                                          'ask'            => $request['question'],
                                          'reply'          => $response['reply'],
                                          'reasoning'      => $response['reasoning_content'] ?? null,
                                          'images'         => '',
                                          'video'          => '',
                                          'files'          => '',
                                          'quotes'         => '',
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
                    'message'           => $request['message'] ?? ($request['prompt'] ?? ''),
                    'reply'             => $response['reply'],
                    'chat_type' => $request['chat_type'] ?? 9006,
                    'usage_tokens'      => $response['usage_tokens'] ?? [],
                    'reasoning_content' => $response['reasoning_content'] ?? null,
                    'file_ids'          => !empty($request['file_id']) ? json_encode($request['file_id']) : '',
                    'task_time' => isset($request['now']) ? time() - $request['now'] : 0,
                    'extra'             => json_encode($response['extra'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), //预留扩展字段
                ];
                ChatLog::create($chatLogData);
            }
        } catch (\Throwable $e) {
            message($e->getMessage(), 1);
        }
    }

    /**
     * @desc tokens计费
     * @return void
     * @date 2024/12/17 10:46
     * @author dagouzi
     */
    public static function chatTokensCharge($request, $tokens): void
    {

        [$tokenScene, $tokenCode] = match ($request['chat_type']) {
            AccountLogEnum::TOKENS_DEC_COMMON_CHAT => ['common_chat', AccountLogEnum::TOKENS_DEC_COMMON_CHAT],
            AccountLogEnum::TOKENS_DEC_SCENE_CHAT => ['scene_chat', AccountLogEnum::TOKENS_DEC_SCENE_CHAT],
            AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT => ['knowledge_chat', AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT],
            AccountLogEnum::TOKENS_DEC_OPENAI_CHAT => ['openai_chat', AccountLogEnum::TOKENS_DEC_OPENAI_CHAT],
            AccountLogEnum::TOKENS_DEC_GEMINI_CHAT => ['gemini_chat', AccountLogEnum::TOKENS_DEC_GEMINI_CHAT],
        };

        $unit =  TokenLogService::getTypeScore($tokenScene);

        //计算消耗tokens
        $points = $unit > 0 ? round($tokens / $unit, 2) : 0;

        //token扣除
        User::userTokensChange($request['user_id'], $points);

        if ($request['chat_type'] == AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT) {
            $extra = ['总消耗tokens数' => $tokens,  '知识库消耗tokens数' => $request['knowledge_tokens'], '算力单价' => $unit, '实际消耗算力' => $points];
        } else {
            $extra = ['总消耗tokens数' => $tokens, '算力单价' => $unit, '实际消耗算力' => $points];
        }


        //扣费记录
        AccountLogLogic::recordUserTokensLog(true, $request['user_id'], $tokenCode, $points, $request['task_id'], $extra);
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

        //检查用户token
        TokenLogService::checkToken($userId, $tokenScene);

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
                        ->find();
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
}
