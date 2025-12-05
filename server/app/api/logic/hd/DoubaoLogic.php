<?php

namespace app\api\logic\hd;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\draw\DrawVideo;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use think\facade\Log;
use think\facade\Cache;


class DoubaoLogic extends ApiLogic
{

    const DOUBAO_TEXT2VIDEO = 'doubao_txt_to_video'; //文生视频
    const DOUBAO_IMAGE2VIDEO = 'doubao_img_to_video'; //图生视频
    const DOUBAO_VIDEO_STATUS = 'doubao_video_status'; //视频状态

    /**
     * @desc 获取详情
     * @param $params
     * @return array
     * @date 2025/7/7 10:59
     * @author dagouzi
     */
    public static function videoDetail($params): array
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
     * @author dagouzi
     */
    public static function videoDelete(array $data)
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
     * @author dagouzi
     */
    public static function text2video($params)
    {
        if (empty($params['text'])) {
            throw new \Exception('请输入视频生成提示词');
        }

        $params['duration'] = $params['duration'] ?? 5;
        $params['request_id'] = $params['request_id'] ?? generate_unique_task_id();

        TokenLogService::checkToken(self::$uid, self::DOUBAO_TEXT2VIDEO);
        $response = self::requestUrl($params, self::DOUBAO_TEXT2VIDEO, self::$uid, 0);

        if (!$response) {
            throw new \Exception('提交文生视频任务错误');
        }

        if (isset($response['code']) && (int)$response['code'] === 10005) {
            throw new \Exception($response['message']);
        }

        self::$returnData = $response;
        return true;
    }

