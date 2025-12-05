<?php

namespace app\common\workerman\rpa\handlers\sph;

use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvAddWechatStrategy;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatLog;
use Workerman\Timer;
use think\facade\Db;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\user\User;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;
use app\common\model\ChatPrompt;


class TaskRecordSaveHandler extends BaseMessageHandler
{
    protected $provinces = [
        '北京' => 'Beijing',
        '上海' => 'Shanghai',
        '天津' => 'Tianjin',
        '重庆' => 'Chongqing',
        '河北' => 'Hebei',
        '山西' => 'Shanxi',
        '辽宁' => 'Liaoning',
        '吉林' => 'Jilin',
        '黑龙江' => 'Heilongjiang',
        '江苏' => 'Jiangsu',
        '浙江' => 'Zhejiang',
        '安徽' => 'Anhui',
        '福建' => 'Fujian',
        '江西' => 'Jiangxi',
        '山东' => 'Shandong',
        '河南' => 'Henan',
        '湖北' => 'Hubei',
        '湖南' => 'Hunan',
        '广东' => 'Guangdong',
        '海南' => 'Hainan',
        '四川' => 'Sichuan',
        '贵州' => 'Guizhou',
        '云南' => 'Yunnan',
        '陕西' => 'Shaanxi',
        '甘肃' => 'Gansu',
        '青海' => 'Qinghai',
        '台湾' => 'Taiwan',
        '内蒙古' => 'Neimenggu',
        '广西' => 'Guangxi',
        '西藏' => 'Xizang',
        '宁夏' => 'Ningxia',
        '新疆' => 'Xinjiang',
        '香港' => 'Xianggang',
        '澳门' => 'Aomen'
    ];
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;

