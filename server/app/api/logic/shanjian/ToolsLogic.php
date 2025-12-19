<?php


namespace app\api\logic\shanjian;


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
    service\ToolsService,
    service\transcoding\AliyunService,
    service\transcoding\QcloudService,
    service\transcoding\QiniuService};
use think\facade\Config;
use think\facade\Db;

/**
 * Class UserLogic
 * @package app\shopapi\logic
 */
class ToolsLogic extends BaseLogic
{
    const NEWS_MIXCUT_TITLE = 'newsMixcutTitle'; //新闻标题

    public static function getCopywriting($params)
    {
        try {
            // 验证必要参数
            if (!isset($params['number']) || !isset($params['keywords'])) {
                throw new \Exception('缺少必要参数:生成数量或生成内容');
            }
            $num = $params['number'];
            $params['channelVersion'] = $params['channelVersion'] ?? 7;
            [$tokenScene, $tokenCode] = match ($params['channelVersion']) {
                7 => ['news_mixcut_title', AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_TITLE],
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

                case 7:
                    $res = $requestService->newsmixcuttitle($params);
                    break;

                default:
            }

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
}
