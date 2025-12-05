<?php


namespace app\api\service;

use app\api\logic\kb\KbRobotLogic;
use app\common\enum\ChatEnum;
use app\common\enum\FileEnum;
use app\common\enum\kb\KnowEnum;
use app\common\enum\kb\RobotEnum;
use app\common\enum\member\MemberPackageEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\UserMemberLogic;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotInstruct;
use app\common\model\kb\KbRobotPublish;
use app\common\model\kb\KbRobotRecord;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use app\common\pgsql\KbEmbedding;
use app\common\service\ai\ChatService;
use app\common\service\ai\flow\CozeService;
use app\common\service\ai\RankerService;
use app\common\service\ai\VectorService;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\WordsService;
use Exception;

/**
 * 机器人对话服务类
 */
class KbChatService
{
    protected bool $isGlobalDirectives = false;

    protected bool $stream = true;
    protected mixed $chatService;          // 对话实例类
    protected mixed $user  = null;         // 用户信息
    protected mixed $robot = null;         // 机器人

    protected array $kbIds = [];           // 知识库ID
    protected string $embChannel='';       // 向量渠道
    protected int $embModelId=0;           // 向量主模型ID
    protected string $embModel='';         // 向量模型
    protected string $embAlias='';         // 向量别名
    protected string $embPrice='0';        // 向量价格
    protected array $embUsage=[];          // 向量tokens信息

    protected string $channel;             // 模型渠道
    protected string $modelMainId;         // 主模型ID
    protected string $modelSubId;          // 子模型ID
    protected string $modelAlias;          // 模型别名
    protected string $model;               // 模型名称
    protected string $price;               // 模型价格
    protected array $configs;              // 模型参数

    protected int $userId      = 0;        // 用户ID
    protected int $cateId      = 0;        // 会话的ID
    protected int $robotId     = 0;        // 机器人ID
    protected int $squareId    = 0;        // 广场的ID
    protected string $question = '';       // 提问问题
    protected string $file = '';           // 提问文件
    protected bool $isMultimodal  = false; // 是否支持多模态

    protected int $shareId          = 0;   // 分享的ID
    protected string $shareApiKey   = '';  // 分享的密钥
    protected string $shareIdentity = '';  // 分享的身份

    protected array $messages     = [];    // 上下文内容
    protected array $quotes       = [];    // 引用的数据
    protected array $usage        = [];    // 消耗的Tokens
    protected array $correlation  = [];    // 相关的问题
    protected string $reasoning   = '';    // 思考的过程
    protected string $reply       = '';    // 回复的内容
    protected array $images       = [];    // 附带的图片
    protected array $video        = [];    // 附带的视频
    protected array $files        = [];    // 附带的文件

    protected bool $instruct      = false; // 菜单指令回复

    protected bool $chatVip = false; // 对话模型是否是VIP
    protected bool $embVip  = false; // 向量模型是否是VIP

    protected int $defaultReplyOpen = 0;        // 默认回复
    protected string $defaultReplyContent = ''; // 默认回复内容

    protected mixed $flowService;           // 工作流实例类
    protected int $flowStatus = 0;          // 启用工作流状态
    protected array $flowConfig = [];       // 工作流配置

    protected string $pgDocument = '';      // PG检索的内容
    protected array $annex = [];
    protected array $fileText = [];

    protected string $uniqueId = '';

