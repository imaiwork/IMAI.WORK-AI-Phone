<?php

namespace app\api\logic\sv;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\sv\SvCopywritingLibrary;
use app\common\model\user\User;
use think\facade\Db;

/**
 * CopywritingLibraryLogic
 * 文案库逻辑处理
 */
class SvCopywritingLibraryLogic extends SvBaseLogic
{
    /**
     * @desc 添加文案
     * @param array $params
     * @return bool
     */
    const KEYWORD_TO_TITLE = 'keywordToTitle'; //关键词转标题
    const KEYWORD_TO_SUBTITLE = 'keywordToSubtitle'; //关键词转副标题
    const KEYWORD_TO_COPYWRITING = 'keywordToCopywriting'; //关键词转副文案
    const KEYWORD_TO_DETAIL = 'keywordToDetail'; //详情

    /**
     * 添加文案库
     */
    public static function add(array $params)
    {
        try {
            $params['user_id'] = self::$uid;

            $item = [
                '1'=>'内容文案',
                '2'=>'口播文案',
                '3'=>'口播混剪文案'
            ];
            $params['name'] = $item[$params['copywriting_type']] . ' '. date('Y-m-d H:i', time());
            $jsonFields = ['title', 'described', 'oral_copy', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $params[$field] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else if (isset($params[$field])) {
                    $params[$field] = json_encode([]);
                }
            }
            $library = SvCopywritingLibrary::create($params);
            self::$returnData = $library->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取文案库详情
     */
    public static function detail(array $params)
    {
        try {
            $library = SvCopywritingLibrary::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$library) {
                self::setError('文案库不存在');
                return false;
            }
            $data = $library->toArray();
            $jsonFields = ['title', 'described', 'oral_copy', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($data[$field])) {
                    $data[$field] = json_decode($data[$field], true);
                } else {
                    $data[$field] = [];
                }
            }
            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新文案库
     */
    public static function update(array $params)
    {
        try {
            $library = SvCopywritingLibrary::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$library) {
                self::setError('文案库不存在');
                return false;
            }
            $jsonFields = ['title', 'described', 'oral_copy', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $params[$field] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else if (isset($params[$field])) {
                    $params[$field] = json_encode([]);
                }
            }

            SvCopywritingLibrary::where('id', $params['id'])->update($params);
            self::$returnData = SvCopywritingLibrary::find($params['id'])->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除文案库
     */
    public static function del($id)
    {
        try {
            if (is_string($id)) {
                SvCopywritingLibrary::destroy(['id' => $id, 'user_id' => self::$uid]);
            } else {
                SvCopywritingLibrary::whereIn('id', $id)->where('user_id', self::$uid)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function addAi($params){

        try {
            $params['user_id'] = self::$uid;

            $baseTaskData = [
                'content' => $params['keyword'],
                'user_id' => $params['user_id'],
            ];
            if ($params['copywriting_type'] == 1) {
                // 创建三种类型的任务
                $taskTypes = [
                    ['type' => 1, 'scene' => self::KEYWORD_TO_TITLE],
                    ['type' => 2, 'scene' => self::KEYWORD_TO_SUBTITLE],
                ];
                foreach ($taskTypes as $type) {
                    $res = self::prepareTaskData($baseTaskData, $type['type'], $type['scene'], $params);
                    if (isset($res['code']) && $res['code'] == 10005) {
                        throw new \Exception($res['message']);
                    }
                    if ($params['channel'] == 2) {
                        $res['status'] = 2;
                    }
                    $tasks[] = $res;
                }
            } else {
                // 创建单个类型的任务
                $scene = self::getSceneByType(3);
                $res = self::prepareTaskData($baseTaskData, 3, $scene, $params);
                if (isset($res['code']) && $res['code'] == 10005) {
                    throw new \Exception($res['message']);
                }
                if ($params['channel'] == 2) {
                    $res['status'] = 2;
                }
                $tasks[] = $res;
            }
            $data = [
                'title' => [],
                'described' => [],
                'oral_copy' => [],
                'content'=> $params['keyword'],
                'user_id' => self::$uid,
                'copywriting_type' => $params['copywriting_type'],
                'status' =>  $res['status'] ?? 0
            ];
            foreach ($tasks as &$task) {
                $task['title'] = $task['described'] =  $task['oral_copy'] = [];
               if ($task['type'] == 1){
                   $data['title'] = json_decode($task['response_content'], JSON_UNESCAPED_UNICODE);
               }elseif ($task['type'] == 2){
                   $data['described'] = json_decode($task['response_content'], JSON_UNESCAPED_UNICODE);
               }else{
                   $data['oral_copy'] = json_decode($task['response_content'], JSON_UNESCAPED_UNICODE);
               }

            }
            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }



    private static function prepareTaskData(array $baseTaskData, int $type, string $scene, array $params): array
    {
        $taskData = array_merge($baseTaskData, ['type' => $type]);
        $taskData['task_id'] = generate_unique_task_id();

        $requestData = [
            'targetCount' => $params['total_num'],
            'keywords' => $taskData['content'],
            'channel' => $params['channel'],
            'description' => $taskData['content'],
            'notifyUrl' => '/api/sv.copywriting/notify'
        ];

        $response = self::requestUrl($requestData, $scene, $taskData['user_id'], $taskData['task_id']);
        if (isset($response['code']) && $response['code'] == 10005) {
            return $response;
        }


        if ( $params['channel'] == 2){
            $contents = [];
            foreach ($response['content'] as $content){
                $contents[] = [
//                    'type' => $type,
//                    'channel' => $params['channel'],
                    'content'=> $content,
                ];
            }
        }
        $taskData['response_content'] = json_encode($contents);

        return $taskData;
    }

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
        $request['id'] = time();
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

                if (in_array($scene, [
                    self::KEYWORD_TO_TITLE, self::KEYWORD_TO_SUBTITLE,
                    self::KEYWORD_TO_COPYWRITING
                ])) {

                    $count = $response['data']['image_count'] ?? 1;

                    $points = round($count * $unit,2);

                    $extra = ['总条数' => $count, '算力单价' => $unit, '实际消耗算力' => $points];
                }

                //token扣除
                User::userTokensChange($userId, $points);

                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
            }
        }

        if(isset($response['code']) && $response['code'] == 10005){
            return $response;
        }
        return $response['data'] ?? [];
    }

    private static function getSceneByType(int $type): string
    {
        return match ($type) {
            1 => self::KEYWORD_TO_TITLE,
            2 => self::KEYWORD_TO_SUBTITLE,
            default => self::KEYWORD_TO_COPYWRITING,
        };
    }
} 