            $this->payload['reply'] = $this->addTaskRecord($content);
            //$this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'task_record');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::SPH_STATUS_ERROR_CODE;
            $this->payload['type'] = 21;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::SPH_STATUS_ERROR_CODE,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        }
    }

    private function addTaskRecord(array $content)
    {
        try {
            if (in_array($content['username'], ['WebSocket地址', 'WebSocket 地址', 'WebSocket地址:', 'WebSocket 地址:', '会话记录'])) {
                $this->setLog('用户名包含WebSocket地址,忽略', 'task_record');
                $this->payload['type'] = 27;
                return [
                    'msg' => '用户名包含WebSocket地址,忽略',
                    'ocr_type' => 3,
                ];
            }
            // $crawlContent = explode("关注\n", $content['crawl_content']);
            // $content['crawl_content'] = $crawlContent[0] ?? $content['crawl_content'];

            if (empty(trim($content['crawl_content']))) {
                $this->setLog('获客内容为空,忽略', 'task_record');
                $this->payload['type'] = 27;
                return [
                    'msg' => '获客内容为空,忽略',
                    'ocr_type' => 2,
                ];
            }

            $task = SvCrawlingTask::where('id', $content['task_id'])->findOrEmpty();
            if ($task->isEmpty()) {
                $this->setLog('任务不存在', 'task_record');
                return;
            }
            $task->status = 1;
            $task->update_time = time();
            $task->save();

            SvCrawlingTaskDeviceBind::where('task_id', $content['task_id'])
                ->where('device_code', $this->payload['deviceId'])
                ->update([
                    'status' => 1,
                    'exec_keyword' => $content['exec_keyword'],
                    'update_time' => time(),
                ]);

            //扣除算力
            $userId = $task['user_id'] ?? 0;
            $tokenScene = "sph_add_wechat";
            $tokenCode = AccountLogEnum::TOKENS_DEC_SPH_ADD_WECHAT;
            $unit = TokenLogService::checkToken($userId, $tokenScene);
            $points = $unit;
            $extra = ['算力单价' => $unit . '算力/条', '实际消耗算力' => $points];
            $sub_task_id = generate_unique_task_id();

            if ($task->add_type == 1) {
                list($status, $reg_content) = $this->autoAddWechatOperation($content, $this->payload['deviceId'], $userId, $task);
            } else {
                $reg_content = $this->getRegContent($content['crawl_content']);
            }

            //$reg_content = $this->getRegContent($content['crawl_content']);
            $hash =  empty($reg_content) ? '' : sha1(implode(',', $reg_content));
            $isExist = false;
            if ($hash !== '') {
                $find = SvCrawlingRecord::where('user_id', $userId)->where('task_id', $content['task_id'])->where('hash', $hash)->limit(1)->findOrEmpty();
                if (!$find->isEmpty()) {
                    $isExist = true;
                }
            }

            $result =  [
                'user_id' => $task['user_id'] ?? 0,
                'task_id' => $content['task_id'],
                'image' => $this->sphBase64ToImage($content, $sub_task_id),
                'device_code' => $this->payload['deviceId'],
                'username' => $content['username'],
                'exec_keyword' => $content['exec_keyword'],
                'crawl_content' => $content['crawl_content'],
                //'reg_content' => implode(',', $response),
                'reg_content' =>  implode(',', $reg_content),
                'clue_type' => empty($reg_content) ? 0 : (preg_match('/1[3-9]\d{9}/', implode(',', $reg_content)) ? 2 : 1),
                'address' => $content['address'] ?? '',
                'sub_task_id' => $sub_task_id,
                //'tokens' => $task->ocr_type == 1 ? ($isExist ? 0 : $points) : 0,
                'tokens' => $isExist ? 0 : $points,
                'hash' => $hash,
                'exec_time' => date('Y-m-d H:i:s'),
                'create_time' => time()
            ];


            SvCrawlingRecord::create($result);
            $task->number_of_implemented_keywords = SvCrawlingRecord::where('task_id', $task['id'])->group('exec_keyword')->count();
            $task->update_time = time();
            $task->save();

            if (!$isExist) {
                User::userTokensChange($userId, $points);
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $sub_task_id, $extra);
            }

            $result['msg'] = '获客内容上报成功';
            $result['ocr_type'] = 1;
            $this->payload['type'] = 27;

            unset($this->payload, $content);
            return $result;
        } catch (\Throwable $e) {
            $this->setLog('异常信息' . $e, 'task_record');
            $this->payload['reply'] = "异常信息:" . $e->getMessage();
            $this->payload['code'] =  WorkerEnum::SPH_ADD_WECHAT_ERROR;
            $this->payload['type'] = 21;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::SPH_ADD_WECHAT_ERROR,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        }
    }

    public function sphBase64ToImage(array $item, string $code)
    {
        if (!isset($item['image'])) {
            return '';
        }
        // 分离Base64头和数据
        $data = explode(',', $item['image']);
        // 解码Base64数据
        $decoded = base64_decode($data[1] ?? $data[0]);
        $output = 'uploads/images/sph/sph_' . $code . '.png';
        $root_path = public_path();
        // 创建目录（如果不存在）
        if (!is_dir(dirname($root_path . $output))) {
            mkdir(dirname($root_path . $output), 0777, true);
        }
        // 保存文件
        if (file_put_contents($root_path . $output, $decoded)) {
            return  $output;
        }
        return '';
    }


    private function getRegContent(string $crawlContent)
    {
        try {
            $strings = explode("\n", $crawlContent);
            if (count($strings) > 2) {
                $strings = array_values(array_slice($strings, 2));
            } else {
                $strings = [];
            }
            if (empty($strings)) {
                return [];
            }
            $crawlContent = implode("\n", $strings);
            $crawlContent = str_replace(array_values($this->provinces), "", $crawlContent);

            $wechatPattern = '/[a-zA-Z][a-zA-Z0-9_-]{5,19}/';
            $phonePattern = '/1[3-9]\d{9}/';
            $pattern = '/(?:[a-zA-Z][a-zA-Z0-9_-]{5,19}|1[3-9]\d{9})/';
            $blacklist = array();
            $addWechat = array();
            $checkArray = ["加vx", "加VX", "加v", "加V", "加wx", "加WX", "+wx", "+WX", "+vx", "+VX", "vx:", "VX:", "vx：", "VX：", "+v", "+V", "V:", "V：", "v:", "v："];
            // if (!$this->containsAnyWithFilter($crawlContent, $checkArray)) {
            //     //指定字符不存在
            //     return [];
            // }
            $content = str_replace($checkArray, '', $crawlContent);
            $matchs = [];
            preg_match_all($pattern, $content, $matchs, PREG_SET_ORDER);
            if (!empty($matchs)) {
                foreach ($matchs as $match) {
                    $this->setLog($match, 'task_record');
                    if (empty($match)) {
                        continue;
                    }
                    $userWechatNo = $match[0];
                    $this->setLog($userWechatNo, 'task_record');

                    if (in_array(strtolower($userWechatNo), $blacklist)) {
                        $this->setLog('忽略字符串', 'task_record');
                        continue;
                    }
                    $addWechat[] = $userWechatNo;
                }
            }
            return array_values(array_unique($addWechat));
        } catch (\Throwable $e) {
            $this->setLog('异常信息' . $e, 'task_record');
            $this->payload['reply'] = "异常信息:" . $e->getMessage();
            $this->payload['code'] =  WorkerEnum::SPH_ADD_WECHAT_ERROR;
            $this->payload['type'] = 21;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::SPH_ADD_WECHAT_ERROR,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];

            $this->sendError($this->connection,  $this->payload);
        }
    }


    private function autoAddWechatOperation(array $payload, string $device_code, int $userid, SvCrawlingTask $task)
    {

        try {
            $wechat_ids = explode(',', $task->wechat_id);
            $wechat_reg_type = (int)$task->wechat_reg_type;
            if (empty($wechat_ids)) {
                $this->setLog('任务ID:' . $task->id . '没有配置微信账号', 'task_record');
                $addWechat = $this->getRegContent($payload['crawl_content']);
                return [true, $addWechat];
            }

            $addWechat = array();
            $replyContent = $payload['crawl_content'];
            $strings = explode("\n", $replyContent);
            if (count($strings) > 2) {
                $strings = array_values(array_slice($strings, 2));
            } else {
                $strings = [];
            }
            if (empty($strings)) {
                return [false, []];
            }
            $replyContent = implode("\n", $strings);
            $replyContent = str_replace(array_values($this->provinces), "", $replyContent);

            $wechatPattern = '/[a-zA-Z][a-zA-Z0-9_-]{5,19}/';
            $phonePattern = '/1[3-9]\d{9}/';
            $pattern = '/(?:[a-zA-Z][a-zA-Z0-9_-]{5,19}|1[3-9]\d{9})/';

            $blacklist = array();

            $isInWechat = false;
            $checkArray = ["加vx", "加VX", "加v", "加V", "加wx", "加WX", "+wx", "+WX", "+vx", "+VX", "vx:", "VX:", "vx：", "VX：", "+v", "+V", "V:", "V：", "v:", "v："];
            // if (!$this->containsAnyWithFilter($replyContent, $checkArray)) {
            //     //指定字符不存在
            //     return [false, []];
            // }

            $content = str_replace($checkArray, '', $replyContent);
            $matchs = [];
            if ($wechat_reg_type === 0) {
                preg_match_all($pattern, $content, $matchs, PREG_SET_ORDER);
            } else {
                if ($wechat_reg_type === 1) {
                    preg_match_all($wechatPattern, $content, $matchs);
                }
                if ($wechat_reg_type === 2) {
                    preg_match_all($phonePattern, $content, $matchs);
                }
            }

            if (!empty($matchs)) {
                $isInWechat = true;
                foreach ($matchs as $match) {
                    $this->setLog($match, 'task_record');
                    if (empty($match)) {
                        continue;
                    }
                    $userWechatNo = $match[0];
                    $this->setLog($userWechatNo, 'task_record');

                    if (in_array(strtolower($userWechatNo), $blacklist)) {
                        $this->setLog('忽略字符串', 'task_record');
                        continue;
                    }

                    $status = 4;

                    //查询当前设备该微信号执行记录
                    $recordCount = SvAddWechatRecord::where('user_id', $userid)
                        ->where('device_code', $device_code)
                        ->where('account_type', 1)
                        ->where('reg_wechat', $userWechatNo)
                        //->where('status', 0)
                        ->where('channel', 1)
                        ->count();
                    $this->setLog($recordCount, 'task_record');
                    if ($recordCount >= 5) {
                        $this->setLog($userWechatNo . '该账号已执行5次,忽略', 'task_record');
                        continue;
                    }

                    $exist = SvAddWechatRecord::where('user_id', $userid)
                        ->where('device_code', $device_code)
                        ->where('account_type', 1)
                        ->where('channel', 1)
                        ->where('crawling_task_id', $task->id)
                        ->where('reg_wechat', $userWechatNo)
                        ->findOrEmpty();
                    if (!$exist->isEmpty()) {
                        $this->setLog($userWechatNo . '该账号已存在执行记录,忽略', 'task_record');
                        continue;
                    }

                    $record = [
                        'user_id' => $userid,
                        'device_code' => $device_code,
                        'account' => $payload['account'] ?? ($payload['username'] ?? ''),
                        'account_type'  => 1,
                        'user_account' => $payload['username'],
                        'original_message' => $payload['crawl_content'],
                        'reg_wechat' => $userWechatNo,
                        'action' => 1,
                        'status' => $status,
                        'channel' => 1,
                        'exec_type' => $payload['exec_type'] ?? 2,
                        'task_id' => time() . rand(100, 999),
                        'crawling_task_id' => $task->id,
                        'create_time' => time()
                    ];

                    $wxPattern = '/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/';
                    if (preg_match($wxPattern, $userWechatNo)) {
                        $response = \app\common\service\ToolsService::Sv()->validateStrings([
                            "strings" => [$userWechatNo],
                        ]);
                        $this->setLog($response, 'task_record');
                        if (isset($response['code']) && (int)$response['code'] !== 10000) {
                            $this->setLog($userWechatNo . '该账号不是有效的微信号,忽略', 'task_record');
                            $this->setLog($response, 'task_record');
                            continue;
                        }
                    }
                    $addWechat[] = $userWechatNo;
                    SvAddWechatRecord::create($record);
                }
            }
            if ($addWechat) {
                //微信号检测
                return [true, $addWechat];
            }

            return [false, []];
        } catch (\Throwable $e) {
            $this->setLog('异常信息' . $e, 'task_record');
            $this->payload['reply'] = "异常信息:" . $e->getMessage();
            $this->payload['code'] =  WorkerEnum::SPH_ADD_WECHAT_ERROR;
            $this->payload['type'] = 21;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::SPH_ADD_WECHAT_ERROR,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];

            $this->sendError($this->connection,  $this->payload);
        }

        return [false, []];
    }
}
