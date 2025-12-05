<?php

namespace app\common\logic;

use app\api\logic\ChatLogic;
use app\common\logic\BaseLogic;
use app\common\model\assistants\Assistants;
use app\common\model\chat\RobotRecord;
use app\common\model\gptModel\ChatLog;
use app\common\model\gptThread\GptThread;
use app\common\service\openai\ThreadService;

class GptThreadLogic extends BaseLogic
{
    /**
     * 添加
     * @param array $params
     * @param int $userID
     * @return bool
     * @author L
     * @data 2024/6/11 14:18
     */
    public static function add(array $params, int $userID): bool
    {
        try {
            $assistantInfo = Assistants::findOrEmpty($params['assistant_id']);
            if ($assistantInfo->isEmpty() || $assistantInfo->status == 0) {
                throw new \Exception("当前助手异常");
            }


            $result = (new ThreadService())::createThread();
            if (!empty($result['error'])) {
                throw new \Exception($result['error']['message']);
            }

            $threadOpenData = [
                'assistant_id' => $assistantInfo->assistants_id
            ];
           /* $runResult      = (new ThreadService($result['id'], data: $threadOpenData))::runThread();

            if (!empty($runResult['error'])) {
                throw new \Exception($runResult['error']['message']);
            }*/

            $params['assistants_id'] = $assistantInfo->id;
            $params['user_id']       = $userID;
            $params['thread_id']     = $result['id'];
//            $params['run_id']        = $runResult['id'];
            GptThread::create($params);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 删除
     * @param int $threadId
     * @param int $userId
     * @return bool
     * @throws \Exception
     * @author L
     * @data 2024/6/11 15:13
     */
    public static function delete(int $threadId, int $userId): bool
    {
        try {
            $threadInfo = GptThread::where('user_id', $userId)->findOrEmpty($threadId);
            if ($threadInfo->isEmpty()) {
                throw new \Exception("会话异常");
            }
            $result = (new ThreadService($threadInfo->thread_id))::deleteThread();
            if (!empty($result['error'])) {
                throw new \Exception($result['error']['message']);
            }

            GptThread::destroy($threadInfo->id);
            ChatLog::destroy(function ($query) use($threadInfo) {
                $query->where('thread_id', $threadInfo->id);
            });
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}