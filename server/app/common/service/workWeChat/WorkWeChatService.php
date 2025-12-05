<?php

namespace app\common\service\workWeChat;

use app\common\model\workWeChat\WorkWeChat;
use app\common\service\FileService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use think\Exception;

class WorkWeChatService
{
    private static string $url = "";
    private static string $qw_url = "";

    public function __construct(string $port, string $ip)
    {
        if (empty($port)) {
            throw new \Exception("端口不存在");
        }
        if (empty($ip)) {
            throw new \Exception("ip不存在");
        }
        self::$url = $ip . ":" . $port . "/api";
        self::checkUserLogin($port);

    }

    /**
     * 检测是否登录
     * @return true
     * @throws GuzzleException
     * @author L
     * @data 2024/8/19 18:25
     */
    public static function checkUserLogin(string $port): bool
    {
        try {
            $loginStatus = (new Client())->post(self::$url, [
                'headers' => [
                    "Content-Type" => "application/json",
                ],
                'json'    => [
                    'type' => 1000
                ]
            ]);
        } catch (GuzzleException $exception) {
            throw new Exception("远程服务异常");
        }
        $loginInfo = json_decode($loginStatus->getBody()->getContents(), true);
        if (empty($loginInfo) || $loginInfo['data']['status'] == 0) {
            throw new \Exception("登录状态异常");
        }
        $updateData = [
            'login_status' => $loginInfo['data']['status']
        ];
        if ($loginInfo['data']['status'] == 1) {
            $updateData['login_out_time'] = null;
        }
        WorkWeChat::where('port', $port)->update($updateData);
        return true;
    }


    /**
     * 发送请求
     * @param array $sendData
     * @return array
     * @author L
     * @data 2024/8/20 11:00
     */
    public static function send(array $sendData)
    {
        try {
            $info = (new Client())->post(self::$url, [
                'headers' => [
                    "Content-Type" => "application/json",
                ],
                'json'    => $sendData
            ]);
            $info = json_decode($info->getBody()->getContents(), true);
            if (empty($info) || $info['errno'] !== 0) {
                throw new \Exception($info['errmsg']);
            }

            return [
                'code' => 200,
                "data" => $info['data'],
                'msg'  => ""
            ];
        } catch (\Exception|GuzzleException $exception) {
            return [
                'code' => 500,
                'msg'  => $exception->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * 获取用户信息
     * @return array
     * @author L
     * @data 2024/8/19 14:18
     */
    public static function getLoginUserInfo(): array
    {
        try {
            return self::send(['type' => 1002]);
        } catch (\Exception $exception) {
            return [
                'code' => 200,
                'msg'  => $exception->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * 搜陌生人消息
     * @return array
     * @author L
     * @data 2024/8/19 17:27
     */
    public static function searchUserInfo(string $key)
    {
        try {
            return self::send([
                'type' => 4008,
                'key'  => $key,
            ]);
        } catch (\Exception $exception) {
            return [
                'code' => 200,
                'msg'  => $exception->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * 搜陌生人消息
     * @return array
     * @author L
     * @data 2024/8/19 17:27
     */
    public static function apply(string $userId, string $openid_orTicket, string $remarks, int $addType = 2)
    {
        try {
            return self::send([
                'type'             => 4009,
                'add_type'         => $addType,
                'user_id'          => $userId,
                'openid_or_ticket' => $openid_orTicket,
                'msg'              => $remarks,
            ]);
        } catch (\Exception $exception) {
            return [
                'code' => 500,
                'msg'  => $exception->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * 同意好友的强求
     * @param string $userId
     * @return array
     * @author L
     * @data 2024/8/20 11:02
     */
    public static function agreeApply(string $userId): array
    {
        try {
            return self::send([
                'type'    => 4002,
                'user_id' => $userId,
            ]);
        } catch (\Exception $exception) {
            return [
                'code' => 500,
                'msg'  => $exception->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * 同意好友的强求
     * @param string $userId
     * @param string $msg
     * @return array
     * @author L
     * @data 2024/8/20 11:02
     */
    public static function sendMsg(string $userId, string $msg, int $type): array
    {
        try {
            $path = "";
            //远程下载文件
            if ($type == 2) {
                $download     = (new Client())->post(self::$qw_url, [
                    'headers' => [
                        "Content-Type" => "application/json",
                    ],
                    'json'    => [
                        'file_path' => FileService::getFileUrl($msg)
                    ]
                ])->getBody()->getContents();
                $downloadInfo = json_decode($download, true);

                $path = $downloadInfo['path'];
            }
            $data = match ($type) {
                //文字
                1 => ['type' => 3000, 'user_id' => $userId, 'msg' => $msg],
                //文件
                2 => ['type' => 3001, 'user_id' => $userId, 'path' => $path],
            };
            return self::send($data);
        } catch (\Exception $exception) {
            return [
                'code' => 500,
                'msg'  => $exception->getMessage(),
                'data' => []
            ];
        }
    }
}