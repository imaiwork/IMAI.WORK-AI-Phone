<?php

namespace app\common\workerman\rpa\handlers\sph;

use app\common\model\sv\SvDevice;
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
use app\common\service\sv\SvSphClueDedupService;


class TaskRecordSaveHandler extends BaseMessageHandler
{
    protected array $provinces = [
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

    protected array $checkArray = [
        "加vx",
        "加VX",
        "加v",
        "加V",
        "加wx",
        "加WX",
        "+wx",
        "+WX",
        "+vx",
        "+VX",
        "vx:",
        "VX:",
        "vx：",
        "VX：",
        "vx",
        "VX",
        "Vx",
        "+v",
        "+V",
        "V:",
        "V：",
        "v:",
        "v：",
        "+",
        ", ",
        " ",
        "-",
        "*",
        "❤"
    ];

    protected $wechatPattern = '/[a-zA-Z][a-zA-Z0-9_-]{5,19}/';
    protected $phonePattern = '/1[3-9]\d{9}/';
    protected $qqPattern = '/\b[1-9]\d{4,10}\b/';
    protected $pattern = '/(?:[a-zA-Z][a-zA-Z0-9_-]{5,19}|1[3-9]\d{9}|\b[1-9]\d{4,10}\b)/';

    protected $wxPhonePattern = '/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/';
    protected $qqPhonePattern = '/\b(?!1[3-9]\d{9}\b)[1-9]\d{4,10}\b/'; //排除手机号

    protected $blacklist = [
        'wechat'
    ];

    protected array $serviceNumberBlacklist = [
        '10000', // 电信
        '10086', // 移动
        '10010', // 联通
        '10011', // 联通/电信
        '10001', // 电信
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
        } finally {
            unset($content);
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
            $hash = empty($reg_content) ? '' : sha1(implode(',', $reg_content));
            $crawlLockValue = null;
            // 跨任务/跨设备：同一用户同一联系方式指纹只入库一次
            if ($hash !== '') {
                $crawlLockValue = SvSphClueDedupService::claimCrawlingHash($userId, $hash);
                if ($crawlLockValue === null) {
                    $this->setLog('获客内容已存在(user_id+hash),跳过入库: ' . $hash, 'task_record');
                    $this->payload['type'] = 27;
                    return [
                        'msg' => '获客内容已存在,跳过',
                        'ocr_type' => 1,
                        'hash' => $hash,
                        'tokens' => 0,
                    ];
                }
            }

            try {
                $result = [
                    'user_id' => $task['user_id'] ?? 0,
                    'task_id' => $content['task_id'],
                    'image' => $this->toolUtil->saveBase64ToImage($content['image'] ?? '', $sub_task_id, 'sph'),
                    'device_code' => $this->payload['deviceId'],
                    'username' => $content['username'],
                    'exec_keyword' => $content['exec_keyword'],
                    'crawl_content' => $content['crawl_content'],
                    //'reg_content' => implode(',', $response),
                    'reg_content' => implode(',', $reg_content),
                    'clue_type' => empty($reg_content) ? 0 : (preg_match('/1[3-9]\d{9}/', implode(',', $reg_content)) ? 2 : 1),
                    'address' => $content['address'] ?? '',
                    'sub_task_id' => $sub_task_id,
                    'tokens' => $points,
                    'hash' => $hash,
                    'exec_time' => date('Y-m-d H:i:s'),
                    'create_time' => time()
                ];

                SvCrawlingRecord::create($result);
                $task->number_of_implemented_keywords = SvCrawlingRecord::where('task_id', $task['id'])->group('exec_keyword')->count();
                $task->update_time = time();
                $task->save();

                User::userTokensChange($userId, $points);
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $sub_task_id, $extra);

                $result['msg'] = '获客内容上报成功';
                $result['ocr_type'] = 1;
                $this->payload['type'] = 27;

                unset($this->payload, $content);
                return $result;
            } finally {
                if ($hash !== '' && $crawlLockValue !== null) {
                    SvSphClueDedupService::releaseCrawlingHash($userId, $hash, $crawlLockValue);
                }
            }
        } catch (\Throwable $e) {
            if ($e->getCode() == 4059) {
                \app\common\model\sv\SvDeviceTask::where('sub_task_id', $content['task_id'])
                    ->where('source', \app\common\enum\DeviceEnum::TASK_SOURCE_CLUES)
                    ->where('device_code', $this->payload['deviceId'])->update([
                        'status' => 3,
                        'remark' => '执行失败:' . $e->getMessage(),
                        'update_time' => time(),
                    ]);
            }

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

    private function getRegContent(string $crawlContent)
    {
        try {
            return $this->extractContactContents($crawlContent);
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

            $replyContent = str_replace(array_values($this->provinces), "", $replyContent);

            // if (!$this->containsAnyWithFilter($replyContent, $checkArray)) {
            //     //指定字符不存在
            //     return [false, []];
            // }

            $contacts = $this->filterContactContentsByRegType(
                $this->extractContactContents($replyContent),
                $wechat_reg_type
            );

            if (!empty($contacts)) {
                foreach ($contacts as $userWechatNo) {
                    $this->setLog($userWechatNo, 'task_record');

                    if (in_array(strtolower($userWechatNo), $this->blacklist)) {
                        $this->setLog('忽略字符串', 'task_record');
                        continue;
                    }

                    $status = 4;

                    // 跨设备全局去重：user_id + reg_wechat，并加锁防并发双写
                    $addLockValue = SvSphClueDedupService::claimAddWechatContact($userid, $userWechatNo);
                    if ($addLockValue === null) {
                        $this->setLog($userWechatNo . '该账号已执行记录,忽略', 'task_record');
                        continue;
                    }

                    try {
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

                        if (preg_match($this->wxPhonePattern, $userWechatNo) || preg_match($this->qqPhonePattern, $userWechatNo)) {
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
                        $autoType = SvDevice::where('device_code', $device_code)->value('auto_type') ?? 0;
                        if ($autoType == 1) {
                            //扣除算力
                            $requestService = \app\common\service\ToolsService::Automation()->wechatAddFriend($record);
                            if (isset($requestService['code']) && $requestService['code'] == 10000) {
                                $tokenScene = "automation_wechat_add_friend";
                                $tokenCode = AccountLogEnum::TOKENS_DEC_AUTOMATION_WECHAT_ADD_FRIEND;
                                $unit = TokenLogService::checkToken($userid, $tokenScene);
                                $points = $unit;
                                $extra = ['算力单价' => $unit . '算力/条', '实际消耗算力' => $points];
                                if ($points > 0) {
                                    User::userTokensChange($userid, $points);
                                    AccountLogLogic::recordUserTokensLog(true, $userid, $tokenCode, $points, $task->id, $extra);
                                }
                            }
                        }

                        \app\api\logic\ApiLogic::sendNotice([
                            'userId' => $userid,
                            'name' => '新增客资通知',
                            'time' => date('Y-m-d H:i:s', time()),
                            'count' => SvAddWechatRecord::where('user_id', $userid)->where('status', 4)->count(),
                            'phone_number' => User::where('id', $userid)->value('mobile'),
                        ], 405);
                    } finally {
                        SvSphClueDedupService::releaseAddWechatContact($userid, $userWechatNo, $addLockValue);
                    }
                }
            }
            if ($addWechat) {
                //微信号检测
                return [true, $contacts];
            }

            return [false, $contacts];
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

    private function extractContactContents(string $crawlContent): array
    {
        $content = str_replace(array_values($this->provinces), '', $crawlContent);
        $phones = $this->extractPhoneNumbers($content);

        $contacts = array_merge(
            $phones,
            $this->extractWechatNumbers($content),
            $this->extractQQNumbers($content, $phones)
        );

        return $this->uniqueContacts($contacts);
    }

    private function filterContactContentsByRegType(array $contacts, int $wechatRegType): array
    {
        if ($wechatRegType === 1) {
            return array_values(array_filter($contacts, function ($contact) {
                return preg_match($this->wxPhonePattern, $contact);
            }));
        }

        if ($wechatRegType === 2) {
            return array_values(array_filter($contacts, function ($contact) {
                return preg_match($this->phonePattern, $contact);
            }));
        }

        return $contacts;
    }

    /**
     * 从文本中提取所有手机号码
     * @param string $text 输入文本
     * @return array 返回手机号数组（纯数字）
     */
    private function extractPhoneNumbers(string $text): array
    {
        $separator = '[加\s\-\+_=＝—–~～·.,，。:：;；、\/\\\\()\[\]{}<>《》【】（）"\'`^*&#!?？❤️❤♥️♥💕💖💗💓💞💘💝\x{FE0F}]*';
        $digit = '[0-9０-９Il\|Oo]';
        $prefix = '(?:[+＋]?(?:v|V|vx|VX|wx|WX|dh|DH|tel|TEL|phone|PHONE)|手机|电话|联系方式)';
        $pattern = '/(?<![A-Za-z0-9_])(?:' . $prefix . $separator . ')?[1１Il\|](?:' . $separator . $digit . '){10}(?![A-Za-z0-9_])/u';

        preg_match_all($pattern, $text, $matches);

        $phones = [];
        foreach ($matches[0] as $match) {
            $normalized = $this->normalizePhoneCandidate($match);
            if (preg_match('/^1[3-9]\d{9}$/', $normalized)) {
                $phones[] = $normalized;
            }
        }

        $cleanText = str_replace($this->checkArray, '', $text);
        preg_match_all($this->phonePattern, $cleanText, $cleanMatches);
        foreach ($cleanMatches[0] as $match) {
            $phones[] = $match;
        }

        return $this->uniqueContacts($phones);
    }

    private function extractWechatNumbers(string $text): array
    {
        preg_match_all($this->wechatPattern, $text, $matches);

        $wechatNos = [];
        foreach ($matches[0] as $match) {
            if ($this->isBlacklistedContact($match) || $this->containsPhoneNumber($match)) {
                continue;
            }
            $wechatNos[] = $match;
        }

        return $this->uniqueContacts($wechatNos);
    }

    private function extractQQNumbers(string $text, array $phones = []): array
    {
        preg_match_all('/(?<!\d)([1-9]\d{4,10})(?!\d)/u', $text, $matches, PREG_OFFSET_CAPTURE);

        $qqNumbers = [];
        foreach ($matches[1] as $match) {
            [$qq, $offset] = $match;
            if (
                $this->isPhoneNumber($qq)
                || $this->isPartOfPhoneNumber($qq, $phones)
                || $this->isLikelyStatisticNumber($text, $offset, strlen($qq))
            ) {
                continue;
            }
            $qqNumbers[] = $qq;
        }

        return $this->uniqueContacts($qqNumbers);
    }

    private function normalizePhoneCandidate(string $candidate): string
    {
        $candidate = strtr($candidate, [
            '０' => '0',
            '１' => '1',
            '２' => '2',
            '３' => '3',
            '４' => '4',
            '５' => '5',
            '６' => '6',
            '７' => '7',
            '８' => '8',
            '９' => '9',
            'I' => '1',
            'l' => '1',
            '|' => '1',
            'O' => '0',
            'o' => '0',
        ]);

        return preg_replace('/\D/', '', $candidate) ?: '';
    }

    private function isPhoneNumber(string $content): bool
    {
        return preg_match($this->phonePattern, $content) === 1;
    }

    private function containsPhoneNumber(string $content): bool
    {
        return preg_match($this->phonePattern, $content) === 1;
    }

    private function isPartOfPhoneNumber(string $content, array $phones): bool
    {
        foreach ($phones as $phone) {
            if (strpos($phone, $content) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isBlacklistedContact(string $content): bool
    {
        return in_array(strtolower($content), $this->blacklist);
    }

    private function isServiceNumber(string $contact): bool
    {
        return in_array($contact, $this->serviceNumberBlacklist, true);
    }

    private function isLikelyStatisticNumber(string $text, int $offset, int $length): bool
    {
        $before = mb_substr(substr($text, 0, $offset), -8);
        $after = mb_substr(substr($text, $offset + $length), 0, 8);

        if (preg_match('/[第约近超逾满成就培育服务客]?$/u', $before) && preg_match('/^\s*(?:[+＋]|(家|个|多|条|年|岁|后|点|时|分|原创|内容|粉丝|品牌|公司|上市|直播))/u', $after)) {
            return true;
        }

        return preg_match('/(原创内容|上市公司|知名品牌|昨天直播|每天|条原创|服务客户)$/u', $before . $after) === 1;
    }

    private function uniqueContacts(array $contacts): array
    {
        $result = [];
        foreach ($contacts as $contact) {
            $contact = trim((string)$contact);
            if ($contact === '' || isset($result[$contact]) || $this->isServiceNumber($contact)) {
                continue;
            }
            $result[$contact] = $contact;
        }

        return array_values($result);
    }
}
