<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\workWeChat\PhoneList;
use app\common\model\workWeChat\WorkConfig;
use app\common\model\workWeChat\WorkWeChat;
use app\common\service\ConfigService;
use app\common\service\workWeChat\WorkWeChatService;
use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use think\facade\Cache;
use think\facade\Log;


/**
 * logic
 */
class WorkWeChatLogic extends BaseLogic
{
    /**
     * 添加
     * @param array $postData
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-08-19 11:38:47
     */
    public static function add(array $postData, int $userId): bool
    {
        try {
            self::$returnData = WorkWeChat::create([
                'ip'            => $postData['ip'],
                'port'          => $postData['port'],
                'login_user_id' => $userId,
            ])->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 更新
     * @param array $postData
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024/8/19 14:36
     */
    public static function updateUser(array $postData, int $userId): bool
    {
        try {
            $info = WorkWeChat::where('login_user_id', $userId)->findOrEmpty($postData['id']);
            if ($info->isEmpty()) {
                throw new \Exception("查询失败");
            }
            $userData         = (new WorkWeChatService($postData['port'], $postData['ip']))::getLoginUserInfo();
            self::$returnData = WorkWeChat::update([
                'id'         => $postData['id'],
                'ip'         => $postData['ip'],
                'port'       => $postData['port'],
                'nick_name'  => $userData['data']['nick_name'],
                'real_name'  => $userData['data']['real_name'],
                'alias'      => $userData['data']['alias'],
                'avatar_url' => $userData['data']['avatar_url'],
                'sex'        => $userData['data']['sex'],
            ])->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError("服务异常");
            return false;
        }
    }


    /**
     * 删除
     * @param array $getData
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-08-19 11:38:47
     */
    public static function delete(array $getData, int $userId): bool
    {
        try {
            WorkWeChat::destroy(['login_user_id' => $userId, 'id' => $getData['id']]);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 详情
     * @param array $getData
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-08-19 11:38:47
     */
    public static function detail(array $getData, int $userId): bool
    {
        try {
            self::$returnData = WorkWeChat::where('login_user_id', $userId)->findOrEmpty($getData['id'])->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 修改状态
     * @param array $params
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024/7/5 10:25
     */
    public static function changeStatus(array $params, int $userId): bool
    {
        try {
            $info = WorkWeChat::where('login_user_id', $userId)->findOrEmpty($params['id']);
            if ($info->isEmpty()) {
                throw new \Exception("信息异常");
            }
            $info->status = 1 - $info->status;
            $info->save();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 导入
     * @param array $params
     * @return bool
     * @author L
     * @data 2024/8/19 14:59
     */
    public static function importData(array $params, int $userId): bool
    {
        $workWeChatIds = $params['work_we_chat_id'];
        try {
            if (($handle = fopen(public_path() . $params['file_path'], 'r')) === false) {
                throw new \Exception("文件不存在");
            }
            $list = [];
            $i    = 0;
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                // 设置第二个参数为0表示不限制每行的字节数
                // $data 是一个数组，包含当前行的数据

                if ($i == 0) {
                    $i++;
                    continue;
                }
                if (empty($data[0])) {
                    continue;
                }
                $encoding = mb_detect_encoding($data[1], mb_list_encodings(), true);
                if ($encoding !== "UTF-8") {
                    $data = array_map(function ($item) {
                        return mb_convert_encoding($item, 'UTF-8', 'GBK');
                    }, $data);
                }
                $list[$i]['file_id']  = $params['file_id'];
                $list[$i]['phone']    = $data[0];
                $list[$i]['name']     = $data[1];
                $list[$i]['remarks']  = $data[2] ?? "";
                $list[$i]['login_id'] = $userId;
                $i++;
            }
            fclose($handle);

            $uniqueArray = array_reduce($list, function ($result, $item) {
                $ids = array_column($result, 'phone');
                if (!in_array($item['phone'], $ids)) {
                    $result[] = $item;
                }
                return $result;
            }, []);
            if (empty($uniqueArray)) {
                throw new \Exception("数据异常");
            }
            $chunks = array_chunk($uniqueArray, ceil(count($uniqueArray) / count($workWeChatIds)));

            foreach ($chunks as $k => &$v) {
                $v = array_map(function ($value) use ($k, $workWeChatIds) {
                    $value['work_we_chat_id'] = $workWeChatIds[$k];
                    return $value;
                }, $v);
            }
            $mergedArray = array_reduce($chunks, function ($carry, $item) {
                return array_merge($carry, $item);
            }, []);

            (new PhoneList)->saveAll($mergedArray);

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 申请加好友
     * @return string
     * @author L
     * @data 2024/8/19 16:46
     */
    public static function apply(): string
    {
        $list = WorkWeChat::where('status', 1)->where('login_status', 1)->select();
        try {
            if ($list->isEmpty()) {
                throw new \Exception("当前暂无在线微信");
            }
            $updateList = [];
            foreach ($list as $k => $v) {
                $config = WorkConfig::where('user_id', $v->login_user_id)->findOrEmpty();
                if ($config->isEmpty()) {
                    $config['space_time'] = 1;
                    $config['count']      = 10;
                }
                $service = new WorkWeChatService($v->port, $v->ip);
                if (time() <= ($v->apply_time + $config['space_time'] * 60)) {
                    continue;
                }
                $userCount = Cache::get("qw:" . $v->id, 0);
                if ($userCount >= $config['count']) {
                    continue;
                }
                $phoneInfo = PhoneList::where('work_we_chat_id', $v->id)->where('status', 0)->findOrEmpty();
                if ($phoneInfo->isEmpty()) {
                    continue;
                }
                $updateList[$k]['id'] = $phoneInfo->id;
                $searchUserData       = $service::searchUserInfo($phoneInfo->phone);
                if ($searchUserData['code'] == 500) {
                    $updateList[$k]['msg']    = $searchUserData['msg'];
                    $updateList[$k]['status'] = 3;
                    Log::write($v->real_name . "添加" . $phoneInfo->phone . "失败" . $searchUserData['msg'], "phone_list");
                    continue;
                }
                foreach ($searchUserData['data']['list'] as $user) {
                    if ($user['item_type'] == 2) {
                        $wx_info = $user;
                    }
                }

                if (empty($wx_info)) {
                    $updateList[$k]['msg']    = "该用户未找到";
                    $updateList[$k]['status'] = 3;
                    Log::write($v->real_name . "添加" . $phoneInfo->phone . "失败" . $searchUserData['msg'], "phone_list");

                    continue;
                }
                $applyUser = $service::apply(
                    $wx_info['user_id'],
                    $wx_info['openid_or_ticket'],
                    $phoneInfo->remarks ?? "你好",
                );
                if ($applyUser['code'] == 500) {
                    $updateList[$k]['msg']    = $applyUser['msg'];
                    $updateList[$k]['status'] = 3;
                    Log::write($v->real_name . "添加" . $phoneInfo->phone . "失败" . $searchUserData['msg'], "phone_list");
                    continue;
                }
                $updateList[$k]['add_time'] = time();
                $updateList[$k]['status']   = 1;
                $updateList[$k]['user_id']  = $wx_info['user_id'];

                $v->apply_time = time();
                $v->save();

                Cache::set("qw:" . $v->id, $userCount + 1, new DateTime(date("Y-m-d ") . "23:59:59"));

                Log::write($v->real_name . "发起添加 " . $phoneInfo->phone . " 成功", "phone_list");
            }
            if (!empty($updateList)) {
                (new PhoneList())->saveAll($updateList);
            }
            return "success";
        } catch (\Exception|GuzzleException $exception) {
            Log::write("error" . $exception->getMessage(), "phone_list");
            return "error";
        }
    }

    /**
     * 接收消息处理
     * @param string $data
     * @return array
     * @throws \Exception
     * @author L
     * @data 2024/8/20 11:15
     */
    public static function getData(array $data): array
    {
        Log::write("get -- " . json_encode($data, JSON_UNESCAPED_UNICODE), "qw");
//        $data       = json_decode($data, true);
        $returnData = [
            'code' => 200,
            "msg"  => "success",
        ];

        $isUpdateStatus = false;

        try {
            $userInfo = WorkWeChat::where('port', $data['port'])
                                  ->json(['msg'], true)
                                  ->where('login_status', 1)
                                  ->findOrEmpty();
            if ($userInfo->isEmpty()) {
                Log::write("用户状态异常");
                throw new \Exception("用户查询失败");
            }
            if ($data['type'] == 900) {
                $userInfo->login_out_time = time();
                $userInfo->login_status   = 0;
                $userInfo->save();
                return $returnData;
            }
            $service   = new WorkWeChatService($userInfo->port, $userInfo->ip);
            $phoneInfo = PhoneList::where('user_id', $data['user_id'])->findOrEmpty();
            if ($phoneInfo->isEmpty()) {
                Log::write("用户不存在", "qw");
            }
            switch ($data['type']) {
                // 对方添加自己好友申请
                case 200:
                    $agree = $service->agreeApply($data['user_id']);
                    Log::write("get -- " . json_encode($agree, JSON_UNESCAPED_UNICODE), "qw");
                    self::msg($agree);
                    sleep(2);
                    foreach ($userInfo->msg as $v) {
                        $send = $service->sendMsg($data['user_id'], $v['msg'], $v['type']);
                        sleep(1);
                        Log::write("get -- " . json_encode($send, JSON_UNESCAPED_UNICODE), "qw");
                    }
                    self::msg($send);
                    $isUpdateStatus = true;
                    break;
                //对方同意添加好友的请求
                case 201:
                    $isUpdateStatus = true;
//                    $send           = $service->sendMsg($data['user_id'], $msg);
//                    self::msg($send);x
                case 100:
                    if ($data['content'] == "我通过了你的联系人验证请求，现在我们可以开始聊天了") {
                        sleep(2);
                        foreach ($userInfo->msg as $v) {
                            $send = $service->sendMsg($data['user_id'], $v['msg'], $v['type']);
                            sleep(1);
                            Log::write("get -- " . json_encode($send, JSON_UNESCAPED_UNICODE), "qw");
                        }
                    }
                    $isUpdateStatus = true;
            }
            if ($isUpdateStatus) {
                $phoneInfo->status       = 2;
                $phoneInfo->success_time = time();
                $phoneInfo->msg = "";
                $phoneInfo->save();
            }
        } catch (\Exception|GuzzleException $exception) {
            Log::write("error" . $exception->getMessage() . $exception->getLine(), "qw");
            $returnData['msg'] = $exception->getMessage();
        }
        //修改客户状态
        return $returnData;
    }

    /**
     * 处理异常信息
     * @param array $data
     * @return true
     * @throws \Exception
     * @author L
     * @data 2024/8/20 11:13
     */
    protected static function msg(array $data)
    {
        if ($data['code'] !== 200) {
            throw new \Exception($data['msg']);
        }
        return true;
    }

    /**
     * 修改基础信息
     * @param array $params
     * @return bool
     * @author L
     * @data 2024/8/21 15:17
     */
    public static function edit(array $params): bool
    {
        try {
            $info = WorkWeChat::where('id', $params['id'])->findOrEmpty();
            if ($info->isEmpty()) {
                throw new \Exception("用户查询异常");
            }
            $params['msg'] = $params['msg'] ? json_encode($params['msg'], JSON_UNESCAPED_UNICODE) : "";
            WorkWeChat::update($params);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 检测登录状态
     * @param int $userId
     * @return true
     * @throws GuzzleException
     * @author L
     * @data 2024/8/22 14:50
     */
    public static function checkUserLogin(array $getData, int $userId): bool
    {
        $userInfo = WorkWeChat::where('login_user_id', $userId)->findOrEmpty($getData['id']);
        try {
            if ($userInfo->isEmpty()) {
                throw new \Exception("用户查询异常");
            }
            $service = new WorkWeChatService($userInfo->port, $userInfo->ip);
            $service->checkUserLogin($userInfo->port);
            return true;
        }catch (\Exception $exception) {
            $userInfo->login_status = 0;
            $userInfo->login_out_time = time();
            $userInfo->save();
            self::setError("服务异常");
            return false;
        }
    }
}
