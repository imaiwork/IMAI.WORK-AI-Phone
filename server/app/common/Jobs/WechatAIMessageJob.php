<?php

namespace app\common\Jobs;

use think\facade\Db;
use think\queue\Job;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\api\logic\ChatLogic;
use app\common\model\user\User;
use app\common\logic\AccountLogLogic;
use app\common\model\chat\ChatLog;
use think\facade\Log;
use app\common\traits\WechatTrait;

class WechatAIMessageJob
{
    use WechatTrait;
    /**
     * 微信ID
     * @var string
     */
    protected string $wechatId;

    /**
     * 好友ID
     * @var string
     */
    protected string $friendId;

    /**
     * 设备编码
     * @var string
     */
    protected string $deviceCode;

    /**
     * 用户ID
     * @var int
     */
    protected int $uid;

    /**
     * 请求数据
     * @var array
     */
    protected array $request;

    /**
     * 任务ID
     * @var string
     */
    protected string $taskId;

    /**
     * 执行任务
     * @param Job $job
     * @param array $data
     * @return void
     */
    public function handle(Job $job, array $data)
    {
        usleep(10000);
        try {
            $this->wechatId = $data['wechat_id'] ?? '';
            $this->friendId = $data['friend_id'] ?? '';
            $this->deviceCode = $data['device_code'] ?? '';
            $this->uid = $data['uid'] ?? 0;
            $this->request = $data['request'] ?? [];
            $this->taskId = $data['task_id'] ?? '';


            
            if (!$this->wechatId || !$this->friendId || !$this->uid || !$this->deviceCode || empty($this->request)) {
                $job->delete();
                return;
            }

            // 检查AI 是否已有回复记录
            $log = ChatLog::where('task_id', $this->taskId)->findOrEmpty();
            if($log->isEmpty()){
                //clogger((json_encode($this->request['knowledge'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), 'wechat');
                if(isset($this->request['knowledge']) && !empty($this->request['knowledge'])){
                    $response = \app\api\logic\KnowledgeLogic::chat([
                        'message' => $this->request['message'],
                        'indexid' => $this->request['knowledge']['index_id'],
                        'rerank_min_score' => $this->request['knowledge']['rerank_min_score'] ?? 0.2,
                        'stream' => false,
                        'user_id' => $this->uid,
                        'scene' => '个微聊天'
                    ]);
//                    Log::write($this->taskId.'消息回复结果'.json_encode($response,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    if (isset($response['choices'][0]) && !empty($response['choices'][0])) {
                        $reply =  $response['choices'][0]['message']['content']; 
                    }

                
                }else{
                    // 执行微信AI消息处理
                    $response = \app\common\service\ToolsService::Wechat()->chat($this->request);
//                    Log::write($this->taskId.'消息回复结果2'.json_encode($response,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    if (isset($response['code']) && $response['code'] == 10000) {
                        // 处理响应
                        $reply = $this->handleResponse($response);
                    } else {
                        Log::write($this->taskId.'队列请求失败'.json_encode($response));
                        // 重试
                        $this->retryJob($job, 'AI消息处理失败', $data);
                    }
                }
                
            }else{

                $reply = $log->reply;
            }

            // 发送消息
            $this->sendMessage($reply);

            // 任务执行成功，删除任务
            $job->delete();
        } catch (\Throwable $e) {
            //clogger($e);
            Log::write($this->taskId . '队列请求参数' . json_encode($data) . '失败原因' . $e->getMessage().'重试次数'.$job->attempts());
//            Log::write('堆栈信息: ' . $e->getTraceAsString()); // 记录堆栈信息
            $this->retryJob($job, $e->getMessage(), $data);
        }
    }

    /**
     * 重试任务
     * @param Job $job
     * @param string $errorMessage
     * @param array $data
     * @return void
     */
    private function retryJob(Job $job, string $errorMessage, array $data)
    {
        try {
            if ($job->attempts() >= 3) {
                // 超过重试次数，记录到失败任务表
                $this->markAsFailed($job, $errorMessage, $data);
                $job->delete();
            } else {
                $delay = pow(2, $job->attempts()) * 5;
                // 重试任务
                $job->release($delay);
            }
        } catch (\Exception $e) {
            //clogger($e, 'wechat');
            // 捕获异常并记录错误日志
            Log::error('重试失败:  ' . $e->getMessage());
        }
    }

    /**
     * 处理响应
     * @param array $response
     * @return string
     */
    private function handleResponse(array $response): string
    {
        //检查扣费
        $unit = TokenLogService::checkToken($this->uid, 'ai_wechat');

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
        ChatLogic::saveChatResponseLog($this->request, $response);

        //计算消耗tokens
        $points = $unit > 0 ? round($tokens / $unit,2) : 0;

        //token扣除
        User::userTokensChange($this->uid, $points);

        $extra = ['总消耗tokens数' => $tokens, '算力单价' => $unit, '实际消耗算力' => $points];

        //扣费记录
        AccountLogLogic::recordUserTokensLog(true, $this->uid, AccountLogEnum::TOKENS_DEC_AI_WECHAT, $points, $this->taskId, $extra);

        return $reply;
    }

    /**
     * 发送消息
     * @param string $reply
     * @return void
     */
    private function sendMessage(string $reply)
    {   
        
        // \app\common\service\ToolsService::Wechat()->push([
        //     'wechat_id' => $this->wechatId,
        //     'friend_id' => $this->friendId,
        //     'device_code' => $this->deviceCode,
        //     'message' => $reply,
        //     'message_type' => $this->request['message_id'] ? 22 : 1, //22: 引用
        //     'remark' => $this->request['message_id'],
        // ]);
        self::wxPush([
            'wechat_id' => $this->wechatId,
            'friend_id' => $this->friendId,
            'device_code' => $this->deviceCode,
            'message' => $reply,
            'message_type' => $this->request['message_id'] ? 22 : 1, //22: 引用
            'remark' => $this->request['message_id'],
            'opt_type' => 'job'
        ]);
    }

    /**
     * 标记任务失败
     * @param Job $job
     * @param string $errorMessage
     * @param array $data
     * @return void
     */
    protected function  markAsFailed(Job $job, string $errorMessage, array $data): void
    {

        try {
            $job_data = json_encode($data, JSON_UNESCAPED_UNICODE);
            Log::write( $data['request']['task_id'] . '错误消息' . $errorMessage . '新增数据' .  $job_data .'重试次数'.$job->attempts());
            Db::name('failed_jobs')->insert([
                'user_id' => $data['uid'] ?? 1,
                'job_id' => $data['request']['task_id'] ?? 2,
                'job_class' => get_class(), 
                'job_data' =>  $job_data,
                'error_message' => $errorMessage,
                'attempts' => $job->attempts(),
                'failed_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // 捕获插入失败的异常并记录日志
            Log::error('标记任务失败时发生异常: ' . $e->getMessage());
        }

    }
}
