<?php


namespace app\api\logic\sv;


use app\api\logic\service\TokenLogService;
use app\common\{
    enum\notice\NoticeEnum,
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
    service\transcoding\AliyunService,
    service\transcoding\QcloudService,
    service\transcoding\QiniuService
};
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

    const NEWS_MIXCUT_TITLE = 'newsMixcutTitle'; //新闻标题


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


    public static function getMatrixCopywriting($params)
    {
        try {
            // 验证必要参数
            if (!isset($params['number']) || !isset($params['keywords'])) {
                throw new \Exception('缺少必要参数:生成数量或生成内容');
            }
            $num = $params['number'];
            $unit = TokenLogService::checkToken($params['user_id'], 'matrix_copywriting');
            $task_id = generate_unique_task_id();
            $params['task_id'] = $task_id;
            $tokenCode = AccountLogEnum::TOKENS_DEC_MATRIX_COPYWRITING;
            $res = \app\common\service\ToolsService::Sv()->getMatrixCopywriting($params);
            if ($res['code'] == 10000) {

                $points = round($num * $unit, 2);

                if ($points > 0) {
                    //token扣除
                    User::userTokensChange($params['user_id'], $points);
                    $extra = ['生成文案条数' => $num, '算力单价' => $unit, '实际消耗算力' => $points];
                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, $params['user_id'], $tokenCode, $points, $task_id, $extra);
                }
                if (isset($res['data']) && count($res['data']) > 0) {
                    self::$returnData = $res['data'];
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

            if (!$response['code']) {
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

            if (!$response['code']) {
                self::setError($response['message']);
                return false;
            }
            self::$returnData = $response;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }



    public static function getCopywriting($params)
    {
        try {
            // 验证必要参数
            if (!isset($params['number']) || !isset($params['keywords'])) {
                throw new \Exception('缺少必要参数:生成数量或生成内容');
            }
            $num = $params['number'];
            $params['channelVersion'] = $params['channelVersion'] ?? 8;
            [$tokenScene, $tokenCode] = match ($params['channelVersion']) {
                8 => ['combined_picture_title', AccountLogEnum::TOKENS_DEC_COMBINED_PICTURE_TITLE],
            };
            $userId = $params['user_id'];

            //计费
            $unit = TokenLogService::checkToken($userId, $tokenScene);
            $requestService = \app\common\service\ToolsService::Coze();
            // 添加辅助参数
            $params['user_id'] = $userId;
            $params['id'] = $params['now'] = time();
            $task_id = generate_unique_task_id();
            $params['task_id'] = $task_id;

            switch ( $params['channelVersion'] ) {
                case 8:
                    $res = $requestService->title($params);
                    break;

                default:
            }
            if ($res['code'] == 10000) {

                $points = round($num * $unit, 2);

                if ($points > 0) {
                    //token扣除
                    User::userTokensChange($params['user_id'], $points);
                    $extra = ['生成标题条数' => $num, '算力单价' => $unit, '实际消耗算力' => $points];

                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, $params['user_id'], $tokenCode, $points, $task_id, $extra);
                }
                if (isset($res['data']) && count($res['data']) > 0) {
                    self::$returnData = $res['data'];
                } else {
                    self::setError('生成失败');
                    return false;
                }
            } else {
                self::setError($res['message']);
                return false;
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
