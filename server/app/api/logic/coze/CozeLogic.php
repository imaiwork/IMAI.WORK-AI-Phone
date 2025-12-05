<?php

namespace app\api\logic\coze;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\coze\CozeConfig;
use app\common\model\coze\CozeLog;
use app\common\model\user\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use think\facade\Log;

class CozeLogic extends ApiLogic
{
    protected string $pat;          // 对应表里的 secret_token
    protected string $url = 'https://api.coze.cn';

    const COZE_AGENT_CHAT = 'cozeAgentChat';
    const COZE_WORKFLOW = 'cozeWorkflow';
    public function getconfig($source, $sourceId)
    {
        $cfg = CozeConfig::where('source', $source);
        if ($source == 1) {
            $cfg = $cfg->where('source_id', $sourceId);
        };
        $cfg = $cfg->findOrEmpty()->toArray();
        if (!$cfg) {
            throw new \Exception('Coze 配置不存在');
        }

        $this->pat = $cfg['secret_token'];
    }

    /** 统一 POST 请求 */
    protected function post(string $uri, array $body)
    {

        try {
            $client = new Client(['timeout' => 0, 'verify' => false, 'http_errors' => false]);
            $rsp = $client->post($this->url . $uri, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->pat,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);
            $data = json_decode((string)$rsp->getBody(), true);/* 此时再判断 */
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            } else {
                throw new \Exception('Coze 返回异常：' . json_last_error_msg());
            }
        } catch (GuzzleException $e) {
            throw new \Exception('Coze 返回异常：' . $e->getMessage());
        }
    }



    public function workflowrun(string $wfId, array $params)
    {
        $agent = $params['agent'];
        $this->getconfig($params['agent']['source'], $params['agent']['source_id']);
        $taskId = $params['taskid'];
        unset($params['id'], $params['source'], $params['source_id'], $params['taskid']);
         $data = $this->post('/v1/workflow/run', [
            'workflow_id' => $wfId,
            'parameters'  => $params,
        ]);
        $params['token_count'] = 0;
         if (isset($data['usage'])){
             $params['token_count'] = $data['usage']['token_count'];
         }

        if (isset($data['code']) && $data['code'] == 0) {
            $params['agent'] = $agent;
            self::requestUrl($params,  self::COZE_WORKFLOW,  self::$uid,  $taskId);
        }
        if (($data['code'] ?? -1) !== 0) {
            $msg = $this->getmsg($data['code']);
            throw new \Exception('工作流 业务错误：' . $msg);
        }
        return $data;

    }




    public function cozechat(array $params)
    {
        $this->getconfig($params['source'], $params['source_id']);
        unset($params['id'], $params['source'], $params['source_id']);
        $url = '/v3/chat';
        $conversation = false;
        if (isset($params['conversation_id']) && $params['conversation_id']) {
            $url = $url . '?conversation_id=' . $params['conversation_id'];
            $conversation = true;
        }
        $body = [
            'bot_id'  => $params['coze_id'],
            'user_id'  => $params['user_id'],
            'auto_save_history'  => true,
            'stream'  => false,
            'additional_messages'  => $params['additional_messages']
        ];

        $client = new Client(['timeout' => 6000, 'verify' => false]);
        $rsp    = $client->post($this->url . $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->pat,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $body,
        ]);
        $contents = $rsp->getBody()->getContents();
        $data     = json_decode($contents, true);
        /* 此时再判断 */
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Coze 返回异常：' . json_last_error_msg());
        }

        if (($data['code'] ?? -1) !== 0) {
            $msg = $this->getmsg($data['code']);
            throw new \Exception('chat 业务错误：' . $msg);
        }
        return $data;
    }


    public function chatstream(array $params)
    {
        $this->getconfig($params['source'], $params['source_id']);
        unset($params['id'], $params['source'], $params['source_id']);
        $url = '/v3/chat';
        if (isset($params['conversation_id']) && $params['conversation_id']) {
            $url = $url . '?conversation_id=' . $params['conversation_id'];
        }
        $body = [
            'bot_id'  => $params['coze_id'],
            'user_id'  => $params['user_id'],
            'auto_save_history'  => true,
            'stream'  => true,
            'additional_messages'  => $params['additional_messages']
        ];


        // 2. 下发 SSE 头
        header('Content-type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('Content-Encoding: none');
        set_time_limit(0);  // 取消 PHP 超时

        // 3. 发起长连接
        $client = new \GuzzleHttp\Client(['timeout' => 0]);
        $resp   = $client->post($this->url . $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->pat,
                'Content-Type'  => 'application/json',
                'Accept'        => 'text/event-stream',
            ],
            'json'   => $body,
            'stream' => true,
        ]);
        $last_error = $usage = [];
        $stream = $resp->getBody();
        $add = [];
        $bot_id = $log_conversation_id = $log_section_id = $log_chat_id = $log_id = $reply = $content = '';
        while (!$stream->eof()) {
            // 一次读一行，直到 \n
            $line = Utils::readLine($stream);
            $line = rtrim($line, "\r\n");
            if (strpos($line, 'data:') !== 0) {
               if (strpos($line, '{"code"') === 0){
                   $res = json_decode($line, true);
                   $msg = $this->getmsg($res['code']);
                   throw new \Exception('chat业务错误：' . $msg);
               }

                continue;
            }
            $json = substr($line, 5);
            $arr  = json_decode($json, true);

//            clogger('---------');
//            clogger(json_encode($arr,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (json_last_error() === JSON_ERROR_NONE && isset($arr['type']) && $arr['type'] === 'answer' && !empty($arr['content'])) {
                if ($content == $arr['content']) {
                    continue;
                }
                $content = $arr['content'];
                if ($content == $reply){
                    continue;
                }

                $reply .= $arr['content'];
             //   clogger($reply);
                $log_chat_id = $arr['chat_id'] ?? '';
                echo "data:" . json_encode([
                    'object' => "loading",
                    'created' => $arr['created_at'] ?? '',
                    'content' => $arr['content'] ?? '',
                    'type' => 'answer',
                    'reasoning_content' => '',
                    'id' => $arr['id'],
                    'chat_id' => $arr['chat_id'],
                    'section_id' => $arr['section_id'],
                    'conversation_id' => $arr['conversation_id'],
                    'task_id' => $arr['section_id'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

                // 输出填充，确保分片立刻向客户端发送
                ob_flush();
                flush();
                continue;
            }
            if (json_last_error() === JSON_ERROR_NONE && isset($arr['status']) && $arr['status'] === 'in_progress') {
                $add[] =[
                    'conversation_id' => $arr['conversation_id'] ?? '',
                    'message_id'      => $arr['id'] ?? '',
                    'type'            => 1,
                    'bot_id'          => $arr['bot_id'],
                    'user_id'         => self::$uid,          // 业务侧用户
                    'role'            => 'user',     // assistant / user
                    'content'         => $params['content'],
                    'create_time'         => $arr['created_at'] ?? time(),
                    'update_time'         => $arr['updated_at'] ?? time(),
                    'status'          => 'in_progress',
                    'token_in'            => 0,     // assistant / user
                    'token_out'            => 0,     // assistant / user
                    'token_total'            => 0,
                    'extra'           => json_encode([
                        'chat_id'           => $arr['id']?? ''
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
                continue;
            }
            // continue;
            if (json_last_error() === JSON_ERROR_NONE && isset($arr['status']) && $arr['status'] === 'completed') {
                $usage = $arr['usage'];
                $last_error = $arr['last_error'];
                $log_id = $arr['id'] ?? '';
                $bot_id = $arr['bot_id'] ?? '';
                $log_section_id = $arr['section_id'] ?? '';
                $log_conversation_id = $arr['conversation_id'] ?? '';
                continue;
            }
            $boolPayload = false;
            if (!is_array($arr)) {
                $boolPayload =  str_contains($arr, 'DONE');
            }
            if ($boolPayload) {
                $params['token_count'] = $usage['token_count'];
                self::requestUrl($params,  self::COZE_AGENT_CHAT,  $params['user_id'],  $log_chat_id);
                $add[] =[
                    'conversation_id' => $log_conversation_id,
                    'message_id'      => $log_chat_id,
                    'type'            => 1,
                    'bot_id'          => $bot_id,
                    'user_id'         => self::$uid,
                    'role'            => 'assistant',
                    'content'         => $reply,
                    'create_time'         =>  time(),
                    'update_time'         =>  time(),
                    'status'          => 'completed',
                    'token_in'            => $usage['input_count'],
                    'token_out'            => $usage['output_count'],
                    'token_total'            => $usage['token_count'],
                    'extra'           => json_encode([
                        'chat_id'           => $log_chat_id,
                        'usage'           => $usage,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
                CozeLog::insertAll($add);
                echo "data:" . json_encode([
                    'object' => 'finished',
                    'created' => time(),
                    'usage' =>  $usage,
                    'last_error' =>  $last_error,
                    'content' => $reply,
                    'reasoning_content' => '',
                    'id' => $log_id,
                    'chat_id' => $log_chat_id,
                    'section_id' => $log_section_id,
                    'conversation_id' => $log_conversation_id,
                    'task_id' => $log_section_id,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                ob_flush();
                flush();
                break;
            }
        }
        exit(1);
    }



    public function cozeretrieve($params): array
    {
        $this->getconfig($params['source'], $params['source_id']);
        unset($params['id'], $params['source'], $params['source_id']);
        $url = '/v3/chat/retrieve';
        $client = new Client(['timeout' => 6000, 'http_errors' => false]);
        $query = [
           'chat_id' => $params['chat_id'],
           'conversation_id' => $params['conversation_id'],
        ];

        $response = $client->get($this->url . $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->pat,
            ],
            'query'   => $query,
        ]);
        $contents = $response->getBody()->getContents();
        $data     = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('retrieve 解析失败：' . json_last_error_msg());
        }
        if (($data['code'] ?? -1) !== 0) {
            $msg = $this->getmsg($data['code']);
            throw new \Exception('retrieve 业务错误：' . $msg);
        }
        if (isset($data['data']['usage'])  && $data['data']['usage']['token_count'] > 0 && isset($data['data']['status']) && $data['data']['status'] == 'completed') {
            self::requestUrl($params,  self::COZE_AGENT_CHAT,  $params['user_id'],  $data['data']['id']);
        }

        return $data;
    }



    public function messagelist($params): array
    {
        $this->getconfig($params['source'], $params['source_id']);
        unset($params['id'], $params['source'], $params['source_id']);
        $url = '/v3/chat/message/list';
        $client = new Client(['timeout' => 6000, 'http_errors' => false]);

        $response = $client->get($this->url . $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->pat,
            ],
            'query'   => $params,
        ]);

        $contents = $response->getBody()->getContents();
        $data     = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('retrieve 解析失败：' . json_last_error_msg());
        }

        if (($data['code'] ?? -1) !== 0) {
            $msg = $this->getmsg($data['code']);
            throw new \Exception('message 业务错误：' . $msg);
        }

        return $data;
    }


    private static function requestUrl( $request,  $scene,  $userId,  $taskId): array
    {

        $requestService = \app\common\service\ToolsService::Coze();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::COZE_AGENT_CHAT => ['coze_agent_chat', AccountLogEnum::TOKENS_DEC_COZE_AGENT_CHAT],
            self::COZE_WORKFLOW => ['coze_workflow', AccountLogEnum::TOKENS_DEC_COZE_WORKFLOW],
        };
        $userId = ltrim($userId, 'user');
        $userId = (int)$userId;
            //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);

        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now'] = time();
        switch ($scene) {
            case self::COZE_AGENT_CHAT:
                $response = $requestService->cozeAgentChat($request);
                break;
            case self::COZE_WORKFLOW:
                $response = $requestService->cozeWorkflow($request);
                break;

            default:
        }
        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {
            $agent = $request['agent'] ?? [];
            $agentpoints = $agent['tokens'] ?? 0;
            $agentdeduction = $agent['deduction'] ?? 0;
            $points = $unit;
            $extrapoints = 0;
            if ($agentpoints > 0) {
                if ($agentdeduction == 0){
                    $extrapoints = round($request['token_count'] / $agentdeduction ,2);

                }else{
                    $extrapoints = $agentpoints;

                }
            }
            $allpoints = round($extrapoints+ $points,2);
            if ($allpoints > 0) {
                $extra = ['附加扣费' => $points, '应用扣费' => $extrapoints, '总扣费' => $allpoints];
                //token扣除
                User::userTokensChange($userId, $allpoints);

                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $allpoints, $taskId, $extra);
            }
        }

        return $response['data'] ?? [];
    }


    public function getmsg($code){
        $code = (int)$code;
        switch ($code){
            case 4000:
                return '成功';
            case 4001:
                return 'Invalid chat（包括chat id错误，chat 找不到）';
            case 4002:
                return 'invalid conversation（包括conversation id错误，conversation 找不到）';
            case 4003:
                return 'meta data 超过限制';
            case 4004:
                return 'additional messages超过限制';
            case 4005:
                return 'invalid message（包括message id错误，message content错误）';
            case 4006:
                return 'invalid bot（包括bot id错误，bot 找不到）';
            case 4007:
                return '流false仅在自动保存为true时允许';
            case 4008:
                return '用户限流';
            case 4009:
                return '已达系统请求上限，请稍后重试';
            case 4010:
                return '当前问题请求的 prompt token 数量超过模型上限';
            case 4011:
                return '当前问题请求的 prompt token 数量超过模型上限';
            case 4005:
                return 'invalid message（包括message id错误，message content错误）';
            case 4006:
                return 'invalid bot（包括bot id错误，bot 找不到）';
            case 4007:
                return '流false仅在自动保存为true时允许';
            case 4008:
                return '用户限流';
            case 4009:
                return '已达系统请求上限，请稍后重试';
            case 4010:
                return '当前问题请求的 prompt token 数量超过模型上限';
            case 4011:
                return '当前账户的 Coze Token 余额不足';
            case 4012:
                return 'invalid model';
            case 4013:
                return '模型错误';
            case 4014:
                return '问题无法回答';
            case 4015:
                return 'bot 未发布到API';
            case 4016:
                return '当前会话已有chat在运行';
            case 4019:
                return '火山Bot调用按量余额不足';
            case 4020:
                return '火山Bot调用超出RPM峰值';
            case 4021:
                return '工作流未配置';
            case 4022:
                return '模型欠费';
            case 4100:
                return '身份验证无效';
            case 4101:
                return '没有权限访问该资源';
            case 4102:
                return '命中风控拦截';
            case 4104:
                return '当前对话不支持取消';

            case 4105:
                return '内容包含敏感信息,请修改后重新提交';
            case 4200:
                return '资源未找到';
            case 4300:
                return '上传文件时文件为空';
            case 4301:
                return '文件上传时超过一个';
            case 4302:
                return '文件上传大小超过限制';
            case 4303:
                return '文件类型不支持';
            case 4304:
                return '文件无效';
            case 4314:
                return '未找到执行记录';
            case 4315:
                return '执行已结束';
            case 5000:
                return '服务器内部错误';
            default:
                return '未知错误';
        }

    }
}
