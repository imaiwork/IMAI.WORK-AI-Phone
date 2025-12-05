<?php

namespace app\api\logic;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\draw\DrawVideo;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use think\facade\Log;
use think\facade\Cache;


class VolcLogic extends ApiLogic
{

    const VOLC_TEXT2VIDEO = 'volc_text_to_video'; //文生视频
    const VOLC_TEXT2VIDEO_STATUS = 'volc_text_to_video_status'; //文生视频状态
    const VOLC_IMAGE2VIDEO = 'volc_image_to_video'; //图生视频
    const VOLC_IMAGE2VIDEO_STATUS = 'volc_image_to_video_status'; //图生视频状态

    /**
     * @desc 获取详情
     * @param $params
     * @return array
     * @date 2025/7/7 10:59
     * @author Rick
     */
    public static function detail($params): array
    {
        $data = DrawVideo::findOrEmpty($params['id'])->toArray();
        if ($data) {
            $data['video_url'] = FileService::getFileUrl($data['video_url']);
        }
        return $data;
    }

    /**
     * @desc 删除视频
     * @param array $data
     * @return bool
     * @date 2025/7/7 10:59
     * @author Rick
     */
    public static function deleteVideo(array $data)
    {
        try {
            if (is_string($data['id'])) {
                DrawVideo::destroy(['id' => $data['id']]);
            } else {
                DrawVideo::whereIn('id', $data['id'])->select()->delete();
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @desc 提交文生视频任务
     * @return bool
     * @date 2025/7/7 10:50
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Rick
     */
    public static function text2video($params)
    {
        $request = [];
        // 组装数据
        foreach ($params as $key => $value) {
            if (in_array($key, ['aspect_ratio', 'text'])) {
                if (!empty($value)) {
                    if ($key == 'aspect_ratio') {
                        $request[$key] = (string)$value;
                    } else {
                        $request[$key] = $value;
                    }
                }
            }
        }

        if (empty($params['text'])) {
            throw new \Exception('请输入文案');
        }

        $response = self::requestUrl($request, self::VOLC_TEXT2VIDEO, self::$uid, 0);

        if (!$response) {
            throw new \Exception('提交文生视频任务错误');
        }

        self::$returnData = $response;
        return true;
    }

    /**
     * @desc 提交图生视频任务
     * @return bool
     * @date 2025/7/7 10:50
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Rick
     */
    public static function image2video($params)
    {
        $request = [];
        // 组装数据
        foreach ($params as $key => $value) {
            if (in_array($key, ['aspect_ratio', 'text', 'prompt', 'image_url'])) {
                if (!empty($value)) {
                    if ($key == 'aspect_ratio') {
                        $request[$key] = (string)$value;
                    } else if ($key == 'image_url') {
                        $request[$key] = "[" . json_encode($value) . "]";
                    } else {
                        $request[$key] = $value;
                    }
                }
            }
        }

        if (empty($params['image_url'])) {
            throw new \Exception('请上传图片');
        }

        $response = self::requestUrl($request, self::VOLC_IMAGE2VIDEO, self::$uid, 1);

        if (!$response) {
            throw new \Exception('提交图生视频任务错误');
        }

        self::$returnData = $response;
        return true;
    }

    /**
     * @desc 获取任务状态
     * @param $request
     * @return true
     * @date 2025/7/7 15:15
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Rick
     */
    public static function getTaskStatus($request)
    {
        $task_id = $request['task_id'];
        $type = $request['type'];
        $result = self::handleResult($task_id, $type, self::$uid);
        self::$returnData = $result;
        return true;
    }

    /**
     * @desc 处理查询结果
     * @param $task_id
     * @param $type
     * @return array|mixed|true|null
     * @date 2025/7/7 15:30
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Rick
     */
    private static function handleResult($task_id, $type, $user_id)
    {
        $result = [];
        $task = DrawVideo::whereOr([
                                    'task_id' => $task_id,
                                    'request_id' => $task_id
                                  ])->findOrEmpty();
        if ($task->isEmpty()) {
            throw new \Exception('参数错误');
        }
        if ($task->task_status === 1) {
            return ['status' => 1, 'msg' => '视频生成成功', 'video_url' => FileService::getFileUrl($task->video_url)];
        }
        //转换
        switch ($type) {
            case 0:
                $scene = self::VOLC_TEXT2VIDEO_STATUS;
                $typeName = '文生视频状态';
                break;
            case 1:
                $scene = self::VOLC_IMAGE2VIDEO_STATUS;
                $typeName = '图生视频状态';
                break;
            default:
                throw new \Exception('参数错误');
        }

        try {
            $response = self::requestUrl(['task_id' => $task_id], $scene, $user_id, $type);
            if (!$response) {
                throw new \Exception('获取[' . $typeName . ']结果错误');
            }

            // 任务的状态，-1:失败，0: 等待中，1: 完成，2: 处理中，3:未通过审核
            if ($response['data']['status'] == 'in_queue') {
                $response['remark'] = '视频处理中';
                self::taskStatus($task, 2, $response);
                $result = ['status' => 2, 'msg' => '视频处理中', 'video_url' => ''];
            } else if ($response['data']['status'] == 'done') {
                $response['remark'] = '视频生成成功';
                $response['data']['video_url'] = FileService::downloadFileBySource($response['data']['video_url'], 'video');
                self::taskStatus($task, 1, $response);
                $result = ['status' => 1, 'msg' => '视频生成成功', 'video_url' => FileService::getFileUrl($response['data']['video_url'])];
            }
        } catch (\Exception $e) {
            self::taskStatus($task, -1, ['remark' => $e->getMessage()]);
            $result = ['status' => -1, 'msg' => '视频生成失败', 'video_url' => ''];
            $change_type = [
                AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_VIDEO,
                AccountLogEnum::TOKENS_DEC_VOLC_IMAGE_TO_VIDEO
            ];
            $refund_num = UserTokensLog::where('user_id', $user_id)->where('change_type', $change_type[$type])->where('action', 1)->where('task_id', $task->task_id)->count();
            if ($refund_num == 0) {
                $points = UserTokensLog::where('user_id', $user_id)->where('change_type', $change_type[$type])->where('task_id', $task->task_id)->value('change_amount');
                if (!empty($points) && $points > 0) {
                    AccountLogLogic::recordUserTokensLog(false, $user_id, $change_type[$type], $points, $task->task_id);
                }
            }
        }
        return $result;
    }

    /**
     * @desc 修改任务状态
     * @param $task
     * @param int $status
     * @param string $remark
     * @return void
     * @date 2025/7/7 15:12
     * @author Rick
     */
    public static function taskStatus($task, int $status = 1, $params = []): void
    {
        $task->task_status = $status;
        if (!empty($params)) {
            $task->rephraser_result = $params['data']['resp_data']['rephraser_result'] ?? '';
            $task->video_url = $params['data']['video_url'] ?? '';
            $task->remark = $params['remark'] ?? '';
        }
        $task->save();
    }

    /**
     * 请求上游接口与计费
     * @param array $request
     * @param string $scene
     * @param int $userId
     * @param int $type
     * @return array
     * @throws \Exception
     */
    private static function requestUrl(array $request, string $scene, int $userId, int $type, $request_id = ''): array
    {

        $requestService = \app\common\service\ToolsService::Volc();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::VOLC_TEXT2VIDEO => ['volc_text_to_video', AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_VIDEO],
            self::VOLC_IMAGE2VIDEO => ['volc_image_to_video', AccountLogEnum::TOKENS_DEC_VOLC_IMAGE_TO_VIDEO],
            default => ['', '']
        };
        //接口单价，检测用户算力是否充足
        $unit = TokenLogService::checkToken($userId, $tokenScene);
        switch ($scene) {
            case self::VOLC_TEXT2VIDEO:
                $response = $requestService->text2Video($request);
                break;
            case self::VOLC_IMAGE2VIDEO:
                $response = $requestService->image2Video($request);
                break;
            case self::VOLC_IMAGE2VIDEO_STATUS:
            case self::VOLC_TEXT2VIDEO_STATUS:
                $response = $requestService->status($request);
                break;
            default:
        }

        if ($tokenScene && isset($response['code']) && $response['code'] == 10000) {
            if ($response['data']['code'] == 10000 && $response['data']['status'] == 10000) {
                $request_id = $request_id ?? $response['data']['request_id'];
                $task = DrawVideo::where('request_id', $request_id)->findOrEmpty();
                if ($task->isEmpty()) {
                    $response['data']['message'] = '请求成功，视频进入生成队列，请等待';
                    $imageUrl = '';
                    if (isset($request['image_url'])) {
                        $imageUrls = json_decode($request['image_url'], true);
                        $imageUrl = is_array($imageUrls) ? $imageUrls[0] : '';
                    }
                    $insert = [
                        'user_id'      => $userId,
                        'task_id'      => $response['data']['data']['task_id'],
                        'request_id'   => $response['data']['request_id'],
                        'model'        => 0,
                        'image_url'    => $imageUrl,
                        'aspect_ratio' => $request['aspect_ratio'],
                        'desc'         => $request['text'] ?? '',
                        'prompt'       => $request['prompt'] ?? '',
                        'task_status'  => 0,
                        'remark'       => $response['data']['message'],
                        'type'         => $type,
                    ];
                    DrawVideo::create($insert);
                } else {
                    $task->task_id = $response['data']['data']['task_id'];
                    $task->task_status = 2;
                    $task->save();
                }

                //计费
                $points = round($unit * 5,2);
                $extra = ['算力单价' => '65算力/秒', '实际消耗算力' => $points];
                User::userTokensChange($userId, $points);
                //扣费记录
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $response['data']['data']['task_id'], $extra);
            }

            if ($response['data']['code'] == 22702) {
                if (!$request_id){
                    $response['data']['message'] = '当前请求人数过多，视频进入生成队列，请等待';
                    $imageUrl = '';
                    if (isset($request['image_url'])) {
                        $imageUrls = json_decode($request['image_url'], true);
                        $imageUrl = is_array($imageUrls) ? $imageUrls[0] : '';
                    }
                    $insert = [
                        'user_id'      => $userId,
                        'task_id'      => '',
                        'request_id'   => $response['data']['request_id'],
                        'model'        => 0,
                        'image_url'    => $imageUrl,
                        'aspect_ratio' => $request['aspect_ratio'],
                        'desc'         => $request['text'] ?? '',
                        'prompt'       => $request['prompt'] ?? '',
                        'task_status'  => 0,
                        'remark'       => $response['data']['message'],
                        'type'         => $type,
                    ];
                    DrawVideo::create($insert);
                }
                $request['request_id'] = !empty($request_id) ? $request_id : $response['data']['request_id'];
                $queue_data = [
                    'request' => $request,
                    'user_id' => $userId,
                    'scene'   => $scene,
                    'type'    => $type
                ];
                Cache::store('redis')->handler()->rPush('video_create_queue', json_encode($queue_data));
            }
        }

        return !empty($response['data']) ? $response['data'] : $response;
    }

    /**
     * @desc 图片转base64
     * @param $url
     * @return string
     * @date 2025/7/7 15:55
     * @author Rick
     */
    private static function imageToStream($url)
    {
        $img = file_get_contents($url);

        return base64_encode($img);
    }


    public static function videoQueue()
    {
        $data = Cache::store('redis')->handler()->lPop('video_create_queue');
        if ($data) {
            $request = json_decode($data, true);
            VolcLogic::requestUrl($request['request'], $request['scene'], $request['user_id'], $request['type'], $request['request']['request_id']);
            Log::write('即梦视频生成任务: ' . $data, 'draw_video');
        }
    }

    public static function videoQueueStatus()
    {
        $data = DrawVideo::where('task_status', 2)->select()->toArray();
        if (!empty($data)) {
            foreach ($data as $item) {
                VolcLogic::handleResult($item['task_id'],$item['type'],$item['user_id']);
            }
            Log::write('即梦视频生成状态查询', 'draw_video');
        }
    }

}
