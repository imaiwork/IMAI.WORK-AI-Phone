<?php
declare (strict_types=1);

namespace app\api\logic\wechat\sop;

use app\api\logic\ApiLogic;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatContact;
use app\common\model\wechat\sop\AiWechatSopPushContent;
use app\common\model\wechat\sop\AiWechatSopPushLog;
use app\common\model\wechat\sop\AiWechatSopPushMember;
use app\common\model\wechat\sop\AiWechatSopPushTime;
use app\common\service\FileService;
use app\common\traits\WechatTrait;
use think\facade\Log;

class PushContentLogic extends ApiLogic
{
    use WechatTrait;

    /**
     * 创建推送内容
     */
    public static function createPushContent(array $params): bool
    {
        try {
            $time = $params['time'];
            // 创建推送时间
            $result = PushTimeLogic::createPushTime($time, $params['push_id']);
            if (!$result) {
                throw new \Exception(PushTimeLogic::getError());
            }
            $pushTime = PushTimeLogic::getReturnData();
            // 创建推送内容
            $pushContentParams = [
                'content'        => json_encode($params['content']),
                'push_id'        => $params['push_id'],
                'user_id'        => self::$uid,
                'extend_content' => $params['extend_content'] ?? '',
                'push_time_id'   => $pushTime['id'], // 使用 push_time_id
            ];
            $pushContent = AiWechatSopPushContent::create($pushContentParams);
            if (!$pushContent) {
                throw new \Exception('创建推送内容失败');
            }

            PushContentLogic::detail(['content_id' => $pushContent->id]);
            $pushContents = PushLogic::getReturnData();
            self::$returnData = $pushContents;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新推送内容
     */
    public static function updatePushContent(array $params): bool
    {
        try {
            $pushContent = AiWechatSopPushContent::where(['id' => $params['content_id'], 'user_id' => self::$uid])
                                                 ->find();
            if (!$pushContent) {
                throw new \Exception('推送内容不存在');
            }
            $pushContent->content = json_encode($params['content']);
            $pushContent->extend_content = isset($params['extend_content']) ? json_encode($params['extend_content']) : '';
            $pushContent->update_time = time();
            $pushContent->save();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除推送内容
     */
    public static function deletePushContent(array $params): bool
    {
        try {

            $pushContent = AiWechatSopPushContent::where(['id' => $params['content_id'], 'user_id' => self::$uid])
                                                 ->find();
            if (!$pushContent) {
                throw new \Exception('推送内容不存在');
            }
            $pushContent->delete();

            $pushTime = AiWechatSopPushTime::where(['id' => $pushContent->push_time_id, 'user_id' => self::$uid])
                                           ->find();
            $pushTime->delete();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取推送内容列表
     */
    public static function detail(array $params): bool
    {
        try {
            $pushContent = AiWechatSopPushContent::where('delete_time', 'null')
                                                 ->where('user_id', self::$uid)
                                                 ->where('id', $params['content_id'])
                                                 ->findOrEmpty();
            if ($pushContent->isEmpty()) {
                throw new \Exception('推送内容不存在');
            }
            $pushTime = AiWechatSopPushTime::where('id', $pushContent->push_time_id)->findOrEmpty();
            if ($pushTime->isEmpty()) {
                throw new \Exception('推送内容的时间异常');
            }
            $pushContent = $pushContent->toArray();
            $pushTime = $pushTime->toArray();
            $pushContent['content'] = json_decode($pushContent['content'], true);
            $pushContent['order_day'] = $pushTime['order_day'];
            $pushContent['push_time'] = $pushTime['push_time'];
            self::$returnData = $pushContent;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * 创建多个推送内容
     */
    public static function createPushContents(array $params): bool
    {
        dd(1);
        try {
            // 开始事务
            AiWechatSopPushContent::startTrans();

            $results = [];
            // 检查是否为多个推送内容
            if (isset($params['push_contents']) && is_array($params['push_contents'])) {
                foreach ($params['push_contents'] as $content) {
                    // 将推送内容参数分开
                    $pushContentParams = [
                        'content'        => $content['content'],
                        'type'           => $content['type'],
                        'extend_content' => $content['extend_content'],
                        'push_time'      => $content['push_time'],
                    ];

                    // 插入推送内容
                    $pushContent = AiWechatSopPushContent::create($pushContentParams);
                    if (!$pushContent) {
                        throw new \Exception('创建推送内容失败');
                    }
                    $results[] = $pushContent->toArray();
                }

                // 插入推送时间
                $pushTimeParams = [
                    'order_day' => $params['order_day'],
                    'push_id'   => $params['push_id'],
                    // 其他推送时间参数
                ];
                self::createPushTime($pushTimeParams);
            }

            // 提交事务
            AiWechatSopPushContent::commit();
            return true;
        } catch (\Exception $e) {
            AiWechatSopPushContent::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function sopPushJob(): bool
    {
        try {
            $startTime = date('H:i:00', time());
            $endTime = date('H:i:59', time());
            $date = date('Y-m-d', time());
            $logs = AiWechatSopPushLog::alias('l')
                                      ->join('ai_wechat_sop_push_member m', 'l.member_id = m.id')
                                      ->field('l.id,l.push_real_day,l.push_real_time,l.status,l.content,m.wechat_id,m.friend_id,m.nickname,m.user_id')
                                      ->where('l.push_real_day', $date)
                                      ->where('l.push_real_time', 'between', [$startTime, $endTime])
                                      ->where('l.status', 0)
                                      ->select();
            if ($logs->isEmpty()) {
                return false;
            }
            $logs = $logs->toArray();
            foreach ($logs as $log) {
                $contents = json_decode($log['content'], true);
                foreach ($contents as $content) {
                    $message = self::format_content($content);
                    if ($message) {
                        $responses[] = self::wxPush([
                                                        'wechat_id'    => $log['wechat_id'],
                                                        'friend_id'    => $log['friend_id'],
                                                        'message'      => $message['message'],
                                                        'message_type' => $message['message_type'],
                                                        'device_code'  => AiWechat::where('user_id', $log['user_id'])
                                                                                  ->where('wechat_id', $log['wechat_id'])
                                                                                  ->value('device_code'),
                                                        'opt_type'     => 'sop'
                                                    ]);
                    }

                }
                Log::write(json_encode($responses), 'system');
                foreach ($responses as $response) {
                    if ($response['code'] == 10000) {
                        $status = 1;
                    } else {
                        $status = 2;
                        break;
                    }
                }
                AiWechatSopPushLog::where('id', $log['id'])->update(['status' => $status, 'update_time' => time()]);
            }
            return true;
        } catch (\think\exception\HttpResponseException $e) {
                $e->getResponse()->getData()['msg'] ?? '提交任务出错';
            return false;
        }
    }

    /**
     * sop被动触发消息推送
     */
    public static function sopStagetriggerPushJob(): bool
    {
        try {
            $startTime = date('H:i:00', time());
            $endTime = date('H:i:59', time());
            $date = date('Y-m-d', time());
            $contents = AiWechatSopPushContent::alias('c')
                                              ->join('ai_wechat_sop_push_time t', 'c.push_time_id = t.id')
                                              ->join('ai_wechat_sop_push p', 't.push_id = p.id')
                                              ->where('t.push_real_day', $date)
                                              ->where('t.push_time', $startTime)
                                              ->where('p.status', 2)
                                              ->where('p.push_type', 1)
                                              ->field('c.id as content_id,c.content,c.user_id,c.push_id,c.push_time_id,t.push_real_day,t.push_time,p.type,p.flow_id,p.stage_id')
                                              ->select();

            if ($contents->isEmpty()) {
                return false;
            }

            $contents = $contents->toArray();

            foreach ($contents as $content) {
                $where = [];
                $items = json_decode($content['content'], true);
                // 1-流程推送,2-阶段推送,3-生日推送,4-节日推送
                if (in_array($content['type'], [1,3,4])){
                    $where[] = ['flow_id','=',(int)$content['flow_id']];
                }else{
                    $where[] = ['flow_id','=',(int)$content['flow_id']];
                    $where[] = ['stage_id','=',(int)$content['stage_id']];
                }

                $members = AiWechatSopPushMember::where($where)->select();
                if (!$members->isEmpty()) {
                    $members = $members->toArray();
                    foreach ($members as $member) {
                        if ($content['type'] == 3){
                            $birthday = AiWechatContact::where('wechat_id', $member['wechat_id'])
                                                       ->where('friend_id', $member['friend_id'])
                                                       ->value('birth_date');
                            if (!str_contains($birthday, date('m-d'))){
                                continue;
                            }
                        }
                        $responses = [];
                        foreach ($items as $item) {
                            $item = self::format_content($item);
                            $responses[] = self::wxPush([
                                                            'wechat_id'    => $member['wechat_id'],
                                                            'friend_id'    => $member['friend_id'],
                                                            'message'      => $item['message'],
                                                            'message_type' => $item['message_type'],
                                                            'device_code'  => AiWechat::where('user_id', $member['user_id'])
                                                                                      ->where('wechat_id', $member['wechat_id'])
                                                                                      ->value('device_code'),
                                                            'opt_type'     => 'sop'
                                                        ]);
                        }

                        //微信消息响应结果
                        foreach ($responses as $response) {
                            if ($response['code'] == 10000) {
                                $status = 1;
                            } else {
                                $status = 2;
                                break;
                            }
                        }

                        //记录该成员本次成功的推送记录
                        if ($status == 1) {
                            $data = [
                                'member_id'      => $member['id'],
                                'user_id'        => $member['user_id'],
                                'push_id'        => $content['push_id'],
                                'content_id'     => $content['content_id'],
                                'content'        => $content['content'],
                                'push_real_day'  => $content['push_real_day'],
                                'push_real_time' => $content['push_time'],
                                'status'         => 1,
                                'create_time'    => time()
                            ];
                            AiWechatSopPushLog::create($data);
                        }

                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function format_content(array $content): array
    {
        switch ($content['type']) {
            case 0: //文本
                // 推送消息
                $message['message'] = $content['content'];
                $message['message_type'] = 1;
                break;
            case 1: //图片
                // 推送消息
                $message['message'] = FileService::getFileUrl($content['content']);
                $message['message_type'] = 2;
                break;
            case 2: //视频
                // 推送消息
                $message['message'] = FileService::getFileUrl($content['content']);
                $message['message_type'] = 4;
                break;
            case 3: //链接
                // 推送消息
                $message['message'] = json_encode([
                                                      'title' => $content['content']['name'] ?? '',
                                                      'desc' => $content['content']['desc'] ?? '',
                                                      'url' => $content['content']['link'] ?? '',
                                                      'thumb' => FileService::getFileUrl($content['content']['img'] ?? ''),
                                                  ]);
                $message['message_type'] = 6;
                break;
            case 4: //小程序
                // 推送消息
                $message['message'] = json_encode([
                                                      "Title"      => $content['content']['name'] ?? '',
                                                      "Url"        => "https://mp.weixin.qq.com/mp/waerrpage?appid={$content['content']['appid']}&type=upgrade&upgradetype=3#wechat_redirect",
                                                      "PagePath"   => $content['content']['link'] ?? '',
                                                      "Source"     => '',
                                                      "SourceName" => '',
                                                      "Thumb"      => FileService::getFileUrl($content['content']['pic'] ?? ''),
                                                      "AppId"      => $content['content']['appid'] ?? '',
                                                      "Icon"       => FileService::getFileUrl($content['content']['pic'] ?? ''),
                                                      'version'    => 48,
                                                  ], JSON_UNESCAPED_UNICODE);
                $message['message_type'] = 13;
                break;
            case 5: //文件
                // 推送消息
                $message['message'] = json_encode($content['content']);
                $message['message_type'] = 8;
                break;
            default:
                $message = [];
        }
        return $message;
    }
} 