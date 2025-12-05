<?php

namespace app\api\logic\suno;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\suno\Suno;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\tools\SunoService;
use think\facade\Log;


/**
 * logic
 */
class SunoLogic extends BaseLogic
{
    /**
     * 添加
     * @param array $postData
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-07-03 10:09:00
     */
    public static function add(array $postData, int $userId): bool
    {
        $tokens = TokenLogService::checkToken($userId, 'mind_map');

        try {
            //token扣除
            User::userTokensChange($userId, $tokens);


            $suno = new SunoService($postData, $userId);
            $result = $suno->createMusic();
            if ($result['code'] != 200) {
                throw new \Exception($result['msg']);
            }
            self::$returnData = Suno::create([
                'user_id' => $userId,
                'task_id' => $result['data']['task_id'],
                'ask' => $postData['ask'],
                'title' => $postData['title'],
                'tags' => $postData['tags'],
                'model' => ConfigService::get('suno', 'info', '')['default'],
            ])->toArray();
            return true;
        } catch (\Exception $exception) {

            //记录 失败进行恢复
            AccountLogLogic::recordUserTokensLog(false, $userId, AccountLogEnum::TOKENS_DEC_MUSIC, $tokens);

            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 创建音乐
     * @param array $postData
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-07-03 10:09:00
     */
    public static function createMusic(array $postData, int $userId): bool
    {
        $tokens = TokenLogService::checkToken($userId, 'music');
        //token扣除
        User::userTokensChange($userId, $tokens);

        try {
            $info = Suno::json(['json_info'], true)->where('user_id', $userId)->findOrEmpty($postData['id']);
            if ($info->isEmpty()) {
                throw new \Exception("信息异常");
            }
            if (empty($info->json_info[$postData['clip_id']])) {
                throw new \Exception("音乐不存在");
            }
            $sendData = [
                'clip_id' => $postData['clip_id'],
                'type' => "concat",
            ];
            $suno = new SunoService($sendData, $userId);
            $result = $suno->createMusic();
            self::$returnData = Suno::create([
                'user_id' => $userId,
                'task_id' => $result['data']['task_id'],
                'ask' => $postData['ask'],
                'model' => ConfigService::get('suno', 'info', '')['default'],
            ])->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 修改任务状态
     * @return bool
     * @author L
     * @data 2024/7/3 16:51
     */
    public static function updateTaskStatus():bool
    {
        try {
            $list = Suno::where('status', 1)->where('dow_status', 0)->select();
            $updateInfo = [];

            $tokens = TokenLogService::getTypeScore('music');

            foreach ($list as $k => $item) {
                $suno = new SunoService(userId: $item->user_id);
                $returnData = $suno->getTaskStatus($item->task_id, $item->user_id, $tokens);
                Log::write($returnData, 'suno');
                if ($returnData['code'] == 200) {
                    $updateInfo[$k]['json_info'] = json_encode($returnData['data'], JSON_UNESCAPED_UNICODE);
                    $updateInfo[$k]['status'] = 2;
                    $updateInfo[$k]['id'] = $item->id;
                    AccountLogLogic::recordUserTokensLog(true, $item->user_id, AccountLogEnum::TOKENS_DEC_MUSIC, $tokens, $item->task_id);
                }
            }
            if (!empty($updateInfo)) {
                (new Suno())->saveAll($updateInfo);
            }
            return true;
        }catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 删除
     * @param int $id
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-07-03 10:09:00
     */
    public static function delete(int $id, int $userId): bool
    {
        try {
            Suno::destroy(['user_id' => $userId, 'id' => $id]);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 编辑
     * @param array $postData
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-07-03 10:09:00
     */
    public static function edit(array $postData, int $userId): bool
    {
        try {
            $info = Suno::where('user_id', $userId)->findOrEmpty($postData['id']);
            if ($info->isEmpty()) {
                throw new Exception("信息异常");
            }

            self::$returnData = Suno::update($postData)->toArray();
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }


    /**
     * 详情
     * @param int $id
     * @param int $userId
     * @return bool
     * @author L
     * @data 2024-07-03 10:09:00
     */
    public static function detail(int $id, int $userId): bool
    {
        try {
            self::$returnData = Suno::where('user_id', $userId)->json(['json_info'], true)->findOrEmpty($id)->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
                        