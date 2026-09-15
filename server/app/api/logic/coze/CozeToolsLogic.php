<?php

namespace app\api\logic\coze;

use app\api\logic\ApiLogic;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\ToolsService;

class CozeToolsLogic extends ApiLogic
{

    public static function fileParse(string $file_url): string
    {
        try {
            $response = ToolsService::Coze()->fileParse($file_url);
            if ((int)$response['code'] !== 10000) {
                throw new \Exception($response['msg'] ?? '文件解析失败');
            }
            $fileContent = $response['data']['output'] ?? '';
            if (empty($fileContent)) {
                throw new \Exception('文件解析内容为空');
            }
            //扣费
            return $fileContent;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return '';
        }
    }

    public static function networkSearch(string $prompt): string
    {
        try {
            $response = ToolsService::Coze()->networkSearch($prompt);
            if ((int)$response['code'] !== 10000) {
                throw new \Exception($response['msg'] ?? '网络搜索失败');
            }
            $netContent = $response['data']['output'] ?? '';
            if (empty($netContent)) {
                throw new \Exception('网络搜索内容为空');
            }
            //扣费
            return $netContent;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return '';
        }
    }
}
