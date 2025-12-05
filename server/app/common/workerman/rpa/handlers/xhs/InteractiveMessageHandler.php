<?php

namespace app\common\workerman\rpa\handlers\xhs;

use app\common\workerman\rpa\BaseMessageHandler;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvInteraction;
use app\common\model\sv\SvPublishSettingDetail;
use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\WorkerEnum;

class InteractiveMessageHandler extends BaseMessageHandler
{
    protected array $contentType = array(
        '笔记' => 1,
        '收藏' => 2,
        '标记' => 3
    );
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;

            $this->service->getRedis()->set("xhs:device:" . $this->payload['deviceId'] . ":taskStatus", json_encode([
                'taskStatus' => 'running',
                'taskType' => 'getInteractiveMessage',
                'duration' => 10,
                'scene' => 'xhs',
                'msg' => '正在获取小红书已发布内容状态、收藏、点赞信息',
                'time' => date('Y-m-d H:i:s', time()),
            ], JSON_UNESCAPED_UNICODE));

            if ($this->msgType == WorkerEnum::RPA_PUBLISHED_POST_STATUS) {
                $this->_updatePostListStatus($content);
            } else if ($this->msgType == WorkerEnum::WEB_POST_STATUS_LIST) {
                $this->_getPostStatusByRpa($content);
            }
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'post');
            $this->payload['reply'] = "异常信息:" . $e->getMessage();
            $this->payload['code'] =  WorkerEnum::UPDATE_POST_INFO_FAIL;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }

    private function _getPostStatusByRpa($content)
    {
        try {


            $worker = $this->service->getWorker();
            $uid = $worker->devices[$this->payload['deviceId']] ?? '';
            if ($uid == '') {
                $this->payload['reply'] = "设备{$this->payload['deviceId']}不在线,无法获取账号信息";
                $this->payload['code'] = WorkerEnum::DEVICE_NOT_ONLINE;
                $this->sendError($this->connection,  $this->payload);
                return;
            }
            $message = array(
                'messageId' => $uid,
                'deviceId' => $this->payload['deviceId'],
                'type' => WorkerEnum::TO_RAP_POST_STATUS_LIST,
                'appVersion' => WorkerEnum::APP_VERSION,
                'appType' => $this->payload['appType'] ?? 3,
                'code' => WorkerEnum::SUCCESS_CODE,
                'reply' => [
                    'type' => WorkerEnum::TO_RAP_POST_STATUS_LIST,
                    'msg' => '获取笔记列表信息',
                    'deviceId' => $this->payload['deviceId']
                ]

            );

            $this->sendResponse($uid, $message, $message['reply']);
            $this->setLog($message, 'user');
        } catch (\Exception $e) {
            $this->setLog('_getPostStatusByRpa' . $e, 'error');
        }
    }


    private function _updatePostListStatus($content)
    {
        try {

            $user = SvAccount::where('device_code', $this->payload['deviceId'])->limit(1)->find();
            if (empty($user)) {
                $this->setLog('异常信息:该设备缺少用户信息.' . $this->payload['deviceId'], 'post');
                return;
            }

            $insertData = array();
            foreach ($content as $item) {
                $this->setLog($item, 'post');
                preg_match_all('/\d+/', ($item['browseFavorited'] ?? ''), $matches);
                $this->setLog($matches, 'post');
                $numbers = $matches[0] ?? [];
                $this->setLog($numbers, 'post');
                $praises = $numbers[0] ?? 0;
                $this->setLog('praises :' . $praises, 'post');
                $views = $numbers[1] ?? 0;
                $this->setLog('views :' . $praises, 'post');

                $find = SvPublishSettingDetail::field('*')
                    ->where('user_id', $user['user_id'])
                    ->where('device_code', $this->payload['deviceId'])
                    ->where('account', $user['account'])
                    ->where('account_type', $user['type'])
                    ->where('material_title', $item['title'])
                    ->where('platform', 3)
                    ->where('status', 3)
                    ->findOrEmpty();
                if (!$find->isEmpty()) {
                    $find->praises = $praises;
                    $find->views = $views;
                    $find->update_time = time();
                    $find->save();
                    $this->setLog($find, 'post');
                }

                array_push($insertData, array(
                    'user_id' => $user['user_id'],
                    'device_code' => $this->payload['deviceId'],
                    'account' => $user['account'],
                    'type' => 3,
                    'content_type' => $this->contentType[$item['contentType']] ?? 4, //4未知
                    'title' => $item['title'],
                    'browse_favorited' => $praises,
                    'liked' => $views,
                    'comments_count' => $item['commentsCount'] ?? 0,
                    'original_data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                    'create_time' => time()
                ));
            }

            if (!empty($insertData)) {
                $model = new SvInteraction();
                $result = $model->saveAll($insertData);
            }

            $this->payload['reply'] = '发布内容信息更新成功';
            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('_updatePostListStatus' . $e, 'error');
        }
    }
}
