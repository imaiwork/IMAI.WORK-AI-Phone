<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\api\logic\coze\CozeChatLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\auto\AutoNeedsAnalysis;
use app\common\model\coze\CozeLog;
use app\common\model\user\User;
use GuzzleHttp\Client;

/**
 * 自动任务需求分析逻辑
 * Class NeedsAnalysisLogic
 * @package app\api\logic\auto
 */
class NeedsAnalysisLogic extends ApiLogic
{
    public static function chat($params)
    {
        try {
            $params['user_id']           = self::$uid;
            $params['special_chat_type'] = 'automation';
            $res                         = (new \app\api\logic\coze\CozeChatLogic)->stream($params);
            if ($res) {
                return true;
            }
            throw new \Exception(CozeChatLogic::getError());
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function chatRetrieve($params)
    {
        try {
            $params['user_id']           = self::$uid;
            $params['special_chat_type'] = 'automation';
            $res                         = (new \app\api\logic\coze\CozeChatLogic)->retrieve($params);
            if ($res) {
                self::$returnData = CozeChatLogic::$returnData;
                return true;
            } else {
                throw new \Exception(CozeChatLogic::getError());
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function analysis($params)
    {
        try {
            $chatLogs = CozeLog::where('conversation_id', $params['conversation_id'])->order('id', 'asc')->select();
            if ($chatLogs->isEmpty()) {
                throw new \Exception('自动任务对话缺失，请重新对话');
            }
            $chatLogs = $chatLogs->toArray();
            foreach ($chatLogs as $chatLog) {
                $params['input']['messageList'][] = ['role' => $chatLog['role'], 'content' => $chatLog['content']];
            }
            $params['input'] = json_encode($params['input'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $taskId          = generate_unique_task_id();
            $res             = self::flowRequest($params);
            if (!empty($res['result'])) {
                AutoNeedsAnalysis::destroy(['conversation_id' => $params['conversation_id']]);
                foreach ($res['result'] as $key => $value){
                    if ($value == '无明确信息'){
                        $res['result'][$key] = '';
                    }
                }
                $insert             = [
                    'user_id'         => self::$uid,
                    'conversation_id' => $params['conversation_id'],
                    'contents'        => $params['input'],
                    'result'          => json_encode($res['result'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'is_draft'        => 1,
                    'task_id'         => $taskId
                ];
                $result             = AutoNeedsAnalysis::create($insert);
                $result             = $result->refresh()->toArray();
                $result['contents'] = json_decode($result['contents'], true);
                $result['result']   = json_decode($result['result'], true);
                self::$returnData   = $result;
                $automationService = \app\common\service\ToolsService::Automation();
                $center = $automationService->analysis($params);
                if ($center['code'] == 10000){
                    //计费单价
                    $unit = TokenLogService::checkToken(self::$uid, 'automation_account_ip_analysis');
                    $points = $unit;
                    $extra = ['扣费项目' => '账号Ip人设分析报告','算力单价' => '5算力/次', '实际消耗算力' => $points];
                    //token扣除
                    User::userTokensChange(self::$uid, $points);
                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, self::$uid, AccountLogEnum::TOKENS_DEC_AUTOMATION_ACCOUNT_IP_ANALYSIS, $points, $taskId, $extra);
                }
                return true;
            } else {
                throw new \Exception('自动任务需求分析失败');
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail($params)
    {
        try {
            $contents = AutoNeedsAnalysis::where('conversation_id', $params['conversation_id'])->findOrEmpty();
            if ($contents->isEmpty()) {
                throw new \Exception('自动任务对话缺失，请重新对话');
            }
            $result             = $contents->toArray();
            $result['contents'] = json_decode($result['contents'], true);
            $result['result']   = json_decode($result['result'], true);
            self::$returnData   = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function update($params)
    {
        try {
            $find = AutoNeedsAnalysis::where('conversation_id', $params['conversation_id'])->where('is_draft', 1)->findOrEmpty();
            if (!$find->isEmpty()) {
                $arr          = [
                    'operation_persona' => $params['operation_persona'], //运营人设
                    'business_type'     => $params['business_type'], //业务类型
                    'account_stage'     => $params['account_stage'], //账号阶段
                    'target_audience'   => $params['target_audience'], //客户对象
                    'core_pain'         => $params['core_pain'], //客户核心痛点
                    'main_platform'     => $params['main_platform'], //主要运营平台
                    'platform_focus'    => $params['platform_focus'], //平台侧重点
                    'content_style'     => $params['content_style'], //内容风格倾向
                    'main_block'        => $params['main_block'], //当前最大运营卡点
                    'risk_tolerance'    => $params['risk_tolerance'], //账号风险承受度
                    'benchmark_account' => $params['benchmark_account'], //对标账号
                ];
                $find->result = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $find->save();
            } else {
                self::setError('自动任务运营分析草稿不存在');
                return false;
            }
            $res              = $find->refresh()->toArray();
            $res['result']    = json_decode($res['result'], true);
            $res['contents']  = json_decode($res['contents'], true);
            self::$returnData = $res;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 智能体请求
     */
    private static function robotRequest($params): bool
    {
        $automationService = \app\common\service\ToolsService::Automation();
        $url               = $automationService::URL;
        $bot_id            = $automationService::BOT_ID;
        $body              = [
            'bot_id'     => $bot_id,
            'parameters' => $params,
        ];
        $request           = [
            'headers' => [
                'Authorization' => 'Bearer ' . $automationService::TOKEN,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $body
        ];
        $client            = new Client(['timeout' => 6000, 'verify' => false]);
        $rsp               = $client->post($url, $request);
        $contents          = $rsp->getBody()->getContents();
        $data              = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        if (($data['code'] ?? -1) !== 0) {
            return false;
        }
        $data['data'] = json_decode($data['data'], true);
        if ($data['data']['continue'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 工作流请求
     */
    private static function flowRequest($params): array
    {
        $automationService = \app\common\service\ToolsService::Automation();
        $url               = $automationService::URL;
        $workflow_id       = $automationService::WORKFLOW_ID;
        $body              = [
            'workflow_id' => $workflow_id,
            'parameters'  => $params,
        ];
        $request           = [
            'headers' => [
                'Authorization' => 'Bearer ' . $automationService::TOKEN,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $body
        ];
        $client            = new Client(['timeout' => 6000, 'verify' => false]);
        $rsp               = $client->post($url, $request);
        $contents          = $rsp->getBody()->getContents();
        $data              = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        if (($data['code'] ?? -1) !== 0) {
            return [];
        }
        $data['data'] = json_decode($data['data'], true);
        if (!empty($data['data']['output'])) {
            return ['result' => $data['data']['output']];
        }
        return [];
    }
}