    /**
     * @desc 提交图生视频任务
     * @return bool
     * @date 2025/7/7 10:50
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public static function image2video($params)
    {

        if (empty($params['image_url'])) {
            throw new \Exception('请上传图片');
        }

        $params['duration'] = $params['duration'] ?? 5;
        $params['request_id'] = $params['request_id'] ?? generate_unique_task_id();

        TokenLogService::checkToken(self::$uid, self::DOUBAO_TEXT2VIDEO);
        
        $response = self::requestUrl($params, self::DOUBAO_IMAGE2VIDEO, self::$uid, 1);

        if (!$response) {
            throw new \Exception('提交图生视频任务错误');
        }

        if (isset($response['code']) && (int)$response['code'] === 10005) {
            throw new \Exception($response['message']);
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
     * @author dagouzi
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
     * @author dagouzi
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
        $scene = self::DOUBAO_VIDEO_STATUS;
        $typeName = '视频状态';

        try {
            $response = self::requestUrl(['id' => $task_id], $scene, $user_id, $type);
            //print_r($response['content']['video_url']);die;
            if (!$response) {
                throw new \Exception('获取[' . $typeName . ']结果错误');
            }

            // 任务的状态，-1:失败，0: 等待中，1: 完成，2: 处理中，3:未通过审核 4 取消任务
            if ($response['status'] == 'queued') {
                $response['remark'] = '排队中';
                self::taskStatus($task, 0, $response);
                $result = ['status' => 0, 'msg' => '排队中', 'video_url' => ''];
            } else if ($response['status'] == 'running') {
                $response['remark'] = '任务运行中';
                self::taskStatus($task, 2, $response);
                $result = ['status' => 2, 'msg' => '任务运行中', 'video_url' => ''];
            } else if ($response['status'] == 'cancelled') {
                $response['remark'] = '取消任务';
                self::taskStatus($task, 4, $response);
                $result = ['status' => 4, 'msg' => '取消任务', 'video_url' => ''];
                self::refund($user_id, $type, $task);
            } else if ($response['status'] == 'failed') {
                $response['remark'] = '任务失败';
                self::taskStatus($task, -1, $response);
                $result = ['status' => -1, 'msg' => '任务失败', 'video_url' => ''];
                self::refund($user_id, $type, $task);
            } else if ($response['status'] == 'succeeded') {
                $response['remark'] = '视频生成成功';
                $response['video_url'] = FileService::downloadFileBySource($response['content']['video_url'], 'video');
                //print_r($response);die;
                self::taskStatus($task, 1, $response);
                $result = ['status' => 1, 'msg' => '视频生成成功', 'video_url' => FileService::getFileUrl($response['video_url'])];
            }
        } catch (\Exception $e) {
            self::taskStatus($task, -1, ['remark' => $e->getMessage()]);
            $result = ['status' => -1, 'msg' => '视频生成失败', 'video_url' => ''];
            self::refund($user_id, $type, $task);
        }
        return $result;
    }

    private static function refund(int $user_id, int $type, DrawVideo $task)
    {
        $change_type = [
            AccountLogEnum::TOKENS_DEC_DOUBAO_TEXT_TO_VIDEO,
            AccountLogEnum::TOKENS_DEC_DOUBAO_IMAGE_TO_VIDEO
        ];
        $refund_num = UserTokensLog::where('user_id', $user_id)->where('change_type', $change_type[$type])->where('action', 1)->where('task_id', $task->task_id)->count();
        if ($refund_num == 0) {
            $points = UserTokensLog::where('user_id', $user_id)->where('change_type', $change_type[$type])->where('task_id', $task->task_id)->value('change_amount') ?? 0;
            AccountLogLogic::recordUserTokensLog(false, $user_id, $change_type[$type], $points, $task->task_id);
        }
    }

    /**
     * @desc 修改任务状态
     * @param $task
     * @param int $status
     * @param string $remark
     * @return void
     * @date 2025/7/7 15:12
     * @author dagouzi
     */
    public static function taskStatus($task, int $status = 1, $params = []): void
    {
        $task->task_status = $status;
        if (!empty($params)) {
            $task->rephraser_result = $params['resp_data']['rephraser_result'] ?? '';
            $task->video_url = $params['video_url'] ?? '';
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

        $requestService = \app\common\service\ToolsService::Doubao();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::DOUBAO_TEXT2VIDEO => ['doubao_txt_to_video', AccountLogEnum::TOKENS_DEC_DOUBAO_TEXT_TO_VIDEO],
            self::DOUBAO_IMAGE2VIDEO => ['doubao_img_to_video', AccountLogEnum::TOKENS_DEC_DOUBAO_IMAGE_TO_VIDEO],
            default => ['', '']
        };
        //接口单价，检测用户算力是否充足
        $unit = TokenLogService::checkToken($userId, $tokenScene);
        switch ($scene) {
            case self::DOUBAO_TEXT2VIDEO:
                $response = $requestService->text2Video($request);
                break;
            case self::DOUBAO_IMAGE2VIDEO:
                $response = $requestService->image2Video($request);
                break;
            case self::DOUBAO_VIDEO_STATUS:
                $response = $requestService->detail($request);
                break;
            default:
        }


        if ($tokenScene && isset($response['code']) && $response['code'] == 10000) {
            if (isset($response['data']['id'])) {
                $request_id = $response['data']['request_id'] ?? '';
                $response['data']['task_id'] = $response['data']['id'];

                $task = DrawVideo::where('request_id', $request_id)->findOrEmpty();
                if ($task->isEmpty()) {
                    $response['data']['message'] = '请求成功，视频进入生成队列，请等待';
                    $imageUrl = $request['image_url'] ?? '';

                    $insert = [
                        'user_id'      => $userId,
                        'task_id'      => $response['data']['id'],
                        'request_id'   => $request_id,
                        'model'        => 1,
                        'image_url'    => $imageUrl,
                        'aspect_ratio' => $request['aspect_ratio'] ?? '16:9',
                        'desc'         => $request['text'] ?? '',
                        'prompt'       => $request['text'] ?? '',
                        'task_status'  => 2,
                        'remark'       => $response['data']['message'],
                        'type'         => $type,
                    ];
                    DrawVideo::create($insert);
                } else {
                    $task->task_id = $response['data']['id'];
                    $task->task_status = 2;
                    $task->save();
                }

                //计费
                $points = round($unit * 5,2);
                $extra = ['算力单价' => $unit . '算力/秒', '实际消耗算力' => $points];
                User::userTokensChange($userId, $points);
                //扣费记录
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $response['data']['id'], $extra);
            }

            if (isset($response['data']['code']) && $response['data']['code'] == 22805) {
                if (!$request_id) {
                    $response['data']['message'] = '当前请求人数过多，视频进入生成队列，请等待';
                    $imageUrl = $request['image_url'] ?? '';
                    $insert = [
                        'user_id'      => $userId,
                        'task_id'      => '',
                        'request_id'   => $request['request_id'] ?? '',
                        'model'        => 1,
                        'image_url'    => $imageUrl,
                        'aspect_ratio' => $request['aspect_ratio'] ?? '',
                        'desc'         => $request['text'] ?? '',
                        'prompt'       => $request['text'] ?? '',
                        'task_status'  => 0,
                        'remark'       => $response['data']['message'],
                        'type'         => $type,
                    ];
                    DrawVideo::create($insert);
                }
                //$request['request_id'] = $request_id;
                $queue_data = [
                    'request' => $request,
                    'user_id' => $userId,
                    'scene'   => $scene,
                    'type'    => $type
                ];
                Cache::store('redis')->handler()->rPush('doubao_video_create_queue', json_encode($queue_data));
            }
        }

        return !empty($response['data']) ? $response['data'] : $response;
    }

    /**
     * @desc 图片转base64
     * @param $url
     * @return string
     * @date 2025/7/7 15:55
     * @author dagouzi
     */
    private static function imageToStream($url)
    {
        $img = file_get_contents($url);

        return base64_encode($img);
    }


    public static function videoQueue()
    {
        $data = Cache::store('redis')->handler()->lPop('doubao_video_create_queue');
        if ($data) {
            $request = json_decode($data, true);
            DoubaoLogic::requestUrl($request['request'], $request['scene'], $request['user_id'], $request['type'], $request['request']['request_id']);
            Log::write('豆包视频生成任务: ' . $data, 'draw_video');
        }
    }

    public static function videoQueueStatus()
    {
        $data = DrawVideo::where('task_status', 2)->select()->toArray();
        if (!empty($data)) {
            foreach ($data as $item) {
                DoubaoLogic::handleResult($item['task_id'], $item['type'], $item['user_id']);
            }
            Log::write('豆包视频生成状态查询', 'draw_video');
        }
    }
}
