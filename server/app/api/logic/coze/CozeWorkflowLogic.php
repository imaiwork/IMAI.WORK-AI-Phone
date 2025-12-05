<?php

namespace app\api\logic\coze;

use app\common\model\coze\CozeAgent;
use app\common\model\coze\CozeLog;
use app\common\model\coze\CozeWorkflow;
use app\common\service\FileService;
use think\facade\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class CozeWorkflowLogic extends CozeLogic
{


    public  function run($params)
    {

        try {
            $agent = CozeAgent::where('id', $params['id'])
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('source_id', self::$uid)
                            ->where('source', CozeAgent::SOURCE_USER);
                    })->whereOr('source', CozeAgent::SOURCE_ADMIN);
                })
                ->findOrEmpty()->toArray();
            if (!$agent) {
                throw new \Exception('智能体不存在' );
            }
            if ($agent['type'] != CozeAgent::TYPE_WORKFLOW) {
                throw new \Exception('工作流不存在' );
            }
            $flow = CozeWorkflow::where('coze_agent_id', $params['id'])
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('source_id', self::$uid)
                            ->where('source', CozeAgent::SOURCE_USER);
                    })->whereOr('source', CozeAgent::SOURCE_ADMIN);
                })
                ->findOrEmpty()->toArray();
            if (!$flow) {
                throw new \Exception('工作流不存在' );
            }
            $output = $flow['outputs']?? [];
            $params['agent'] = $agent;

            $data = $this->workflowrun($agent['coze_id'], $params);
            if ($data['code'] != 0){
                throw new \Exception(  $data['msg'] );
            }
            $add = [];
            if (isset($data['data'])) {
                $logid = $data['detail']['logid'] ?? '';
                $usage = $data['usage'] ?? '';
                $content = json_decode($data['data'],true);
                // 根据 outputs 配置与 $content 的字段进行匹配，返回相应的 type
                $outputConfigs = [];
                if (is_string($output) && $output !== '') {
                    $decoded = json_decode($output, true);
                    if (is_array($decoded)) {
                        $outputConfigs = $decoded;
                    }
                } elseif (is_array($output)) {
                    $outputConfigs = $output;
                }

                $matchedTypes = [];
                if (is_array($content) && is_array($outputConfigs)) {
                    foreach ($outputConfigs as $cfg) {
                        $fieldKey = $cfg['fields'] ?? '';
                        if ($fieldKey !== '' && array_key_exists($fieldKey, $content)) {
                            $matchedTypes[$fieldKey] = $cfg['type'] ?? null;
                            $value = $content[$cfg['fields']];
                            switch ($cfg['type']) {
                                case 'image':
                                case 'video':
                                case 'audio':
                                    unset($content[$cfg['fields']]);
                                    if (is_array($value)){
                                       foreach ($value as $v){

                                           $url = FileService::downloadFileBySource($v,$cfg['type']);
                                           if ($url){
                                               $content[$cfg['fields']][] = FileService::getFileUrl($url);
                                           }
                                       }
                                    }else{
                                        $url = FileService::downloadFileBySource($value,$cfg['type']);
                                        if ($url){
                                            $content[$cfg['fields']] = FileService::getFileUrl($url);
                                        }
                                    }
                                    break;
                            }
                        }
                    }
                }

                $add[] =[
                    'conversation_id' => $logid,
                    'message_id'      => $params['taskid'],
                    'type'            => 2,
                    'bot_id'          => $agent['coze_id'],
                    'user_id'         => self::$uid,          // 业务侧用户
                    'role'            => 'workflow',     // assistant / user
                    'content'         => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'create_time'         => $value['created_at'] ?? time(),
                    'update_time'         => $value['updated_at'] ?? time(),
                    'status'          => 'running' ,
                    'token_in'          => $usage['input_count'] ?? 0 ,
                    'token_out'          => $usage['output_count'] ?? 0 ,
                    'token_total'          => $usage['token_count'] ?? 0 ,

                    'extra'           => json_encode([
                        'usage'           => $usage,
                        'logid'             => $logid,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];

            }
            if (count($add) > 0){
                CozeLog::insertAll($add);
            }

            self::$returnData = $content;
            return true;
        } catch (\Exception $e) {
            throw new \Exception(   $e->getMessage() );
        }
    }
}