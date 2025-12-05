<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\ConfigService;
use app\common\service\FileService;
use think\exception\HttpResponseException;
use think\facade\Log;

class ShanjianAnchorLogic extends ApiLogic
{
    const SHANJIAN_AVATAR = 'shanjianAvatar';
    const SHANJIAN_VOICE = 'shanjianVoice';
    const SHANJIAN_VIDEO = 'shanjianVideo';


    public static function add(array $params)
    {
        $name = $params['name'] ?? '形象合成'.date('YmdHi');
        try {
            $task_id = generate_unique_task_id();
            $param['task_id'] = $task_id;
            $param['user_id'] = self::$uid;
            $param['videoUrl'] = $params['anchor_url'] ?? "";
            $param['authVideoUrl'] = $params['authorized_url'] ?? "";
            $param['authText'] =  ConfigService::get('digital_human', 'shanjian_auth', '闪剪AI');
            $scene = self::SHANJIAN_AVATAR;
            $response = self::requestUrl($param, $scene, self::$uid, $task_id);
            Log::channel('shanjian')->write('闪剪形象'.json_encode($response,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (isset($response['code']) && $response['code'] == 10000) {

                $data = [
                    'user_id' => self::$uid,
                    'task_id' => $task_id,
                    'pic' => $params['pic'] ?? '',
                    'name' => $name,
                    'anchor_url' => $params['anchor_url'] ?? '',
                    'authorized_pic' => $params['authorized_pic'] ?? '',
                    'authorized_url' => $params['authorized_url'] ?? '',
                    'create_time' => time(),
                ];
                $model = new ShanjianAnchor();
                $model->save($data);
                $data['id'] = $model->id;
                self::$returnData = $data;
                return true;
            }else{
                $msg = $response['message'] ?? '检验失败';
                self::setError($msg);
                return false;
            }



        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                ShanjianAnchor::destroy(['id' => $id, 'user_id' => self::$uid]);
            } else {
                ShanjianAnchor::whereIn('id', $id)->where('user_id', self::$uid)
                    ->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail(array $params)
    {
        $model = ShanjianAnchor::where('id', $params['id'])
            ->where('user_id', self::$uid)
            ->findOrEmpty();
        if ($model->isEmpty()) {
            self::setError('记录不存在');
            return false;
        }
        self::$returnData = $model->toArray();
        return true;
    }


    private static function requestUrl(array $request, string $scene, int $userId, string $taskId): array
    {

        $requestService = \app\common\service\ToolsService::Shanjian();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::SHANJIAN_AVATAR => ['human_avatar_shanjian', AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN],
            self::SHANJIAN_VOICE => ['human_voice_shanjian', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN],
            self::SHANJIAN_VIDEO => ['human_video_shanjian', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN],
        };

        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);
        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now'] = time();
        switch ($scene) {
            case self::SHANJIAN_AVATAR:
                $response = $requestService->fastTrain($request);
                break;
            case self::SHANJIAN_VOICE:
                $response = $requestService->voiceTrain($request);
                break;
            case self::SHANJIAN_VIDEO:
                $response = $requestService->virtualmanBroadcast($request);
                break;
            default:
        }
        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {

            $points = $unit;

            if ($points > 0) {

                $extra = [];
                switch ($scene) {
                    case self::SHANJIAN_AVATAR:
                        $extra = ['算力单价' => $unit, '实际消耗算力' => $points];
                        break;
                    case self::SHANJIAN_VOICE:
                        $extra = ['算力单价' => $unit, '实际消耗算力' => $points];
                        break;
                    case self::SHANJIAN_VIDEO:
                        $duration = $response['data']['duration'] ?? 1;
                        $points = round($duration * $unit,2);
                        $extra = ['视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points];
                        break;
                    default:
                }
                //token扣除
                User::userTokensChange($userId, $points);

                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
            }
        }

        return $response;
    }

    public static function updateAnchor(array $data): bool
    {
        $model = ShanjianAnchor::where('task_id', $data['task_id'])->where('user_id', $data['user_id'])->where('status', 1)->select()
            ->each(function ($item) use ($data) {

                if (in_array($data['status'], ['failed', 'succeed'])) {
                    $item->status = ($data['status'] == 'succeed') ? 3 : 2;
                    // TODO 失败退费
                    if ($item->status == 2) {
                        self::refundTokens($item->user_id, $data['taskId'], $data['task_id'], 'human_avatar_shanjian');
                        $item->remark = $data['errorMessage'];
                    }else{
                        $item->anchor_id = $data['result']['virtualmanId'] ;
                        $param = [
                            'audioUrl' => $item->anchor_url,
                            'user_id' => $item->user_id,
                            'task_id' => $item->task_id,
                        ];
                        try {
                            $scene = self::SHANJIAN_VOICE;
                            $response = self::requestUrl($param, $scene, $item->user_id, $item->task_id);
                            Log::channel('shanjian')->write('闪剪音色结果返回'.json_encode($response,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            if (isset($response['code']) && $response['code'] == 10000) {
                                $item->status = 4;
                            }else{
                                $item->status = 5;
                                $item->remark = $response['message'] ?? '';
                            }
                        } catch (HttpResponseException $e) {
                            $item->status = 5;
                            $response = $e->getResponse();   // 先拿到 Response 对象
                            $responsedata     = $response->getData(); // 返回的就是数组
                            Log::channel('shanjian')->write('闪剪音色结果返回2'.json_encode($responsedata,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                            $item->remark = $responsedata['msg'] ?? '';
                        }catch (\Exception $e) {
                            $item->status = 5;
                            $item->remark = $e->getMessage() ?? '';
                        }
                    }


                }
                $item->save();
            });

        return true;
    }



    /**
     * @desc 退费
     * @param int $userId
     * @param int $id
     * @param string $taskId
     * @param string $type
     * @return bool
     */
    public static function refundTokens(int $userId, string $result_id, string $taskId, string $type): bool
    {

        try {
            [$typeIndex, $typeID] = match ($type) {
                'human_avatar_shanjian' => [1, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SHANJIAN],
                'human_voice_shanjian' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN],
                'human_video_shanjian' => [4, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN],
            };
            // 请求查询接口
            $requestParams = [
                'taskId' => $result_id,
                'task_id' => $taskId
            ];
            $response = \app\common\service\ToolsService::Shanjian()->status($requestParams);
            if (isset($response['code']) && $response['code'] == 10000) {
                return true;
            }
            $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
            //查询是否已返还
            if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('shanjian')->write('闪剪退费失败'.$e->getMessage());
            return false;
        }
    }

    public static function updateVoice(array $data): bool
    {
        $model = ShanjianAnchor::where('task_id', $data['task_id'])->where('user_id', $data['user_id'])->where('status', 4)->select()
            ->each(function ($item) use ($data) {

                if (in_array($data['status'], ['failed', 'succeed'])) {
                    $item->status = ($data['status'] == 'succeed') ? 6 : 5;
                    // TODO 失败退费
                    if ($item->status == 5) {
                        self::refundTokens($item->user_id,  $data['taskId'],$data['task_id'], 'human_voice_shanjian');
                        $item->remark = $data['errorMessage'];
                    }else{
                        $item->voice_id = $data['result']['speakerId'] ?? '';
                        $item->voice_url = FileService::downloadFileBySource($data['result']['demoAudioUrl'], 'audio');

                    }

                }
                $item->save();
            });

        return true;
    }

    public static function authorizedList($data)
    {
        //TODO 新增分页
        $pageNo = ($data['page_no'] - 1) * $data['page_size'];
        $pageSize = $data['page_size'];
        $name = $data['name'] ?? '';

        $result = ShanjianAnchor::where(['user_id' => self::$uid])
            ->where('status','>',2)
            ->limit($pageNo, $pageSize)
            ->when($name, function ($query) use ($name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->order('create_time', 'desc')
            ->group('authorized_url')
            ->select()
            ->toArray();

        $count = ShanjianAnchor::where(['user_id' => self::$uid])
            ->when($name, function ($query) use ($name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->where('status','>',2)
            ->group('authorized_url')
            ->count();



        $data = [
            'lists' => $result,
            'count' =>  $count,
            'page_no' => $data['page_no'],
            'page_size' => $data['page_size'],
        ];
        return $data;
    }

}


