<?php

namespace app\queue;

use app\common\enum\ChatEnum;
use app\common\enum\kb\KnowEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbKnowFiles;
use app\common\model\kb\KbKnowQa;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use app\common\pgsql\KbEmbedding;
use app\common\service\ai\chat\DoubaoService;
use app\common\service\ai\chat\OpenaiService;
use app\common\service\ai\chat\QwenService;
use Exception;
use Ramsey\Uuid\Uuid;
use think\queue\Job;

class QaQueueJob
{
    protected mixed $chatService;   // 对话实例类

    protected string $channel;      // 模型渠道
    protected string $model;        // 模型名称
    protected int $modelSubId;      // 子模型ID
    protected string $modelAlias;   // 模型别名
    protected string $price;        // 模型价格
    protected array $configs;       // 模型参数

    protected array $messages = []; // 上下文组
    protected array $content  = []; // 答复结果
    protected array $qaArray  = []; // 问答数组
    protected bool $isVipFree = false;  // 是否VIP免费: false=不免费

    // 消耗的token
    protected array $usage = [
        'prompt_tokens'     => 0,
        'completion_tokens' => 0,
        'total_tokens'      => 0,
        'str_length'        => 0
    ];

    public function fire(Job $job, array $data): bool|string
    {
        // 接收参数
        echo "\n";
        $initTime = time();
        $qaId = intval($data['id']);

        // 读取任务
        $modelKbKnowQa = new KbKnowQa();
        $knowQa = $modelKbKnowQa
            ->where(['id'=>$qaId])
            ->order('status acs, id desc')
            ->findOrEmpty();

        // 读知识库
        $kbId = $knowQa['kb_id']??0;
        $modelKbKnow = new KbKnow();
        $kbKnow = $modelKbKnow->where(['id'=>$knowQa['kb_id']??0])->findOrEmpty();
        if ($kbKnow->isEmpty() || !$kbKnow->is_enable) {
            $err = $kbKnow->isEmpty() ? "知识库丢失了($kbId)" : '知识库被禁用($kbId)';
            $modelKbKnowQa
                ->where(['kb_id'=>$kbId])
                ->update([
                    'status' => KnowEnum::QA_FAIL,
                    'error'  => $err,
                    'update_time' => time()
                ]);

            BaseQueue::clearQaQueue();
            echo "队列已被重置: " . date('Y-m-d H:i:s', time());
            sleep(1);

            $this->queueCount();
            return $this->inputError($err, $qaId, $job, false);
        }

        // 验证数据
        if ($knowQa->isEmpty())   { return $this->inputError("任务数据丢失", $qaId, $job, true); }
        if ($knowQa->status == 1) { return $this->inputError("任务在进行了", $qaId, $job, false); }
        if ($knowQa->status == 2) { return $this->inputError("任务已成功了", $qaId, $job, false); }
        if ($knowQa->status == 3) { return $this->inputError("任务已失败了", $qaId, $job, false); }

        // 主模型读取
        $mainModel = (new Models())
            ->where(['id'=>trim($kbKnow['documents_model_id'])])
            ->where(['type'=>ChatEnum::MODEL_TYPE_CHAT])
            ->findOrEmpty()
            ->toArray();

        // 主模型验证
        if (!$mainModel || !$mainModel['is_enable']) {
            $error = !$mainModel ? '拆分模型已不存在!' : '拆分模型已被下架了!';
            return $this->inputError($error, $qaId, $job, true);
        }

        // 子模型读取
        $modelModelsCost = new ModelsCost();
        $subModels = $modelModelsCost
            ->field(['id,model_id,channel,name,alias,price,status'])
            ->where(['id'=>trim($kbKnow['documents_model_sub_id'])])
            ->where(['type'=>ChatEnum::MODEL_TYPE_CHAT])
            ->findOrEmpty()
            ->toArray();

        // 子模型验证
        if (!$subModels || !$subModels['status']) {
            $error = !$subModels ? '向量模型已不存在' : '向量模型已被下架了';
            return $this->inputError($error, $qaId, $job, true);
        }

        // 模型使用
        $this->modelSubId = intval($subModels['id']);
        $this->modelAlias = $subModels['alias'];
        $this->channel = $subModels['channel'];
        $this->model   = $subModels['name'];
        $this->price   = $subModels['price'];
        KbKnowQa::update(['model_id'=>$mainModel['id']], ['id'=>$knowQa['id']]);

        // 模型参数
        $this->configs = json_decode($mainModel['configs'], true);
        $this->configs['channel']  = trim($this->channel);
        $this->configs['model']    = trim($this->model);
        $this->configs['model_id'] = $mainModel['id'];
        $this->configs['outputStream'] = false;

        // is_limit=false (无限制次数), surplus_num=剩余次数
        $surplus_num = '收费每1k/token = ' . $this->price;


        // 验证用户
        $modelUser = new User();
        $user = $modelUser->where(['id'=>$knowQa->user_id])->findOrEmpty();
        if ($user->isEmpty())    { return $this->inputError("用户不存在了", $qaId, $job, true); }
        if ($user->balance <= 0 && !$this->isVipFree) { return $this->inputError("用户余额不足", $qaId, $job, true); }

        // 更新状态
        $knowQa->status    = KnowEnum::RUN_ING;
        $knowQa->error     = '';
        $knowQa->results   = '';
        $knowQa->task_time = 0;
        $knowQa->save();
        sleep(1);

        // 选择渠道
        for ($i=1; $i<=2; $i++) {
            try {
                $startTime = time();
                if (count($this->qaArray) >= 30) {
                    break;
                }
                //TODO 文件拆分
                $this->chatService = match ($this->channel) {
                    'openai',
                    'qwen'    => (new QwenService($this->configs)),
                    'doubao'  => (new DoubaoService($this->configs)),
                    default   => throw new Exception('模型配置错误'),
                };

                echo "\n";
                echo "使用的模型: ". $this->model ."\n";
                echo "费用的计算: ". $surplus_num ."\n";
                echo "开始拆分($i): [".$knowQa->id.']' .$knowQa['name']. "\n";

                $this->qa($this->setMessages($knowQa->content));
                echo "拆分成功: ". count($this->qaArray) . "组数据\n";
                echo "拆分耗时: ". time() - $startTime . "\n";
            } catch (Exception $e) {
                $job->delete();
                return $this->inputError($e->getMessage(), $qaId, $job, true);
            }
        }

        // 过滤去重
        $repeat = [];
        $qaList = [];
        foreach ($this->qaArray as $item) {
            $q   = $item['Q'];
            $a   = $item['A'];
            $md5 = md5($q.$a);
            if (in_array($md5, $repeat)) {
                continue;
            }
            $repeat[] = md5($q.$a);
            $qaList[] = ['Q'=>$q, 'A'=>$a];
        }

        try {
            // 保存结果
            $knowQa->status    = KnowEnum::QA_OK;
            $knowQa->results   = json_encode($qaList, JSON_UNESCAPED_UNICODE);
            $knowQa->usage     = json_encode($this->usage, JSON_UNESCAPED_UNICODE);
            $knowQa->tokens    = tokens_price('chat', $this->modelSubId, $this->usage['str_length']);
            $knowQa->model     = $this->model;
            $knowQa->error     = '';
            $knowQa->task_time = time() - $initTime;
            $knowQa->save();
            sleep(1);
            $job->delete();

            // 写入知识库
            $this->pushKb($knowQa);
            echo "任务完成: 拆分ID=". $knowQa['id'] . "\n";
        } catch (Exception $e) {
            echo "拆分异常: " . $e->getMessage().$e->getLine();
            $knowQa->status = KnowEnum::QA_FAIL;
            $knowQa->error  = $e->getMessage();
            $knowQa->save();
            $job->delete();
        }

        // 显示任务数
        $this->queueCount();
        sleep(1);
        return true;
    }

