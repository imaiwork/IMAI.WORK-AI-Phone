<?php


namespace app\api\logic;


use app\api\logic\service\TokenLogService;
use app\common\{
    enum\notice\NoticeEnum,
    enum\user\AccountLogEnum,
    logic\AccountLogLogic,
    logic\BaseLogic,
    model\ChatPrompt,
    model\human\HumanVideoTask,
    model\sv\SvVideoTask,
    model\tools\ToolsLog,
    model\user\User,
    service\ConfigService,
    service\FileService,
    service\transcoding\AliyunService,
    service\transcoding\QcloudService,
    service\transcoding\QiniuService
};
use think\facade\Config;
use think\facade\Db;

/**
 * 会员逻辑层
 * Class UserLogic
 * @package app\shopapi\logic
 */
class ToolsLogic extends BaseLogic
{
    /**
     * @notes 删除
     * @param array $params
     * @param int $userId
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(array $params, int $userId): bool
    {
        $id = $params['id'] ?? 0;
        try {
            $detail = ToolsLog::where(['id' => $id, 'user_id' => $userId])->findOrEmpty();
            if ($detail->isEmpty()) {
                throw new \Exception("记录查找异常");
            }
            ToolsLog::destroy(['id' => $id, 'user_id' => $userId]);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 工具
     * @param array $params
     * @param int $userId
     * @return bool
     * @throws \Exception
     * @author L
     * @data 2024/6/17 17:28
     */
    public static function chat(array $params, int $userId): bool
    {
        $value = json_decode($params['value'], true);
        $saveData = [
            'user_id' => $userId,
            'tools_id' => $params['tools_id'],
            'ask' => $value['prompt'],
            'mode' => $value['mode'],
            'reply' => '',
            'task_time' => 0,
            'status' => 2,
        ];
        $log = ToolsLog::create($saveData);

        $now = time();
        try {
            throw new \Exception("生成异常，方法废弃,请重新尝试");
            $result = [];
            switch ($params['tools_id']) {
                case 1:
                  //  $result = (new Stability($userId, $value))->toImage();
                    break;
            }
            if ($result['code'] !== 200) {
                $log->status = 3;
                $log->save();
                throw new \Exception("生成异常，请重新尝试");
            }
            $log->task_time = time() - $now;
            $log->status = 1;
            $log->reply = $result['file_url'];
            $log->save();
            $saveData['file_url'] = $result['file_url'];
            $saveData['id'] = $log->id;
            self::$returnData = $saveData;
            return true;
        } catch (\Exception $exception) {
            $log->status = 3;
            $log->save();
            self::setError($exception->getMessage());
            return false;
        }
    }

    public static function getPrompt($request)
    {
        //查询是否存在
        $ChatPrompt = $prompt = ChatPrompt::select();
        if ($prompt->isEmpty()) {
            self::setError('提示词不存在');
            return false;
        }
        try {
            self::$returnData = $ChatPrompt->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function clip()
    {
        try {
            $response = \app\common\service\ToolsService::Auth()->clipNotice();
            self::$returnData = $response;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


}
