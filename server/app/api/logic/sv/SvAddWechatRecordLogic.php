<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvAddWechatStrategy;
use app\common\model\wechat\AiWechat;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvCrawlingTask;
use think\facade\Cache;
/**
 * StrategyLogic
 * @desc 加微策略
 * @author Qasim
 */
class SvAddWechatRecordLogic extends SvBaseLogic
{

    public static function retry(array $params)
    {
        try {
            $record = SvAddWechatRecord::field('*')->where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($record->isEmpty()) {
                throw new \Exception('添加记录不存在', 400);
            }
            
            if((int)$record->status === 1){
                throw new \Exception('请勿重复执行', 400);
            }

            $account = SvAccount::where('device_code', $record['device_code'])->where('user_id', self::$uid)->findOrEmpty();
            if($account->isEmpty()){
                throw new \Exception('设备账号信息不存在,请先更新设备账号信息', 400);
            }

            // $strategy = SvAddWechatStrategy::where('device_code', $record['device_code'])->where('account', $account['account'])->limit(1)->findOrEmpty();
            // if($strategy->isEmpty()){
            //     throw new \Exception('请先配置加微策略', 400);
            // }

            $crawlingTask = SvCrawlingTask::where('id', $record['crawling_task_id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($crawlingTask->isEmpty()) {
                throw new \Exception('获客任务不存在', 400);
            }

            if ($crawlingTask['add_type'] !== 1) {
                throw new \Exception('仅支持加微任务重试', 400);
            }

            if (empty($crawlingTask['wechat_id'])) {
                throw new \Exception('获客任务未配置微信号', 400);
            }

            
            $currentTime = time(); // 获取当前时间戳
            $coolingThreshold = $currentTime - 7200; // 2小时前的时间戳（7200秒）
            $wechat = AiWechat::field('*')
                ->where('user_id', self::$uid)
                ->where('wechat_id', 'in', explode(',', $crawlingTask['wechat_id']))
                ->where(function($query) use ($coolingThreshold) {
                    $query->where('is_cooling', 0)
                        ->whereOr('cooling_time', '<', $coolingThreshold);
                })
                ->order('update_time asc')->limit(1)->findOrEmpty();
            if($wechat->isEmpty()){
                throw new \Exception('当前账号存在安全风险，暂停添加', 400);
            }

            $messages = json_decode($crawlingTask['remarks'], true) ?? [];
            $message = $messages[array_rand($messages)] ?? '您好！';

            $message = [
                'DeviceId' => $wechat['device_code'],
                'WeChatId' => $wechat['wechat_id'],
                'Phones' => [$record['reg_wechat']],
                'Message' => $message,
                'TaskId' => $record['task_id'],
                'Remark' => $record['Remark'] ?? '',
            ];
            //print_r($message);die;
            $content = \app\common\workerman\wechat\handlers\client\AddFriendsTaskHandler::handle($message);

            $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
            $message->setMsgType($content['MsgType']);

            $any = new \Google\Protobuf\Any();
            $any->pack($content['Content']);
            $message->setContent($any);
            $pushMessage = $message->serializeToString();

            $channel = "socket.{$wechat['device_code']}.message";
            \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            \Channel\Client::publish($channel, [
                'data' => is_array($pushMessage) ? json_encode($pushMessage) : $pushMessage
            ]);

            $record->status = 2;
            $record->wechat_no = $wechat->wechat_id;
            $record->wechat_name = $wechat->wechat_nickname;
            $record->result = '';
            $record->update = time();
            $record->save();

            $wechat->is_cooling = 0;
            $wechat->cooling_time = 0;
            $wechat->update_time = time();
            $wechat->save();

            return true;

        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
        return true;
    }

    public static function delete(array $params)
    {
        $record = SvAddWechatRecord::field('*')
            ->where('id', $params['id'])
            ->where('user_id', self::$uid)
            ->findOrEmpty();
        if ($record->isEmpty()) {
            self::setError('添加记录不存在');
            return false;
        }
        $record->delete();

        return true;
    }
}