    /**
     * @notes QA拆分请求
     * @param array $messages
     * @author kb
     */
    private function qa(array $messages): void
    {
        $this->chatService->chatSseRequest($messages);

        $content = $this->chatService->getReplyContent()[0];
        $qaArray = $this->splitQaText($content);

        $this->content[] = $content;
        $this->qaArray   = array_merge($this->qaArray, $qaArray);

        $Q = [];
        $S = [];
        foreach ($this->qaArray as $item) {
            $qStr = trim(trim($item['Q']), "\n");
            if (!in_array($qStr, $Q)) {
                $S[] = $item;
                $Q[] = $qStr;
            }
        }
        $this->qaArray = $S;

        $usage = $this->chatService->getUsage();
        $this->usage = [
            'prompt_tokens'     => $this->usage['prompt_tokens']     + $usage['prompt_tokens'],
            'completion_tokens' => $this->usage['completion_tokens'] + $usage['completion_tokens'],
            'total_tokens'      => $this->usage['total_tokens']      + $usage['total_tokens'],
            'str_length'        => $this->usage['str_length']        + $usage['str_length']
        ];
    }

    /**
     * @notes 输出错误信息
     * @param string $err
     * @param int $qaId
     * @param Job $job
     * @param bool $isMark (标记失败: true=是, false=否)
     * @return string
     * @author kb
     */
    private function inputError(string $err, int $qaId, Job $job, bool $isMark): string
    {
        echo "\n";
        echo $err . ": " . 'QID='.$qaId . "\n";
        $job->delete();
        sleep(1);

        // 需要标记失败状态的
        if ($isMark) {
            $modelKbKnowQa = new KbKnowQa();
            $modelKbKnowQa->where(['id' => $qaId])->update([
                'error' => $err,
                'status' => KnowEnum::QA_FAIL,
            ]);
        }

        $this->queueCount();
        return $err;
    }

