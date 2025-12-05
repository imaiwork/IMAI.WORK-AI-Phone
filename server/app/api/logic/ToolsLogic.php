<?php


namespace app\api\logic;


use app\api\logic\service\TokenLogService;
use app\common\{enum\notice\NoticeEnum,
    enum\user\AccountLogEnum,
    logic\AccountLogLogic,
    logic\BaseLogic,
    model\ChatPrompt,
    model\human\HumanVideoTask,
    model\sv\SvVideoTask,
    model\tools\ToolsLog,
    model\user\User,
    service\ConfigService,
    service\FileService,
    service\tools\Stability,
    service\transcoding\AliyunService,
    service\transcoding\QcloudService,
    service\transcoding\QiniuService};
use think\facade\Config;
use think\facade\Db;

/**
 * 会员逻辑层
 * Class UserLogic
 * @package app\shopapi\logic
 */
class ToolsLogic extends BaseLogic
{
    /**
     * @notes 删除
     * @param array $params
     * @param int $userId
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(array $params, int $userId): bool
    {
        $id = $params['id'] ?? 0;
        try {
            $detail = ToolsLog::where(['id' => $id, 'user_id' => $userId])->findOrEmpty();
            if ($detail->isEmpty()) {
                throw new \Exception("记录查找异常");
            }
            ToolsLog::destroy(['id' => $id, 'user_id' => $userId]);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 工具
     * @param array $params
     * @param int $userId
     * @return bool
     * @throws \Exception
     * @author L
     * @data 2024/6/17 17:28
     */
    public static function chat(array $params, int $userId): bool
    {
        $value = json_decode($params['value'], true);
        $saveData = [
            'user_id' => $userId,
            'tools_id' => $params['tools_id'],
            'ask' => $value['prompt'],
            'mode' => $value['mode'],
            'reply' => '',
            'task_time' => 0,
            'status' => 2,
        ];
        $log = ToolsLog::create($saveData);

        $now = time();
        try {
            $result = [];
            switch ($params['tools_id']) {
                case 1:
                    $result = (new Stability($userId, $value))->toImage();
                    break;
            }
            if ($result['code'] !== 200) {
                $log->status = 3;
                $log->save();
                throw new \Exception("生成异常，请重新尝试");
            }
            $log->task_time = time() - $now;
            $log->status = 1;
            $log->reply = $result['file_url'];
            $log->save();
            $saveData['file_url'] = $result['file_url'];
            $saveData['id'] = $log->id;
            self::$returnData = $saveData;
            return true;
        } catch (\Exception $exception) {
            $log->status = 3;
            $log->save();
            self::setError($exception->getMessage());
            return false;
        }
    }


    public static function getSearchTerms($params)
    {
        try {
            // 验证必要参数
            if (!isset($params['targetCount']) || !isset($params['keyword'])) {
                throw new \Exception('缺少必要参数:生成数量或生成内容');
            }
            $num = (int)$params['targetCount'];
            $unit = TokenLogService::checkToken($params['user_id'], 'sph_search_terms');
            $tokenCode = AccountLogEnum::TOKENS_DEC_SPH_SEARCH_TERMS;
            $res = \app\common\service\ToolsService::Sv()->getSearchTerms($params);
            if ($res['code'] == 10000) {
                $points = round($num * $unit, 2);
                if ($points > 0) {
                    //token扣除
                    User::userTokensChange($params['user_id'], $points);
                    $task_id = generate_unique_task_id();
                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, $params['user_id'], $tokenCode, $points, $task_id);
                } else {
                    self::setError('扣费有问题');
                    return false;
                }
                if (isset($res['data']['content']) && count($res['data']['content']) > 0) {
                    self::$returnData = $res['data']['content'];
                } else {
                    self::setError('生成失败');
                    return false;
                }
            } else {
                self::setError('生成失败2');
                return false;
            }
            return true;
        } catch (\Exception $e) {

            self::setError($e->getMessage());
            return false;
        }
    }


    public static function getPrompt($request)
    {
        //查询是否存在
        $ChatPrompt = $prompt = ChatPrompt::select();
        if ($prompt->isEmpty()) {
            self::setError('提示词不存在');
            return false;
        }
        try {
            self::$returnData = $ChatPrompt->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function clip()
    {
        try {
            $response = \app\common\service\ToolsService::Auth()->clipNotice();
            self::$returnData = $response;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getMatrixCopywriting($params){
        try {
            // 验证必要参数
            if (!isset($params['number']) || !isset($params['keywords'])) {
                throw new \Exception('缺少必要参数:生成数量或生成内容');
            }
            $num = $params['number'];
            $unit = TokenLogService::checkToken($params['user_id'], 'matrix_copywriting' );
            $task_id = generate_unique_task_id();
            $params['task_id'] = $task_id;
            $tokenCode = AccountLogEnum::TOKENS_DEC_MATRIX_COPYWRITING;
            $res = \app\common\service\ToolsService::Sv()->getMatrixCopywriting($params);
            if ($res['code'] == 10000) {

                $points = round($num * $unit,2);

                if ($points > 0){
                    //token扣除
                    User::userTokensChange($params['user_id'], $points);
                    $extra = ['生成文案条数' => $num, '算力单价' => $unit, '实际消耗算力' => $points];

                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, $params['user_id'], $tokenCode, $points, $task_id,$extra);
                }else{
                    self::setError('扣费有问题');
                    return false;
                }
                if (isset($res['data']) && count($res['data']) >0){
                    self::$returnData = $res['data'];
                }else{
                    self::setError('生成失败');
                    return false;
                }
            }else{
                self::setError('生成失败2');
                return false;
            }
            return true;
        } catch (\Exception $e) {

            self::setError($e->getMessage());
            return false;
        }
    }


    public static function transcoding($request)
    {
        try {
            $config = [
                'default' => ConfigService::get('storage', 'default', 'local'),
                'engine' => ConfigService::get('storage') ?? ['local' => []],
            ];
            switch ($config['default']) {
                case 'aliyun':
                    $Aliyun = new AliyunService($config['engine']['aliyun']);
                    $response = $Aliyun->main($request);
                    break;
                case 'qcloud':
                    $Qcloud = new QcloudService($config['engine']['qcloud']);
                    $response = $Qcloud->main($request);
                    break;
                case 'qiniu':
                    $Qiniu = new QiniuService($config['engine']['qiniu']);
                    $response = $Qiniu->main($request);
                    break;
                default:
                    self::setError('不支持本地转码');
                    return false;
            }

            if(!$response['code']){
                self::setError($response['message']);
                return false;
            }
            self::$returnData['jobid'] = $response['jobid'];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function searchTranscoding($request)
    {
        try {
            $config = [
                'default' => ConfigService::get('storage', 'default', 'local'),
                'engine' => ConfigService::get('storage') ?? ['local' => []],
            ];

            switch ($config['default']) {
                case 'aliyun':
                    $Aliyun = new AliyunService($config['engine']['aliyun']);
                    $response = $Aliyun->search($request);
                    break;
                case 'qcloud':
                    $Qcloud = new QcloudService($config['engine']['qcloud']);
                    $response = $Qcloud->search($request);
                    break;
                case 'qiniu':
                    $Qiniu = new QiniuService($config['engine']['qiniu']);
                    $response = $Qiniu->search($request);
                    break;
                default:
                    self::setError('不支持本地转码');
                    return false;

            }

            if(!$response['code']){
                self::setError($response['message']);
                return false;
            }
            self::$returnData =$response;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


}
