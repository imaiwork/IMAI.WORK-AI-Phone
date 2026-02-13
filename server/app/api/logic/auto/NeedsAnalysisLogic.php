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
    /**
     * 已废弃
     */
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

    /**
     * 已废弃
     */
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

    /**
     * 已废弃
     */
    public static function analysisOld($params)
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

    public static function analysis($params)
    {
        try {
            $request['Content'] = json_encode($params['contents'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $request['Model']   = 1;
            $res                = self::flowRequest($request);
            if (!empty($res['result'])) {
                $result['contents']    = $params['contents'];
                $result['result']      = ["Analysis_Form"=>json_decode($res['result']['Analysis_Form'],true),"Operations"=>""];
                self::$returnData      = $result;
                return true;
            } else {
                throw new \Exception('分析失败');
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function report($params)
    {
        try {
            $request['Content'] = json_encode($params['contents'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $request['Model']    = '';
            $res                 = self::flowRequest($request);
            if (!empty($res['result']['Operations'])) {
                $report = AutoNeedsAnalysis::where('device_code', $params['device_code'])->where('user_id', self::$uid)->where('step', 2)->findOrEmpty();
                if ($report->isEmpty()){
                    $insert             = [
                        'user_id'         => self::$uid,
                        'contents'        => $request['Content'],
                        'result'          => json_encode(["Analysis_Form"=>"","Operations"=>json_decode($res['result']['Operations'],true)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'is_draft'        => 1,
                        'step'            => 2,
                        'device_code'     => $params['device_code']
                    ];
                    $report             = AutoNeedsAnalysis::create($insert);
                }else{
                    $report->result = json_encode(["Analysis_Form"=>"","Operations"=>json_decode($res['result']['Operations'],true)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $report->is_draft = 1;
                    $report->save();
                }
                $result             = $report->refresh()->toArray();
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
                    AccountLogLogic::recordUserTokensLog(true, self::$uid, AccountLogEnum::TOKENS_DEC_AUTOMATION_ACCOUNT_IP_ANALYSIS, $points, '', $extra);
                }
                return true;
            } else {
                throw new \Exception('报告生成失败');
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail($params)
    {
        try {
            $analysis = AutoNeedsAnalysis::where('device_code','=', $params['device_code'])->where('user_id','=', self::$uid)->where('step','=',1)->findOrEmpty();
            $report = AutoNeedsAnalysis::where('device_code','=', $params['device_code'])->where('user_id','=', self::$uid)->where('step','=',2)->findOrEmpty();

            if ($analysis->isEmpty()){
                $result['analysis'] = [];
                $result['report']   = [];
                self::$returnData   = $result;
                return true;
            }
            $analysis = $analysis->toArray();
            if ($report->isEmpty()){
                $result['analysis'] = ['result' => json_decode($analysis['result'], true)];
                $result['report']   = [];
            } else{
                $report   = $report->toArray();
                $result['analysis'] = ['result' => json_decode($analysis['result'], true)];
                $result['report']   = ['result' => json_decode($report['result'], true)];
            }
            self::$returnData      = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function add($params)
    {
        try {
            $analysis = AutoNeedsAnalysis::where('device_code', $params['device_code'])->where('user_id', self::$uid)->where('step',1)->findOrEmpty();
            $arr              = [
                'basicInformation'             => $params['basicInformation'], //基础信息
                'ipTalent'                     => $params['ipTalent'], //IP人选
                'ipStyle'                      => $params['ipStyle'], //IP风格
                'targetCustomers'              => $params['targetCustomers'], //目标客户
                'productServiceFeatures'       => $params['productServiceFeatures'], //产品/服务特点
                'brandStory'                   => $params['brandStory'], //品牌故事
                'contentPreferences'           => $params['contentPreferences'], //内容偏好
                'brandAchievementsPositioning' => $params['brandAchievementsPositioning'], //品牌成就与位置
                'accountStage'                 => $params['accountStage'], //账号阶段
            ];
            if ($analysis->isEmpty()){
                $insert           = [
                    'user_id'     => self::$uid,
                    'device_code' => $params['device_code'],
                    'contents'    => json_encode($params['contents'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'result'      => json_encode(["Analysis_Form"=>$arr,"Operations"=>""], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'is_draft'    => 1,
                    'step'        => 1,
                ];
                $analysis         = AutoNeedsAnalysis::create($insert);
            }else{
                $analysis->result = json_encode(["Analysis_Form"=>$arr,"Operations"=>""], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $analysis->save();
            }
            $res              = $analysis->refresh()->toArray();
            $res['contents']  = $params['contents'];
            $res['result']    = json_decode($res['result'], true);
            self::$returnData = $res;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function update($params)
    {
        try {
            $where = [['step', '=', $params['step']],['user_id', '=', self::$uid]];
            $find = AutoNeedsAnalysis::where('device_code', $params['device_code'])->where($where)->findOrEmpty();
            if (!$find->isEmpty()) {
//                $arr          = [
//                    'operation_persona' => $params['operation_persona'], //运营人设
//                    'business_type'     => $params['business_type'], //业务类型
//                    'account_stage'     => $params['account_stage'], //账号阶段
//                    'target_audience'   => $params['target_audience'], //客户对象
//                    'core_pain'         => $params['core_pain'], //客户核心痛点
//                    'main_platform'     => $params['main_platform'], //主要运营平台
//                    'platform_focus'    => $params['platform_focus'], //平台侧重点
//                    'content_style'     => $params['content_style'], //内容风格倾向
//                    'main_block'        => $params['main_block'], //当前最大运营卡点
//                    'risk_tolerance'    => $params['risk_tolerance'], //账号风险承受度
//                    'benchmark_account' => $params['benchmark_account'], //对标账号
//                ];
                $arr = [
                    'basicInformation'             => $params['basicInformation'], //基础信息
                    'ipTalent'                     => $params['ipTalent'], //IP人选
                    'ipStyle'                      => $params['ipStyle'], //IP风格
                    'targetCustomers'              => $params['targetCustomers'], //目标客户
                    'productServiceFeatures'       => $params['productServiceFeatures'], //产品/服务特点
                    'brandStory'                   => $params['brandStory'], //品牌故事
                    'contentPreferences'           => $params['contentPreferences'], //内容偏好
                    'brandAchievementsPositioning' => $params['brandAchievementsPositioning'], //品牌成就与位置
                    'accountStage'                 => $params['accountStage'], //账号阶段
                ];
                $find->result = json_encode(["Analysis_Form"=>$arr,"Operations"=>""], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $find->save();
            } else {
                self::setError('任务不存在');
                return false;
            }
            $res                = $find->refresh()->toArray();
            $res['contents']    = json_decode($res['contents'], true);
            $res['result']      = json_decode($res['result'], true);
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
        $client            = new Client(['timeout' => 600, 'verify' => false]);
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
        $client            = new Client(['timeout' => 600, 'verify' => false]);
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
        if (!empty($data['data'])) {
            return ['result' => $data['data']];
        }
        return [];
    }
}