    /**
     * @notes 获取调教上下文
     * @param string $content
     * @return array
     * @author kb
     */
    private function setMessages(string $content): array
    {
        if (empty($this->messages)) {
            $prompt = "<context></context> 标记中是一段文本，学习和分析它，并整理学习成果：
            - 提出问题并给出每个问题的答案。
            - 答案需详细完整，给出相关原文描述。
            - 答案可以包含普通文字、链接、代码、表格、公示、媒体链接等 markdown 元素。
            - 避免给出重复的问题和答案,
            - 最多提出 30 个问题。
            
            严格要求按下面的格式返回多个问题和答案:
            Q1: 问题。
            A1: 答案。
            Q2:
            A2:
            ……

            <context>
            {{text}}
            <context/>";

            $prompt = str_replace("            ", "", $prompt);
            $prompt = str_replace("{{text}}", $content, $prompt);
            $messages[] = ['role' => 'user', 'content' => $prompt];
            $this->messages = $messages;
        } else {
            foreach ($this->content as $content) {
                $this->messages[] = ['role'=>'assistant', 'content'=>$content];
                $this->messages[] = ['role'=>'user', 'content'=>'继续'];
            }
        }

        return $this->messages ;
    }

    /**
     * @notes 解析QA问答结果
     * @param string $answer
     * @return array
     * @author kb
     */
    private function splitQaText(string $answer): array
    {
        $regex = '/Q\d+:(\s*)(.*)(\s*)A\d+:(\s*)([\s\S]*?)(?=Q|$)/';
        preg_match_all($regex, $answer, $matches);

        $results = [];
        foreach ($matches[0] as $text) {
            if (!$text) {
                continue;
            }

            preg_match_all('/Q\d+: (.*?)A\d+: (.*)/s', $text, $matchesItem);
            if ($matchesItem[1] and $matchesItem[2]) {
                $results[] = [
                    'Q' => trim($matchesItem[1][0]??'', "\n"),
                    'A' => trim($matchesItem[2][0], "\n")
                ];
            }
        }

        return $results;
    }

