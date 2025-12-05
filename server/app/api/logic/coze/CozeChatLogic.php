<?php

namespace app\api\logic\coze;

use app\common\model\coze\CozeAgent;
use app\common\model\coze\CozeLog;
use think\facade\Db;
use app\common\model\coze\CozeWorkflow;
use think\facade\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class CozeChatLogic extends CozeLogic
{

    /**
     * 统一查询可访问的智能体
     */
    private function findAccessibleAgent(int $agentId): array
    {
        $agent = CozeAgent::where('id', $agentId)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('source_id', self::$uid)
                        ->where('source', CozeAgent::SOURCE_USER);
                })->whereOr('source', CozeAgent::SOURCE_ADMIN);
            })
            ->where('type', CozeAgent::TYPE_AGENT)
            ->findOrEmpty()
            ->toArray();

        if (empty($agent)) {
            throw new \Exception('智能体不存在');
        }
        return $agent;
    }

    /**
     * 合并智能体参数到请求参数
     */
    private function enrichParamsWithAgent(array $params, array $agent): array
    {
        $params['source'] = $agent['source'];
        $params['coze_id'] = $agent['coze_id'];
        $params['source_id'] = $agent['source_id'];
        $params['user_id'] = 'user' . self::$uid;
        return $params;
    }

    /**
     * 统一处理接口返回
     */
    private function handleCozeResponse(array $data, bool $stripUsage = false): array
    {
        if (!isset($data['code'])) {
            throw new \Exception('服务返回异常');
        }
        if ((int)$data['code'] !== 0) {
            $message = is_array($data['msg'] ?? null) ? json_encode($data['msg'], JSON_UNESCAPED_UNICODE) : ($data['msg'] ?? '未知错误');
            throw new \Exception($message);
        }
        if (isset($data['data'])) {
            foreach ($data['data'] as $key => &$value) {
               if (isset($value['type']) && in_array($value['type'],['function_call', 'tool_output','verbose']) ){
                 unset($data['data'][$key]);
               }
            }
        }
        if ($stripUsage && isset($data['data']['usage'])) {
            unset($data['data']['usage']);
        }
        return $data['data'] ?? [];
    }

    public function chat($params)
    {
        try {
            $agent = $this->findAccessibleAgent((int)$params['id']);
            $params = $this->enrichParamsWithAgent($params, $agent);

            // 追加用户消息
            $params['additional_messages'][] = [
                'role' => 'user',
                'content' => $params['content'],
                'content_type' => 'text',
            ];
            if ((int)$agent['stream'] === 1){
                throw new \Exception('该智能体是流式返回');
            }
            $resp = $this->cozechat($params);
            $add = [];
            if (isset($resp['data'])) {
                $logid = $resp['detail']['logid'] ?? '';
                $value = $resp['data'];
                $add[] =[
                    'conversation_id' => $value['conversation_id'],
                    'message_id'      => $value['id'],
                    'type'            => 1,
                    'bot_id'          => $value['bot_id'],
                    'user_id'         => self::$uid,          // 业务侧用户
                    'role'            => 'user',     // assistant / user
                    'content'         => $params['content'],
                    'create_time'         => $value['created_at'] ?? time(),
                    'update_time'         => $value['updated_at'] ?? time(),
                    'status'          => $value['status'] === 'in_progress' ? 'in_progress' : 'completed',
                    'extra'           => json_encode([
                        'chat_id'           => $value['id'],
                        'logid'             => $logid,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];

            }
            if (count($add) > 0){
                CozeLog::insertAll($add);
            }
            $payload = $this->handleCozeResponse($resp);
            self::$returnData = $payload;
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function retrieve($params)
    {
        try {
            $agent = $this->findAccessibleAgent((int)$params['id']);
            $params = $this->enrichParamsWithAgent($params, $agent);
            $params['agent'] = $agent;
            $resp = $this->cozeretrieve($params);
            $payload = $this->handleCozeResponse($resp, true);
            self::$returnData = $payload;
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function chatmessage($params)
    {
        try {
            $agent = $this->findAccessibleAgent((int)$params['id']);
            $params = $this->enrichParamsWithAgent($params, $agent);
            $add = [];
            $resp = $this->messagelist($params);
            $conversation_id = $message_id = $bot_id = $content = '';
            if (isset($resp['data'])) {
                $logid = $resp['detail']['logid'] ?? '';
                foreach ($resp['data'] as $key => &$value) {
//                    clogger(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    if (isset($value['type']) && in_array($value['type'],['function_call', 'tool_output','verbose'])){
                        unset($resp['data'][$key]);
                        continue;
                    }
                    if (isset($value['type']) && $value['type'] == 'answer'){
                        $content .= $value['content'];
                        $message_id = $value['chat_id'];
                        $bot_id = $value['bot_id'];
                        $conversation_id = $value['conversation_id'];
                        $created_at =  $value['created_at'] ?? time();
                        $updated_at =  $value['updated_at'] ?? time();
                    }
                }
                $resp['data'] = array_values($resp['data']);
            }
            if ( $content != ''){
                $add[] =[
                    'conversation_id' => $conversation_id,
                    'message_id'      => $message_id,
                    'type'            => 1,
                    'bot_id'          => $bot_id,
                    'user_id'         => self::$uid,
                    'role'            => 'assistant',
                    'content'         => $content,
                    'create_time'     => $created_at,
                    'update_time'     => $updated_at,
                    'status'          => 'completed',
                    'extra'           => json_encode([
                        'chat_id'           => $message_id,
                        'type'              => 'answer',
                        'logid'             => $logid,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
                CozeLog::insertAll($add);
            }
            $payload = $this->handleCozeResponse($resp, true);
            self::$returnData = $payload;
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    public function stream($params){
        try {
            $agent = $this->findAccessibleAgent((int)$params['id']);
            $params = $this->enrichParamsWithAgent($params, $agent);

            // 追加用户消息
            $params['additional_messages'][] = [
                'role' => 'user',
                'content' => $params['content'],
                'content_type' => 'text',
            ];

            // 非流式模式由服务端直接聚合返回
            if ((int)$agent['stream'] === 0) {
                throw new \Exception('该智能体不是流式返回');
            }
            $params['agent'] = $agent;
            $resp = $this->chatstream($params);

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}