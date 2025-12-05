<?php

declare(strict_types=1);

namespace app\common\traits;

use think\facade\Log;
use Channel\Client as ChannelClient;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;
use app\common\model\ChatPrompt;
use app\common\enum\user\AccountLogEnum;
use app\api\logic\service\TokenLogService;
use app\common\logic\AccountLogLogic;
use app\common\model\user\User;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\sv\SvDeviceTask;
use app\common\enum\DeviceEnum;



use Predis\Client as redisClient;
use think\cache\driver\Redis;

trait SphTaskTrait
{

    public static function sphSend(string $task_id): void
    {
        try {
            $task = SvCrawlingTask::where('id', $task_id)->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('任务不存在');
            }

            $rows = SvCrawlingTask::alias('ct')
                ->field('*')
                ->join('sv_crawling_task_device_bind b', 'ct.id = b.task_id and b.exec_keyword = ""')
                ->where('ct.id', $task_id)
                ->where('ct.status', 'in', [0, 1])
                ->fetchSql(false)
                ->select()
                ->toArray();
            if (empty($rows)) {
                throw new \Exception('暂时没有需要执行的设备');
            }

            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            $flag = false;
            $msg = '';
            foreach ($rows as $row) {
                if($flag){
                    continue;
                }
                $_deviceTaskStatus = self::redis()->get("xhs:device:{$row['device_code']}:taskStatus");
                if (!empty($_deviceTaskStatus)) {
                    $deviceTaskStatus = json_decode(($_deviceTaskStatus), true);
                    if (is_null($deviceTaskStatus)) {
                        $deviceTaskStatus = json_decode(unserialize($_deviceTaskStatus), true);
                    }
                    if (is_array($deviceTaskStatus) && $deviceTaskStatus['taskStatus'] == 'running') {
                        $datetime = date('Y-m-d H:i:s', strtotime($deviceTaskStatus['time']) + (int)$deviceTaskStatus['duration']);
                        $msg = "设备{$row['device_code']}正在执行小红书任务，请在【{$datetime}】秒后重试";
                        $time = strtotime($deviceTaskStatus['time']) + (int)$deviceTaskStatus['duration'];
                        if (time() < $time) {
                            $flag = true;
                            continue;
                            //throw new \Exception($msg);
                        }
                    }
                }

                if($flag){
                    throw new \Exception($msg);
                }




            // self::sendAppExec($row, 1);
            // usleep(200 * 1000); //200毫秒
            $task = [
                'id' => $row['id'],
                'task_id' => $task_id,
                'platform' => self::getPlatform((int)$row['type']),
                'device_code' => $row['device_code'],
                'keywords' => json_decode($row['keywords'], true),
                'exec_number' => 10000,
                'is_chat' => $row['chat_type'],
                'chat_number' => $row['chat_number'],
                'chat_interval_time' => $row['chat_interval_time'],
                'add_type' => $row['add_type'],
                'remarks' => json_decode($row['remarks'], true),
                'add_remark_enable' => $row['add_remark_enable'] ?? 0,
                'add_number' => $row['add_number'],
                'add_interval_time' => $row['add_interval_time'],
                'greeting_content' => $row['greeting_content'],
                //'greeting_content' => self::createGreetingContents($row, $row['user_id']),
                'status' => 0,
                'ocr_type' => $row['ocr_type'],
                'crawl_type' => $row['crawl_type'],
                'create_time' => $row['create_time'],
            ];
                self::sendAppExec($row, 4);
                usleep(200 * 1000); //200毫秒
                $task = [
                    'id' => $row['id'],
                    'task_id' => $task_id,
                    'platform' => self::getPlatform((int)$row['type']),
                    'device_code' => $row['device_code'],
                    'keywords' => json_decode($row['keywords'], true),
                    'exec_number' => 10000,
                    'is_chat' => $row['chat_type'],
                    'chat_number' => $row['chat_number'],
                    'chat_interval_time' => $row['chat_interval_time'],
                    'add_type' => $row['add_type'],
                    'remarks' => json_decode($row['remarks'], true),
                    'add_remark_enable' => $row['add_remark_enable'] ?? 0,
                    'add_number' => $row['add_number'],
                    'add_interval_time' => $row['add_interval_time'],
                    'greeting_content' => $row['greeting_content'],
                    //'greeting_content' => self::createGreetingContents($row, $row['user_id']),
                    'status' => 0,
                    'ocr_type' => $row['ocr_type'],
                    'crawl_type' => $row['crawl_type'],
                    'create_time' => $row['create_time'],
                ];

            $data = array(
                'type' => 20,
                'appType' => 1,
                'content' => json_encode($task, JSON_UNESCAPED_UNICODE),
                'deviceId' => $row['device_code'],
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data);

                $data = array(
                    'type' => 20,
                    'appType' => 4,
                    'content' => json_encode($task, JSON_UNESCAPED_UNICODE),
                    'deviceId' => $row['device_code'],
                    'appVersion' => '2.1.1',
                    'messageId' => 0,
                );
                self::setLog($data);

                $channel = "device.{$row['device_code']}.message";
                ChannelClient::publish($channel, [
                    'data' => json_encode($data)
                ]);
                //SvCrawlingTaskDeviceBind::where('task_id', $task_id)->where('device_code', $row['device_code'])->update(['status' => 0, 'update_time' => time()]);

                self::redis()->set("xhs:device:{$row['device_code']}:taskStatus", json_encode([
                    'taskStatus' => 'standby',
                    'taskType' => 'setSph',
                    'msg' => '执行视频号',
                    'duration' => 0,
                    'time' => date('Y-m-d H:i:s', time()),
                    'scene' => 'sph'
                ], JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }


    public static function sphCronSend(SvDeviceTask $dtask): void
    {
        $task = SvCrawlingTask::where('id', $dtask->sub_task_id)->findOrEmpty();
        if ($task->isEmpty()) {
            throw new \Exception('任务不存在');
        }

        $find = SvCrawlingTask::alias('ct')
            ->field('*')
            ->join('sv_crawling_task_device_bind b', 'ct.id = b.task_id and b.exec_keyword = ""')
            ->where('ct.id', $task->id)
            ->where('b.device_code', $dtask->device_code)
            ->where('ct.status', 'in', [0, 1])
            ->fetchSql(false)
            ->findOrEmpty();
        if ($find->isEmpty()) {
            throw new \Exception('暂时没有需要执行的设备');
        }
        ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        $_deviceTaskStatus = self::redis()->get("xhs:device:{$find->device_code}:taskStatus");
        if (!empty($_deviceTaskStatus)) {
            $deviceTaskStatus = json_decode(($_deviceTaskStatus), true);
            if (is_null($deviceTaskStatus)) {
                $deviceTaskStatus = json_decode(unserialize($_deviceTaskStatus), true);
            }
            if (is_array($deviceTaskStatus) && $deviceTaskStatus['taskStatus'] == 'running') {
                $datetime = date('Y-m-d H:i:s', strtotime($deviceTaskStatus['time']) + (int)$deviceTaskStatus['duration']);
                $msg = "设备正在执行小红书任务，请在【{$datetime}】秒后重试";
                $time = strtotime($deviceTaskStatus['time']) + (int)$deviceTaskStatus['duration'];
                if (time() < $time) {
                    throw new \Exception($msg);
                }
            }
        }
            // self::sendAppExec($find, 1);
            // usleep(200 * 1000); //200毫秒
            $task = [
                'id' => $find['id'],
                'task_id' => $task->id,
                'platform' => self::getPlatform((int)$find['type']),
                'device_code' => $find['device_code'],
                'keywords' => json_decode($find['keywords'], true),
                'exec_number' => 10000,
                'is_chat' => $find['chat_type'],
                'chat_number' => $find['chat_number'],
                'chat_interval_time' => $find['chat_interval_time'],
                'add_type' => $find['add_type'],
                'remarks' => json_decode($find['remarks'], true),
                'add_remark_enable' => $find['add_remark_enable'] ?? 0,
                'add_number' => $find['add_number'],
                'add_interval_time' => $find['add_interval_time'],
                'greeting_content' => $find['greeting_content'],
                //'greeting_content' => self::createGreetingContents($row, $row['user_id']),
                'status' => 0,
                'ocr_type' => $find['ocr_type'],
                'crawl_type' => $find['crawl_type'],
                'create_time' => $find['create_time'],
            ];

            $data = array(
                'type' => 20,
                'appType' => 1,
                'content' => json_encode($task, JSON_UNESCAPED_UNICODE),
                'deviceId' => $find['device_code'],
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data);

            $channel = "device.{$find['device_code']}.message";
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);
            //SvCrawlingTaskDeviceBind::where('task_id', $task_id)->where('device_code', $row['device_code'])->update(['status' => 0, 'update_time' => time()]);

            self::redis()->set("xhs:device:{$find['device_code']}:taskStatus", json_encode([
                'taskStatus' => 'standby',
                'taskType' => 'setSph',
                'msg' => '执行视频号',
                'duration' => 0,
                'time' => date('Y-m-d H:i:s', time()),
                'scene' => 'sph'
            ], JSON_UNESCAPED_UNICODE));
    }

    private static function sendAppExec($find, $appType)
    {
        $app = SvDeviceRpa::where('device_code', $find['device_code'])->where('app_type', $appType)->findOrEmpty();
        if ($app->isEmpty()) {
            throw new \Exception('当前设备未绑定app');
        }
        $payload = [
            "messageId" => 2,
            "type" => 90, //执行那个app指令
            "appType" => $appType,
            "content" => json_encode([
                "deviceId" => $find['device_code'],
                "appType" => $appType,
                'msg' => '视频号',
                'task_id' => $app->id
            ], JSON_UNESCAPED_UNICODE),
            "deviceId" => $find['device_code'],
            "appVersion" => "2.1.2"
        ];

        $channel = "device.{$find['device_code']}.message";
        ChannelClient::publish($channel, [
            'data' => json_encode($payload)
        ]);

        SvDeviceRpa::where('device_code', $find['device_code'])->where('app_type', '<>', $appType)->update(['status' => 0, 'update_time' => time()]);
        SvDeviceRpa::where('device_code', $find['device_code'])->where('app_type', $appType)->update([
            'status' => 1,
            'update_time' => time(),
            'start_time' => date('Y-m-d H:i:s', time()),
        ]);
    }

    public static function sphPause(string $task_id): void
    {
        $find = SvCrawlingTask::where('id', $task_id)->findOrEmpty();
        if ($find->isEmpty()) {
            throw new \Exception('任务不存在');
        }
        if (empty($find->device_codes)) {
            throw new \Exception('设备不存在');
        }
        $deviceIds = json_decode($find->device_codes, true);
        ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        foreach ($deviceIds as $_deviceId) {
            $data = array(
                'type' => 22,
                'appType' => 1,
                'content' => json_encode(array(
                    'task_id' => $task_id,
                    'deviceId' => $_deviceId,
                    'msg' => '任务暂停'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $_deviceId,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );

            $channel = "device.{$_deviceId}.message";
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);
            SvCrawlingTaskDeviceBind::where('task_id', $task_id)->where('device_code', $_deviceId)->update(['status' => 2, 'update_time' => time()]);
        }
    }

    public static function sphRecovery(string $task_id): void
    {
        $find = SvCrawlingTask::where('id', $task_id)->findOrEmpty();
        if ($find->isEmpty()) {
            throw new \Exception('任务不存在');
        }
        if (empty($find->device_codes)) {
            throw new \Exception('设备不存在');
        }
        $deviceIds = json_decode($find->device_codes, true);
        ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        foreach ($deviceIds as $_deviceId) {
            $data = array(
                'type' => 23,
                'appType' => 1,
                'content' => json_encode(array(
                    'task_id' => $task_id,
                    'deviceId' => $_deviceId,
                    'msg' => '任务恢复'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $_deviceId,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );

            $channel = "device.{$_deviceId}.message";
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);
            SvCrawlingTaskDeviceBind::where('task_id', $task_id)->where('device_code', $_deviceId)->update(['status' => 1, 'update_time' => time()]);
        }
    }

    public static function sphDelete(string $task_id): void
    {
        $find = SvCrawlingTask::where('id', $task_id)->findOrEmpty();
        if ($find->isEmpty()) {
            throw new \Exception('任务不存在');
        }
        if (empty($find->device_codes)) {
            throw new \Exception('设备不存在');
        }
        $deviceIds = json_decode($find->device_codes, true);
        ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        foreach ($deviceIds as $_deviceId) {
            $isRun = SvDeviceTask::where('sub_task_id', $task_id)->where('device_code', $_deviceId)->where('task_type', 4)->where('status', 1)->findOrEmpty();
            if (!$isRun->isEmpty()) {
                $data = array(
                    'type' => 22,
                    'appType' => DeviceEnum::ACCOUNT_TYPE_SPH,
                    'content' => json_encode(array(
                        'task_id' => $task_id,
                        'deviceId' => $_deviceId,
                        'msg' => '任务删除'

                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $_deviceId,
                    'appVersion' => '2.1.1',
                    'messageId' => 0,
                );

                $channel = "device.{$_deviceId}.message";
                ChannelClient::publish($channel, [
                    'data' => json_encode($data)
                ]);
            }
            SvCrawlingTaskDeviceBind::where('task_id', $task_id)->where('device_code', $_deviceId)->select()->delete();
            SvDeviceTask::where('sub_task_id', $task_id)->where('device_code', $_deviceId)->where('task_type', 4)->select()->delete();
        }

        $find->delete();
    }

    private static function getPlatform(int $type): string
    {

        return DeviceEnum::getAccountTypeDesc($type);
    }

    private static function createGreetingContents(array $task, int $user_id): array
    {
        try {
            $returnContent = '';
            //获取提示词
            $keyword = $task['add_friends_prompt'] != '' ? $task['add_friends_prompt'] : (ChatPrompt::where('prompt_name', '私信内容')->value('prompt_text') ?? '');
            $keyword .= "\n- 生成10条不同描述的文案\n- 生成的结果文案首部的数字和点号.\n- 只能生成中文文案\n- 每条文案长度不能超过100个字符";

            $request = [
                'stream' => false,
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $keyword],
                    [
                        'role' => 'user',
                        'content' => $task['greeting_content'],
                    ],
                ],
                'user_id' => $user_id,
                'task_id' => generate_unique_task_id(),
                'chat_type' => AccountLogEnum::TOKENS_DEC_OPENAI_CHAT,
                'now'       => time(),
            ];
            //print_r($request);die;

            $response = \app\common\service\ToolsService::Sv()->openaiChat($request);
            //print_r($response);die;

            if (isset($response['code']) && $response['code'] == 10000) {
                // 处理响应
                $returnContent = self::handleResponse($response, $request['model'], $request['task_id'], $user_id);
            } else {
                throw new \Exception(json_encode($response, JSON_UNESCAPED_UNICODE));
            }
            $returnContent = self::formatReturnContent($returnContent);
            return $returnContent;
        } catch (\Throwable $e) {
            self::setLog($e->__toString());
            throw new \Exception('AI生成私信失败:' . $e->getMessage());
        }
    }
    private static function formatReturnContent(string $returnContent): array
    {
        $returnContent = preg_replace('/\d+\./', '', $returnContent);
        $returnContent = explode("\n", $returnContent);
        return array_values(array_filter($returnContent));
    }


    private static function handleResponse(array $response, string $model, string $task_id, int $user_id)
    {
        try {
            $scene = 'openai_chat';
            //检查扣费
            $unit = TokenLogService::checkToken($user_id, $scene);
            // 获取回复内容
            $reply = $response['data']['message'] ?? '';
            //计费
            $tokens = $response['data']['usage']['total_tokens'] ?? 0;
            if (!$reply || $tokens == 0) {
                throw new \Exception('获取内容失败');
            }

            $response = [
                'reply' => $reply,
                'usage_tokens' => $response['data']['usage'] ?? [],
            ];
            //计算消耗tokens
            $points = $unit > 0 ? round($tokens / $unit, 2) : 0;
            //token扣除
            User::userTokensChange($user_id, $points);

            $extra = ['总消耗tokens数' => $tokens, '算力单价' => $unit, '实际消耗算力' => $points, '场景' => '视频号获客私信打招呼内容'];
            $desc = AccountLogEnum::TOKENS_DEC_OPENAI_CHAT;
            //扣费记录
            AccountLogLogic::recordUserTokensLog(true, $user_id, $desc, $points, $task_id, $extra);


            return $reply;
        } catch (\Exception $e) {
            self::setLog($e->__toString());
            throw new \Exception('AI生成私信扣费失败:' . $e->getMessage());
        }
    }

    private static function redis(): Redis
    {

        return new Redis([
            'host'        => env('redis.HOST', '127.0.0.1'),
            'port'        => env('redis.PORT', 6379),
            'password'    => env('redis.PASSWORD', '123456'),
            'select'      => env('redis.WS_SELECT', 9),
            'timeout'     => 0,
            'persistent'  => true,
        ]);
    }


    private static function setLog($content, $type = 'sph')
    {
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        Log::write($content, $type);
    }
}