    /**
     * @notes 推入知识库存储 (等待训练)
     * @param mixed $qaObject (QA数据对象)
     * @author kb
     */
    private function pushKb(mixed $qaObject): void
    {
        $modelKbKnowFiles = new KbKnowFiles();
        $files = $modelKbKnowFiles
            ->where(['id'=>$qaObject['fd_id']])
            ->where(['know_id'=>$qaObject['kb_id']])
            ->findOrEmpty()
            ->toArray();

        if ($files) {
            $lists = [];
            $index = 1;
            $batchCode = md5('QA'.time(). $qaObject['fd_id'] . $qaObject['kb_id']);

            foreach ($this->qaArray as $item) {
                $lists[] = [
                    'uuid'        => (Uuid::uuid4())->toString(),
                    'user_id'     => $qaObject['user_id'],
                    'kb_id'       => $qaObject['kb_id'],
                    'fd_id'       => $qaObject['fd_id'],
                    'index'       => $index,
                    'code'        => $batchCode,
                    'salt'        => md5($item['Q']),
                    'channel'     => $this->channel,
                    'model'       => $this->model,
                    //'dimension'   => $vectorModels[$channel]['dimension']??1536,
                    'question'    => $item['Q'],
                    'answer'      => $item['A'],
                    'status'      => KnowEnum::RUN_WAIT,
                    'create_time' => time(),
                    'update_time' => time(),
                    'delete_time' => 0
                ];
                $index += 1;
            }

            $modelKbEmbedding = new KbEmbedding();
            foreach ($lists as $item) {
                $modelKbEmbedding->insert($item);
                BaseQueue::pushEM(['uuid'=>$item['uuid']]);
            }

            // 详细的扣费信息
            $flowsUsage = [
                'flows' => [
                    [
                        'name'              => 'qa',
                        'model'             => $this->modelAlias,
                        'total_price'       => $qaObject['tokens'],
                        'prompt_tokens'     => $this->usage['prompt_tokens']     ?? 0,
                        'completion_tokens' => $this->usage['completion_tokens'] ?? 0,
                        'total_tokens'      => $this->usage['total_tokens']      ?? 0,
                        'str_length'        => $this->usage['str_length']      ?? 0
                    ]
                ]
            ];

            // 扣除用户费用
            if (!$this->isVipFree and $this->qaArray) {
                $user = (new User())->where(['id' => $files['user_id']])->findOrEmpty();
                $balance = $user->balance - $qaObject['tokens'];
                $balance = max($balance, 0);
                User::update([
                    'balance' => $balance
                ], ['id' => $files['user_id']]);

                // 记录变动记录
                $changeType   = AccountLogEnum::UM_DEC_KB_QA;
                $changeAction = AccountLogEnum::DEC;
                $changeRemark = AccountLogEnum::getChangeTypeDesc($changeType);
                UserAccountLog::add($files['user_id'], $changeType, $changeAction, $qaObject['tokens'], '', $changeRemark, [], 0, $flowsUsage);
                echo "结算费用: ". $qaObject['tokens'] . "\n";
            } else {
                if (!$this->qaArray) {
                    echo "结算费用: 拆分失败不收费" . "\n";
                } else {
                    echo "结算费用: VIP免费" . "\n";
                }
            }
        }
    }

    /**
     * 转向量的任务数
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    private function queueCount(): void
    {
        // 查询队列长度
        $queues = BaseQueue::getQaJobLength();
        echo "剩余任务: ". $queues ."条\n";

        if ($queues <= 0) {
            $model = new KbKnowQa();
            $lists = $model
                ->where(['status'=>KnowEnum::QA_WAIT])
                ->limit(50)
                ->select()
                ->toArray();

            if ($lists) {
                foreach ($lists as $item) {
                    BaseQueue::pushQA(['id'=>$item['id']]);
                }
                echo "重载任务: " . count($lists) ."条\n";
            }
        }
    }
}