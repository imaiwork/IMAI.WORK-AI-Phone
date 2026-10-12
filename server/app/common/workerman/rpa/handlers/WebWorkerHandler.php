<?php

namespace app\common\workerman\rpa\handlers;

use app\common\cache\UserTokenCache;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;

class WebWorkerHandler extends BaseMessageHandler
{
    protected int $msgType;
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        $content['userId'] = $content['userId'] ?? 0;
        try {
            $this->uid = $uid;
            $this->payload = $payload;
            $this->connection = $connection;
            $userInfo = $this->getAuthenticatedUserInfo($payload, $content);
            $this->userId = (int)$userInfo['user_id'];
            $content['userId'] = $this->userId;
            $worker = $this->service->getWorker();
            $source = (string)($content['sourceType'] ?? WorkerEnum::WS_SOURCE_PC_TYPE);
            if (!in_array($source, WorkerEnum::WS_SOURCES, true)) {
                $source = WorkerEnum::WS_SOURCE_PC_TYPE;
            }
            $oldUid = $this->service->getRepository()->getWebUserUid($source, $this->userId);
            if (isset($worker->uidConnections[$uid])) {

                // foreach ($worker->uidConnections as $connection) {
                //     if($connection->userid == $this->userId && $connection->uid !== $uid){
                //         $this->setLog('删除用户旧的socket连接, 设备号:' . $connection->userid . ', uid:' . $connection->uid . ', name:' . $connection->name, 'ws');
                //         $connection->close();
                //     }
                // }

                $worker->uidConnections[$uid]->apptype = WorkerEnum::WS_WEB_TYPE;
                $worker->uidConnections[$uid]->userid = $this->userId;
                $worker->uidConnections[$uid]->clientType = WorkerEnum::WS_CLIENT_TYPE;
                $worker->uidConnections[$uid]->sourceType = $source;
                $worker->uidConnections[$uid]->name = 'web_' . $this->userId;

                $this->service->getRepository()->bindWebUser($source, $this->userId, $uid, (int)$worker->id);
                $this->service->setWorker($worker);
                $this->service->closeWebUserConnections($this->userId, $source, $uid, 'WebSocket连接被替换');
                if ($oldUid !== null && $oldUid !== '' && $oldUid !== $uid) {
                    $this->service->closeConnection($oldUid, 'WebSocket连接被替换');
                }
            }

            $message = array(
                'messageId' => $uid,
                'type' => 'bindSocket',
                'reply' => [
                    'type' => 'bindSocket',
                    'msg' => '绑定成功',
                    'userId' => $this->userId,
                    'sourceType' => $source,
                    'wsId' => $uid
                ],
                'code' => WorkerEnum::SUCCESS_CODE
            );

            $this->sendResponse($uid, $message, $message['reply']);
            $this->registerChannelListener($this->connection, (string)$this->userId, 'webUser');
            $this->setLog($message, 'bind');
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'bind');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  $e->getCode() ?: WorkerEnum::WED_BIND_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        } finally{
            unset($content);
        }
    }


    private function getAuthenticatedUserInfo(array $payload, array $content): array
    {
        $token = trim((string)($content['token'] ?? $payload['token'] ?? ''));
        if ($token === '') {
            throw new \Exception('请先登录', WorkerEnum::WEB_USER_INVALID_REQUEST);
        }

        $userInfo = (new UserTokenCache())->getUserInfo($token);
        if (empty($userInfo)) {
            throw new \Exception('登录超时，请重新登录', WorkerEnum::WEB_USER_INVALID_REQUEST);
        }

        if (!empty($userInfo['is_disable'] ?? 0)) {
            throw new \Exception('账号已被禁用', WorkerEnum::WEB_USER_INVALID_REQUEST);
        }

        $tokenUserId = (int)($userInfo['user_id'] ?? 0);
        if ($tokenUserId <= 0) {
            throw new \Exception('用户参数无效', WorkerEnum::WEB_USER_INVALID_REQUEST);
        }

        $requestUserId = (int)($content['userId'] ?? 0);
        if ($requestUserId > 0 && $requestUserId !== $tokenUserId) {
            throw new \Exception('用户身份不匹配', WorkerEnum::WEB_USER_INVALID_REQUEST);
        }

        return $userInfo;
    }

}
