<?php

namespace app\common\workerman\rpa\handlers\wechat;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;

use app\common\model\sv\SvAddWechatRecord;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatLog;
use app\common\model\sv\SvCrawlingManualTaskRecord;
use app\common\workerman\rpa\WorkerEnum;



class AddWechatTaskNoticeHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;

            $this->payload['reply'] = $this->setAddWechatTaskNotice($content);

            //$this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'error');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        } finally {
            unset($content);
        }
    }

    private function setAddWechatTaskNotice(array $content)
    {
        try {
            if($content['isManual'] == 0){
                $record = SvAddWechatRecord::where('id', $content['recordId'])->limit(1)->findOrEmpty();
            }else{
                $record = SvCrawlingManualTaskRecord::where('id', $content['recordId'])->limit(1)->findOrEmpty();
            }

            if ($record->isEmpty()) {
                throw new \Exception('添加微信好友记录不存在');
            }

            $maps = array(
                0 => '添加请求成功',
                1 => '用户不存在',
                2 => '已经是好友了',
                3 => '添加请求操作过于频繁，请稍后再试',
                4 => '添加请求当前账号存在安全风险，暂时无法添加朋友',
            );

            $record->image = $this->saveBase64ToImage($content['image'] ?? '', generate_unique_task_id(), 'wechat');
            $record->status = (int)$content['status'] === 0 ? 1 : 0;
            $record->result = $maps[$content['status']] ?? '未知状态：' . $content['status'];
            $record->update = time();
            $record->save();

            $wechat = AiWechat::where('wechat_id', '=', $record->wechat_no)->limit(1)->findOrEmpty();
            if (!$wechat->isEmpty()) {
                $wechat->update_time = time();
                $wechat->save();
            }

            if ($record->status === 1) {
                AiWechatLog::create([
                    'user_id'   => $record->user_id,
                    'wechat_id' => $record->wechat_no,
                    'friend_id' => $record->reg_wechat ?? $record->clue_wechat,
                    'log_type'      => AiWechatLog::TYPE_THROUGH_FRIEND,
                    'create_time' => time()
                ]);
            }
            return '添加微信记录状态更新成功';
        } catch (\Throwable $th) {
            $this->setLog('异常信息' . $th->__toString());
            throw new \Exception($th->getMessage(), $th->getCode());
        }
        return '添加微信记录状态更新失败';
    }
}