    /**
     * @notes 初始化
     * @param array $params
     * @param int $userId
     * @param bool $stream
     * @throws Exception
     * @author kb
     */
    public function __construct(array $params, int $userId, bool $stream=true)
    {
        if ($stream) {
            header('Access-Control-Allow-Origin: *');
            header('Connection: keep-alive');
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
        }

        // 基础参数
        $this->userId   = $userId;
        $this->question = $params['question'];
        $this->squareId = intval($params['square_id']??0);
        $this->robotId  = intval($params['robot_id']??0);
        $this->cateId   = intval($params['cate_id']??0);
        $this->stream = $stream;
        $this->uniqueId = $params['unique_id'] ?? '';
        $this->annex    = []; // 附件

        // 分享参数
        $this->shareId       = $params['share_id']    ?? 0;
        $this->shareApiKey   = $params['apiKey']      ?? '';
        $this->shareIdentity = $params['identity']    ?? '';
        //$shareContextNum     = $params['context_num'] ?? 0;

        // 查询机器人
        $modelKbRobot = new KbRobot();
        $this->robot = $modelKbRobot->where(['id'=>$this->robotId])->findOrEmpty()->toArray();
        if (!$this->robot || !$this->robot['is_enable']) {
            $error = !$this->robot ? '机器人不存在了!' : '机器人已被禁用了!';
            throw new Exception($error);
        }

        // 查询知识库
        $know = null;
        if ($this->robot['kb_ids']) {
            $this->kbIds = explode(',', $this->robot['kb_ids']);
            $know = (new KbKnow())->whereIn('id', $this->kbIds)->findOrEmpty()->toArray();
            if (!$know) {
                $this->robot['kb_ids'] = '';
                $this->kbIds = [];
            }
        }

        // 查询小模型
        $modelModelsCost = new ModelsCost();
        $subModels = $modelModelsCost->where(['type'=>ChatEnum::MODEL_TYPE_CHAT])->where(['id'=>$this->robot['model_sub_id']])->findOrEmpty()->toArray();
        if (!$subModels || !$subModels['status']) {
            $error = !$subModels ? ($this->robot['model_sub_id'] ? '对话模型可能已被下架了' : '请配置机器人对话模型') : '对话模型已被下架了';
            throw new Exception($error);
        }

        // 查询大模型
        $mainModel = (new Models())->where(['id'=>$subModels['model_id']])->findOrEmpty()->toArray();
        if (!$mainModel || !$mainModel['is_enable']) {
            $error = !$mainModel ? '对话模型已被下架!' : '对模型已被下架了!';
            throw new Exception($error);
        }

        $this->modelMainId = $mainModel['id'];
        $this->modelSubId  = $subModels['id'];
        $this->modelAlias  = $subModels['alias'];
        $this->channel     = $subModels['channel'];
        $this->price       = $subModels['price'];
        $this->model       = $subModels['name'];
        $this->configs = json_decode($mainModel['configs'], true);
        $this->configs['channel'] = $this->channel;
        $this->configs['model'] = $this->model;
        $this->configs['model_id'] = $subModels['model_id'];
        $this->configs['temperature'] = $this->robot['temperature']??0.8;

        // 向量模型
        if ($know) {
            $mainEmb = (new Models())->where(['type'=>ChatEnum::MODEL_TYPE_EMB])->where(['id'=>$know['embedding_model_id']])->findOrEmpty();
            if ($mainEmb->isEmpty() || !$mainEmb->is_enable) {
                throw new Exception('向量模型已被下架了');
            }

            //$embModels = $modelModelsCost->where(['type'=>ChatEnum::MODEL_TYPE_EMB])->where(['id'=>$know['embedding_model_sub_id']])->findOrEmpty()->toArray();
            $embModels = $modelModelsCost->where(['type'=>ChatEnum::MODEL_TYPE_EMB])->where(['model_id'=>$mainEmb['id']])->order('status desc')->findOrEmpty()->toArray();
            if (!$embModels) {
                throw new Exception('向量模型已被下架了: ' . $mainEmb['name']);
            }
            $this->embModelId = $mainEmb['id'];
            $this->embChannel = $embModels['channel'];
            $this->embModel   = $embModels['name'];
            $this->embAlias   = $embModels['alias'];
            $this->embPrice   = $embModels['price'];
        }

        // VIP验证
        $this->chatVip = $this->checkVip($this->modelMainId, MemberPackageEnum::APPLY_CHAT);
        $this->embVip  = $this->checkVip($this->embModelId, MemberPackageEnum::APPLY_VECTOR);

        // 查询用户
        if ($userId) {
            $this->user = (new User())->where(['id'=>$userId])->findOrEmpty();
            if ($this->user->isEmpty() || $this->user->is_blacklist || $this->user->is_disable) {
                $error = $this->user->isEmpty() ? '用户异常' : '当前用户已被拉黑';
                throw new Exception($error);
            }

            // 最低消费验证
            $chatConfig = ConfigService::get('chat')??[];
            $min_consume_status = intval($chatConfig['min_consume_status'] ?? 0);
            $min_consume_price = $chatConfig['min_consume_price'] ?? 0;
            $min_consume_tips = $chatConfig['min_consume_tips'] ?? '';
            if ($min_consume_status and $min_consume_price) {
                if ($this->user->balance < $min_consume_price) {
                    throw new Exception($min_consume_tips?:'您当前余额低于系统最低消费限额,请前往充值中心充值');
                }
            }

            if (!$this->chatVip and !$this->embVip) {
                if (($this->embPrice || $this->price) and $this->user->balance <= 0) {
                    throw new Exception('账户余额不足!');
                }
            }
        }

        // 分享的上下文数
//        if ($this->shareApiKey) {
//            $this->configs['context_num'] = $shareContextNum;
//        }
        $this->configs['context_num'] = $this->robot['context_num'] ?? 0;

        // 是否开启默认回复
        $this->defaultReplyOpen = ConfigService::get('chat','default_reply_open', 0);
        if($this->defaultReplyOpen){
            $this->defaultReplyContent = ConfigService::get('chat','default_reply', '');
            $this->channel = 'system';
        }

        // 是否支持多模态
        if (in_array($mainModel['channel'], ['zhipu', 'openai', 'azure'])) {
            if (in_array($subModels['name'], [
                'gpt-4o',
                'glm-4v',
                'gpt-4o-2024-05-13',
                'gpt-4-vision-preview',
                'gpt-4-1106-vision-preview'])
            ) {
                $this->isMultimodal = true;
                // 附件-图片
                foreach (($params['annex'] ?? []) as $item) {
                    if (!empty($item['url'])) {
                        $this->annex[] = [
                            'type' => $item['type'] ?? FileEnum::IMAGE_TYPE,
                            'name' => $item['name'] ?? '',
                            'url' => FileService::setFileUrl($item['url']),
                        ];
                    }
                }
            }
        }

        // 第三方工作流参数
        $this->flowStatus = intval($this->robot['flow_status']) ?? 0;
        $this->flowConfig = $this->robot['flow_config'] ?? KbRobotLogic::flowConfigDefault();
        if ($this->flowStatus && empty($this->flowConfig['workflow_id'])) {
            throw new Exception('关联工作流配置异常');
        }

        $supportFile = $this->robot['support_file'] ?? 0;
        if($supportFile) {
            // 文件内容
            foreach (($params['files'] ?? []) as $item) {
                $this->annex[] = [
                    'type' => $item['type'] ?? FileEnum::FILE_TYPE,
                    'name' => $item['name'] ?? '',
                    'url'  => FileService::setFileUrl($item['url']??''),
                    'size' => $item['size']??0,
                    'text' => $item['text']??'',
                ];
                $this->fileText[] = json_encode([
                    'name'    => trim($item['name'] ?? ''),
                    'size'    => $item['size'] ?? 0,
                    'content' => $item['text'] ?? '',
                ], JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }
        }
    }

    /**
     * 验证模型是不是VIP免费
     *
     * @param int $modelId  (主模型ID: 对话模型/向量模型)
     * @param int $type (1=对话模型, 2=向量模型)
     * @return bool
     */
    public function checkVip(int $modelId, int $type): bool
    {
        // is_limit=false (无限制次数), surplus_num=剩余次数
        $vips = UserMemberLogic::getUserPackageApply($this->userId, $type);
        foreach ($vips as $item) {
            if ($item['channel'] == $modelId) {
                if (!$item['is_limit'] || $item['surplus_num']) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * @notes 发起对话
     * @throws Exception
     * @author kb
     */
    public function chat(): array
    {
        try {
            // 敏感词验证
            WordsService::sensitive($this->question);

            // 问题审核(百度)
            WordsService::askCensor($this->question);

            // 查询指令
            $instruct = $this->getChatInstruct();

            // 取上下文
            $messages = $this->makeMessages();

            // 根据情况答复
            if ($this->stream) {
                $this->sse($instruct, $messages);
            } else {
                $chatResult = $this->http($instruct, $messages);
            }

            // 记录用户信息
            $model = new User();
            $model->startTrans();
            try {
                $this->saveChatRecord();
                $model->commit();
            } catch (Exception $e) {
                $model->rollback();
                throw new Exception($e->getMessage());
            }

            // 非流式的返回
            if (!$this->stream) {
                return $chatResult ?? [];
            }
        } catch (Exception $e) {
            $error = $this->handleError($e->getMessage());
            $err = ChatService::parseReturnError(
                $this->stream,
                $error,
                $e->getCode(),
                $this->model
            );

            if (!$this->stream) {
                return $err;
            }
        }
        return [];
    }

    /**
     * @notes SSE输出
     * @param array $instruct
     * @param array $messages
     * @return void
     * @throws Exception
     */
    private function sse(array $instruct, array $messages): void
    {
        // 根据情况答复
        if ($instruct) {
            // 指令回复
            $this->images = $instruct['images'];
            $this->reply = $instruct['content'];
            ChatService::parseReturnSuccess('chat', time(), $instruct['content'], 0, $this->model);
            foreach ($instruct['images'] as $img) {
                $data = json_encode(['url'=>FileService::getFileUrl($img['url']), 'name'=>$img['url']]);
                ChatService::parseReturnSuccess('image', time(), $data, 0, $this->model);
            }
        } elseif ($this->robot['search_empty_type'] === 2 and !$this->quotes) {
            // 空值回复
            $this->reply = $this->robot['search_empty_text'] ?: '我无法理解哦~';
            ChatService::parseReturnSuccess('chat', time(), $this->reply, 0, $this->model);
        } else {
            $this->chatService = ChatService::AIChannelFactory($this->channel, $this->configs);
            if ($this->flowStatus) {
                // 使用工作流
                $this->flowService = new CozeService($this->flowConfig);
                // 发起对话
                if ($this->stream) {
                    $this->flowService->chatSseRequest($this->question);
                    if (!empty($this->flowService->getFlowFiles())) {
                        $this->images = $this->flowService->getFlowFiles();
                        ChatService::parseReturnSuccess('image', time(), json_encode($this->images), 0, $this->model);
                    }
                }
                // 获取回复内容
                $this->reply = $this->flowService->getReplyContent()[0] ?? '';
                $this->usage = $this->flowService->getUsage();
            } else {
                // AI回复
                $this->chatService->chatSseRequest($messages);

                // 图片输出
                $images = array_map(function($item) {
                    return ['url' => FileService::getFileUrl($item['url']), 'name' => $item['name']];
                }, $this->images);
                if ($images && $this->stream) {
                    ChatService::parseReturnSuccess('image', time(), json_encode($images), 0, $this->model);
                }

                // 视频输出
                $video = array_map(function($item) {
                    return ['url' => FileService::getFileUrl($item['url']), 'name' => $item['name']];
                }, $this->video);
                if ($video && $this->stream) {
                    ChatService::parseReturnSuccess('video', time(), json_encode($video), 0, $this->model);
                }

                // 附件输出
                $files = array_map(function($item) {
                    return ['url' => FileService::getFileUrl($item['url']), 'name' => $item['name']];
                }, $this->files);
                if ($files && $this->stream) {
                    ChatService::parseReturnSuccess('file', time(), json_encode($files), 0, $this->model);
                }

                // 获取回复
                $this->reasoning = $this->chatService->getReplyContent('reasoning');
                $this->reply = $this->chatService->getReplyContent()[0];
                $this->usage = $this->chatService->getUsage();
            }

            // 相似问题输出
            if ($this->stream) {
                $chatData = '';
                $chatEvent = 'finish';
                if ($this->robot['related_issues_num']) {
                    $chatEvent = 'question';
                    $makeIssue = $this->robot['related_issues_num'];
                    $questions = ChatService::makeQuestion($this->chatService, $this->messages, $this->reply, $makeIssue);
                    $chatData = json_encode($questions, JSON_UNESCAPED_UNICODE);
                    $this->correlation = $questions;
                }
                ChatService::parseReturnSuccess($chatEvent, '', $chatData, 0, $this->model, 'stop');
            }
        }

        if (!$this->usage) {
            $str_length        = mb_strlen($this->question.$this->reply);
            $prompt_tokens     = gpt_tokenizer_count($this->question);
            $completion_tokens = gpt_tokenizer_count($this->question.$this->reply);
            $this->usage = [
                'prompt_tokens'     => $prompt_tokens,
                'completion_tokens' => $completion_tokens,
                'total_tokens'      => $prompt_tokens + $completion_tokens,
                'str_length'        => $str_length
            ];
        }
    }

    /**
     * @notes HTTP输出
     * @param array $instruct
     * @param array $messages
     * @return array
     * @throws Exception
     */
    private function http(array $instruct, array $messages): array
    {
        if ($instruct) {
            // 指令回复
            $this->images = $instruct['images'];
            $this->reply = $instruct['content'];
        } elseif ($this->robot['search_empty_type'] === 2 and !$this->quotes) {
            // 空值回复
            $this->reply = $this->robot['search_empty_text'] ?: '我无法理解哦~';
        } else {
            if ($this->flowStatus) {
                // 使用工作流
                $this->flowService = new CozeService($this->flowConfig);
                // 发起对话
                $this->flowService->chatHttpRequest($this->question);
                if (!empty($this->flowService->getFlowFiles())) {
                    $this->images = $this->flowService->getFlowFiles();
                }
                $this->video = [];
                $this->files = [];
                // 获取回复内容
                $this->reply = $this->flowService->getReplyContent()[0] ?? '';
                $this->usage = $this->flowService->getUsage();
            } else {
                // AI回复
                $this->chatService = ChatService::AIChannelFactory($this->channel, $this->configs);
                $this->chatService->chatHttpRequest($messages);
                // 获取回复
                $this->reasoning = $this->chatService->getReplyContent('reasoning');
                $this->reply = $this->chatService->getReplyContent()[0];
                $this->usage = $this->chatService->getUsage();
            }
        }

        if (!$this->usage) {
            $str_length        = mb_strlen($this->question.$this->reply);
            $prompt_tokens     = gpt_tokenizer_count($this->question);
            $completion_tokens = gpt_tokenizer_count($this->question.$this->reply);
            $this->usage = [
                'prompt_tokens'     => $prompt_tokens,
                'completion_tokens' => $completion_tokens,
                'total_tokens'      => $prompt_tokens + $completion_tokens,
                'str_length'        => intval($str_length)
            ];
        }

        $images = [];
        $video = [];
        $files = [];
        foreach ($this->images as $item) {
            $images[] = ['url' => FileService::getFileUrl($item['url']), 'name' => $item['name'] ?? ''];
        }

        foreach ($this->video as $item) {
            $video[] = ['url' => FileService::getFileUrl($item['url']), 'name' => $item['name'] ?? ''];
        }

        foreach ($this->files as $item) {
            $files[] = ['url' => FileService::getFileUrl($item['url']), 'name' => $item['name'] ?? ''];
        }

        $index = 0;
        $event = 'chat';
        $finish = 'stop';
        $chatResult = ChatService::parseReturnSuccess(
            $event,
            time(),
            $this->reply,
            $index,
            $this->model,
            $finish,
            $this->stream,
            $this->usage
        );

        if ($images) {
            $chatResult['image'] = $images;
        }
        if ($video) {
            $chatResult['video'] = $video;
        }
        if ($files) {
            $chatResult['file'] = $files;
        }

        return $chatResult;
    }

    /**
     * @notes 构建上下文
     * @return array
     * @throws Exception
     */
    private function makeMessages(): array
    {
        $messages = [];

        // 全局指令
        if (!empty($this->configs['global_directives'])) {
            $this->isGlobalDirectives = true;
            $messages[] = ['role'=>'system', 'content'=>$this->configs['global_directives']];
        }

        // 角色指令
        if ($this->robot['roles_prompt']) {
            $messages[] = ['role' => 'system', 'content' => $this->robot['roles_prompt']];
        }

        // 限定指令
        $document = $this->getPgEmbedding();
        if (empty($document) && empty($this->fileText)) {
            $prompt = RobotEnum::getPromptTpl('question');
        } else {
            $prompt = RobotEnum::getPromptTpl('chat');
        }

        if ($this->robot['limit_prompt']) {
            $prompt = $this->robot['limit_prompt'];
        }

        // 上下文
        $context = $this->getChatContext();
        $messages = array_merge($messages, $context);

        // 知识库
        $prompt = str_replace('[[document]]', $document, $prompt);
        $prompt = str_replace('[[files]]', implode("\n\n", $this->fileText), $prompt);
        $prompt = str_replace('[[question]]', $this->question, $prompt);

        // 问题
        if ($this->annex) {
            $hasImage = false;
            foreach ($this->annex as $item) {
                if (!empty($item['url']) and ($item['type'] == FileEnum::IMAGE_TYPE)) {
                    $messages[] = [
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => FileService::getFileUrl($item['url'])]],
                        ]
                    ];
                    $hasImage = true;
                }
            }
            if (!$hasImage) {
                $messages[] = ['role' => 'user', 'content' => $prompt];
            }
        } else {
            $messages[] = ['role' => 'user', 'content' => $prompt];
        }

        $this->messages = $messages;
        return $messages;
    }

    /**
     * @notes 获取对话上下文
     * @throws Exception
     * @author kb
     */
    private function getChatContext(): array
    {
        $messages = [];

        // 不需要上下文
        $contextNum = intval($this->configs['context_num'] ?? 0);
        if (!$contextNum) {
            return $messages;
        }

        $where[] = ['robot_id', '=', $this->robotId];
        $where[] = ['category_id', '=', $this->cateId];
        if ($this->shareApiKey) {
            // 分享发布的
            $where[] = ['share_id', '=', $this->shareId];
            $where[] = ['share_apikey', '=', $this->shareApiKey];
            $where[] = ['share_identity', '=', $this->shareIdentity];
            $where[] = ['unique_id', '=', $this->uniqueId];
        } else {
            // 普通对话的
            $where[] = ['user_id', '=', $this->userId];
        }

        // 从广场来了
        if ($this->squareId) {
            $where[] = ['square_id', '=', $this->squareId];
        }

        $context_num = intval($this->configs['context_num']??0);
        if ($context_num <= 0) {
            return $messages;
        }

        $modelRecord = new KbRobotRecord();
        $chatRecords = $modelRecord
            ->field(['id,ask,reply,files_plugin'])
            ->where($where)
            ->where(['is_show'=>1])
            ->limit($context_num)
            ->order('id desc')
            ->select()
            ->toArray();

        $chatRecords = array_reverse($chatRecords);
        foreach ($chatRecords as $record){
            $ask   = is_array($record['ask']) ? implode('，', $record['ask']) : $record['ask'];
            $reply = is_array($record['reply']) ? implode('，', $record['reply']) : $record['reply'];

            $filesPlugin = json_decode($record['files_plugin'] ?? '[]', true);
//            foreach ($filesPlugin as $t) {
//                if (!empty($t['text']) and $t['type'] == FileEnum::FILE_TYPE) {
//                    $this->fileText[] = json_encode([
//                        'name' => $t['name'] ?? '',
//                        'size' => $t['size'] ?? 0,
//                        'content' => $t['text'],
//                    ], JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
//                }
//            }

            if ($filesPlugin and $this->isMultimodal) {
                $content[] = ['type' => 'text', 'text' => (string)$ask];
                foreach ($filesPlugin as $t) {
                    if (intval($t['type']) == FileEnum::IMAGE_TYPE) {
                        $imgUrl    = FileService::getFileUrl($t['url']);
                        $content[] = ['type' => 'image_url', 'image_url' => ['url' => $imgUrl]];
                    }
                }
                $messages[] = ['role' => 'user', 'content' => $content];
            } else {
                $messages[] = ['role'=>'user', 'content'=>strval($ask)];
            }

            $messages[] = ['role'=>'assistant', 'content'=>strval($reply)];
        }

        return $messages;
    }

    /**
     * @notes 获取引用的内容
     * @throws Exception
     * @author kb
     */
    private function getPgEmbedding(): string
    {
        try {
            if (empty($this->kbIds)) {
                return '';
            }

            $finalData = [];
            if ($this->embModelId) {
                // GPT转向量
                $vectorService = new VectorService($this->embModelId);
                $embeddingArr = $vectorService->toEmbedding($this->embChannel, $this->embModel, $this->question);
                $embeddingStr = '[' . implode(',', $embeddingArr) . ']';
                $this->embUsage = $vectorService->getUsage();

                // 匹配的规则
                $symbol = '=';
                $orders = 'score asc';

                // 查询相似度
                $modelKbEmbedding = new KbEmbedding();
                $sql = $modelKbEmbedding
                    ->alias('pe')
                    ->field('pe.uuid,pe.question,pe.answer,pe.annex,(pe.embedding <' . $symbol . '> :embedding) AS score')
                    ->whereIn('pe.kb_id', $this->kbIds)
                    ->where(['pe.status' => KnowEnum::RUN_OK])
                    ->where(['pe.dimension' => count($embeddingArr)])
                    ->where(['pe.is_delete' => 0])
                    ->bind(['embedding' => $embeddingStr])
                    ->order($orders)
                    ->limit(64)
                    ->buildSql();

                // 执行数据查询
                $sql = str_replace("( SELECT", "SET LOCAL hnsw.ef_search = 100;\n(SELECT", $sql);
                $pgLists = app('db')->connect('pgsql')->query($sql);

                // 数据重排
                if ($this->robot['ranking_status'] and $pgLists) {
                    $rankingScore = $this->robot['ranking_score'];
                    $rankingModel = $this->robot['ranking_model'];
                    $reResults = (new RankerService($rankingModel))->sendAuto($this->question, $pgLists, $rankingScore);
                    foreach ($reResults as $index => $reItem) {
                        if ($index < $this->robot['search_limits']) {
                            $reItem['question'] = get_file_domain($reItem['question']);
                            $reItem['answer'] = get_file_domain($reItem['answer']);
                            $finalData[] = $reItem;
                        }
                    }
                } else {
                    $semantics = [];
//                    $searchSimilarity = number_format(1 - $this->robot['search_similarity'], 3);
                    foreach ($pgLists as $p) {
                        $p['score'] = trim($p['score'], '-');
                        $p['score'] = number_format(1 - $p['score'], 5);
                        if ($p['score'] >= $this->robot['search_similarity']) {
                            $semantics[] = $p;
                        }
                    }

                    $scores = array_column($semantics, 'score');
                    array_multisort($scores, SORT_DESC, $semantics);
                    foreach ($semantics as $index => $pg) {
                        if ($index < $this->robot['search_limits']) {
                            $pg['question'] = get_file_domain($pg['question']);
                            $pg['answer'] = get_file_domain($pg['answer']);
                            $finalData[] = $pg;
                        }
                    }
                }
            }

            // 处理引用数据
            $documents = "";
            foreach ($finalData as $pgItem) {
                if ($documents) {
                    $documents .= "\n------\n\n";
                }

                $documents .= $pgItem['question'] . "\n";
                if ($pgItem['answer']) {
                    $documents .= $pgItem['answer'] . "\n";
                }

                $score = ltrim($pgItem['score'], '-');
                $this->quotes[] = [
                    'uuid'     => $pgItem['uuid'],
                    'score'    => $score,
                    'answer'   => $pgItem['answer'],
                    'question' => $pgItem['question'],
                ];

                $annex = json_decode($pgItem['annex'], true);
                $this->images = array_merge($this->images, $annex['images'] ?? []);
                $this->video  = array_merge($this->video, $annex['video'] ?? []);
                $this->files  = array_merge($this->files, $annex['files'] ?? []);
            }

            $this->pgDocument = $documents;
            return $documents;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @notes 获取对话指令
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    private function getChatInstruct(): array
    {
        $modelKbRobotInstruct = new KbRobotInstruct();
        $instruct = $modelKbRobotInstruct
            ->field(['id,keyword,content,images'])
            ->where(['robot_id'=>$this->robotId])
            ->where(['keyword'=>$this->question])
            ->findOrEmpty()
            ->toArray();

        if ($instruct) {
            $imageList = [];
            $images = $instruct['images'] ? explode(',', $instruct['images']) : [];
            foreach ($images as $url) {
                $imageList[] = ['url'=>$url, 'name'=>$url];
            }

            return [
                'keyword' => $instruct['keyword'],
                'content' => $instruct['content'],
                'images'  => $imageList
            ];
        }

        return [];
    }

    /**
     * @notes 保存对话记录
     * @author kb
     */
    private function saveChatRecord(): void
    {
        // 上下文组
        $context = $this->messages;
        $context[] = ['role'=>'assistant', 'content'=>$this->reply];
        if ($this->isGlobalDirectives) {
            array_shift($context);
        }

        // 对话Tokens
        $chatUseTokens = tokens_price('chat', $this->modelSubId, $this->usage['str_length']);
        $chatUseTokens = $this->chatVip ? 0 : $chatUseTokens;
        $chatUseTokens = $this->defaultReplyOpen ? 0 : $chatUseTokens; // 默认回复不收费
        $flowsUsage = [
            'robotId'     => $this->robot['id'],
            'robotName'   => $this->robot['name'],
            'flows' => [
                [
                    'name'              => 'chat',
                    'model'             => $this->modelAlias,
                    'total_price'       => $chatUseTokens,
                    'prompt_tokens'     => $this->usage['prompt_tokens'],
                    'completion_tokens' => $this->usage['completion_tokens'],
                    'total_tokens'      => $this->usage['total_tokens'],
                    'str_length'        => $this->usage['str_length']
                ]
            ]
        ];

        // 向量Tokens
        $embUseTokens = 0;
        if ($this->embUsage) {
            $embUseTokens = tokens_price('emb', $this->embModelId, $this->embUsage['str_length']);
            $embUseTokens = $this->embVip ? 0 : $embUseTokens;
            $embUseTokens = $this->defaultReplyOpen ? 0 : $embUseTokens; // 默认回复不收费
            $flowsUsage['flows'][] = [
                'name'              => 'emb',
                'model'             => $this->embAlias,
                'total_price'       => $embUseTokens,
                'prompt_tokens'     => $this->embUsage['prompt_tokens'],
                'completion_tokens' => $this->embUsage['completion_tokens'],
                'total_tokens'      => $this->embUsage['total_tokens'],
                'str_length'        => $this->embUsage['str_length']
            ];
        }

        // 用户扣费 (菜单指令不需要扣费)
        $changeAmount = 0;
        if (!$this->instruct and (!$this->chatVip || !$this->embVip)) {
            if (!$this->chatVip) {
                $changeAmount += $chatUseTokens;
            }

            if (!$this->embVip) {
                $changeAmount += $embUseTokens;
            }

            $balance = $this->user->balance - $changeAmount;

            User::update([
                'balance' => max($balance, 0)
            ], ['id' => $this->userId]);

            // 扣费日志
            if ($changeAmount) {
                $changeType = AccountLogEnum::UM_DEC_ROBOT_CHAT;
                $changeAction = AccountLogEnum::DEC;
                $changeRemark = AccountLogEnum::getChangeTypeDesc($changeType);
                UserAccountLog::add($this->userId, $changeType, $changeAction, $changeAmount, '', $changeRemark, [], 0, $flowsUsage);
            }
        }

        // 提问数量
        User::update([
            'total_chat' => ['inc', 1]
        ], ['id'=>$this->userId]);

        // 保存记录
        $userId = $this->shareId ? $this->robot['user_id'] : $this->userId;
        KbRobotRecord::create([
            'user_id'        => $userId,
            'robot_id'       => $this->robotId,
            'category_id'    => $this->cateId,
            'square_id'      => $this->squareId,
            'chat_model_id'  => $this->modelMainId,
            'emb_model_id'   => $this->embModelId,
            'ask'            => $this->question,
            'reply'          => $this->reply,
            'reasoning'      => $this->reasoning,
            'images'         => json_encode($this->images, JSON_UNESCAPED_UNICODE),
            'video'          => json_encode($this->video, JSON_UNESCAPED_UNICODE),
            'files'          => json_encode($this->files, JSON_UNESCAPED_UNICODE),
            'quotes'         => json_encode($this->quotes, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'context'        => json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'correlation'    => json_encode($this->correlation, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'flows'          => json_encode($flowsUsage['flows'], JSON_UNESCAPED_UNICODE),
            'files_plugin'   => json_encode($this->annex, JSON_UNESCAPED_UNICODE),
            'model'          => $this->modelAlias,
            'tokens'         => $changeAmount,
            'share_id'       => $this->shareId,
            'share_apikey'   => $this->shareApiKey,
            'share_identity' => $this->shareIdentity,
            'is_flow'        => $this->flowStatus,
            'unique_id'      => $this->uniqueId,
        ]);

        // 分享更新
        if ($this->shareId) {
            (new KbRobotPublish())
                ->where(['id' => $this->shareId])
                ->where(['robot_id' => $this->robotId])
                ->update([
                    'use_count' => ['inc', 1],
                    'use_time'  => time()
                ]);
        }
    }

    private function handleError($error)
    {
        if (str_starts_with($error, 'Input length')) {
            preg_match('/exceeds the maximum length (\d+)/', $error, $matches);
            $length = $matches[1]??'';
            return "您输入的内容已超出模型限制长度限制$length, 请重新调整后进行发送";
        } elseif (str_contains($error, 'Range of input length should be')) {
            preg_match('/\[1,\s*(\d+)]/', $error, $matches);
            $length = ($matches[1]??'');
            return "您输入的内容已超出模型限制长度限制$length, 请重新调整后进行发送";
        } elseif (str_contains($error, 'This model\'s maximum context length is')) {
            preg_match('/This model\'s maximum context length is (\d+) tokens. However, you requested (\d+) tokens/', $error, $matches);
            $maxLength = $matches[1]?? '';
            $requestedLength = $matches[2]?? '';
            return "您输入的内容已超出模型限制长度$maxLength tokens，您请求的长度为$requestedLength tokens，请重新调整后进行发送";
        }

        return $error;
    }
}