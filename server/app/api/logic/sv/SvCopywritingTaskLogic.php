<?php

namespace app\api\logic\sv;


use app\api\logic\service\TokenLogService;
use app\common\model\sv\SvCopywriting;
use app\common\model\sv\SvCopywritingContent;
use app\common\model\sv\SvCopywritingTask;
use think\facade\Db;
use think\facade\Log;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;

/**
 * SvCopywritingTaskLogic
 * @desc 文案逻辑处理
 */
class SvCopywritingTaskLogic extends SvBaseLogic
{


    const KEYWORD_TO_TITLE = 'keywordToTitle'; //关键词转标题
    const KEYWORD_TO_SUBTITLE = 'keywordToSubtitle'; //关键词转副标题
    const KEYWORD_TO_COPYWRITING = 'keywordToCopywriting'; //关键词转副文案
    const KEYWORD_TO_DETAIL = 'keywordToDetail'; //详情



    public static function queryCopywritingCron()
    {


        $copywritingtasks = SvCopywritingTask::where('status', 1)
            ->where('channel', 1)
            ->limit(3)
            ->select()->toArray();

        if (!$copywritingtasks) {
            self::setError('没有要查询的文案信息');
            return false;
        }
        foreach ($copywritingtasks as $copywritingtask) {
            $responsedata = json_decode($copywritingtask['response_content'], true);
            $update['tries'] = $copywritingtask['tries'] + 1;
            $requestData = [
                'id' => $responsedata['id'],
                'type' => $copywritingtask['type']
            ];

            $copywriting = SvCopywriting::where('id', $copywritingtask['copywriting_id'])->findOrEmpty();
            $task_id = $copywritingtask['task_id'];
            $user_id = $copywriting->user_id;
            $response = self::requestUrl($requestData, self::KEYWORD_TO_DETAIL, $user_id,   $task_id );
            $status = $response['State'] ?? '';

            if ($status == 'Finished') {


                $copywritingcontent = [];
                $contents = json_decode($response['content'], true);
                foreach ($contents as $content) {
                    $copywritingcontent[] = [
                        'user_id' => $user_id,
                        'copywriting_id' => $copywritingtask['id'],
                        'type' => $copywritingtask['type'],
                        'content' => $content
                    ];
                }
                (new SvCopywritingContent())->saveAll($copywritingcontent);

                $update['status'] = 2;
                SvCopywritingTask::where('id',$copywritingtask['id'])->save($update);
                $copywriting->success_num = $copywriting->success_num + 1;
                if ($copywriting->add_type != 0) {
                    $copywriting->status = 2;
                }
                if ($copywriting->add_type == 0 && $copywriting->success_num == 3) {
                    $copywriting->status = 2;
                }
                $copywriting->save();
            }

            if ($status == 'Failed') {

                $userId = $user_id;
                $alltype = [
                    '1' => AccountLogEnum::KEYWORD_TO_TITLE,
                    '2' => AccountLogEnum::KEYWORD_TO_SUBTITLE,
                    '3' => AccountLogEnum::KEYWORD_TO_COPYWRITING
                ];
                $typeID = $alltype[$copywritingtask['type']];
                $response = self::requestUrl($requestData, self::KEYWORD_TO_DETAIL, $user_id, $task_id);
                if ($response['State'] == 'Failed') {

                    //查询是否已返还
                    if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $task_id)->count() == 0) {

                        $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $task_id)->value('change_amount') ?? 0;

                        AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $task_id);
                    }
                    $update['status'] = 3;
                    SvCopywritingTask::where('id',$copywritingtask['id'])->save($update);
                    $copywriting->error_num = $copywriting->error_num + 1;
                    $num = $copywriting->error_num + $copywriting->success_num;
                    if ($num == 3) {
                        $copywriting->status = 3;
                        $copywriting->save();
                    }
                }


            }

        }
    }
        /**
         * 请求上游接口与计费
         * @param array $request
         * @param string $scene
         * @param int $userId
         * @param string $taskId
         * @return array
         * @throws \Exception
         */
        private static function requestUrl(array $request, string $scene, int $userId, string $taskId): array
        {

            $requestService = \app\common\service\ToolsService::Sv();
            [$tokenScene, $tokenCode] = match ($scene) {
                self::KEYWORD_TO_TITLE => ['keyword_to_title', AccountLogEnum::KEYWORD_TO_TITLE],
                self::KEYWORD_TO_SUBTITLE => ['keyword_to_subtitle', AccountLogEnum::KEYWORD_TO_SUBTITLE],
                self::KEYWORD_TO_COPYWRITING => ['keyword_to_copywriting', AccountLogEnum::KEYWORD_TO_COPYWRITING],
                self::KEYWORD_TO_DETAIL => ['keyword_to_detail', ''],
            };
            //计费
            $unit = TokenLogService::checkToken($userId, $tokenScene);

            // 添加辅助参数
            $request['task_id'] = $taskId;
            $request['user_id'] = $userId;
            $request['now'] = time();
            switch ($scene) {

                case self::KEYWORD_TO_TITLE:
                    $response = $requestService->title($request);
                    break;
                case self::KEYWORD_TO_SUBTITLE:
                    $response = $requestService->subtitle($request);
                    break;
                case self::KEYWORD_TO_COPYWRITING:
                    $response = $requestService->text($request);
                    break;
                case self::KEYWORD_TO_DETAIL:
                    $response = $requestService->detail($request);
                    break;
                default:
            }
            //成功响应，需要扣费
            if (isset($response['code']) && $response['code'] == 10000) {

                $points = $unit;
                if ($points > 0) {

                    $extra = [];

                    //合成视频按时长扣费
                    if (in_array($scene, [
                        self::KEYWORD_TO_TITLE, self::KEYWORD_TO_SUBTITLE,
                        self::KEYWORD_TO_COPYWRITING
                    ])) {

                        $count = $request['data']['image_count'] ?? 1;

                        $points = round($count * $unit,2);

                        $extra = ['总条数' => $count, '算力单价' => $unit, '实际消耗算力' => $points];
                    }

                    //token扣除
                    User::userTokensChange($userId, $points);

                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
                }
            }

            return $response['data'] ?? [];
        }

}