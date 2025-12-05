<?php


namespace app\api\logic;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\mindMap\MindMap;
use app\common\model\user\User;
use app\common\model\ChatPrompt;

/**
 * 文章逻辑
 * Class ArticleLogic
 * @package app\api\logic
 */
class MindMapLogic extends ApiLogic
{


    /**
     * 删除
     * @param array $params
     * @return bool
     * @author L
     * @data 2024/7/1 15:06
     */
    public static function delete(array $params): bool
    {
        try {

            if (is_string($params['id'])) {
                MindMap::destroy(['id' => $params['id'], 'user_id' => self::$uid]);
            } else {
                MindMap::whereIn('id', $params['id'])->where('user_id', self::$uid)->select()->delete();
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 编辑
     * @param array $params
     * @return bool
     * @author L
     * @data 2024/7/1 15:08
     */
    public static function edit(array $params): bool
    {
        //设置不超时
        set_time_limit(0);

        //计费单价
        $unit = TokenLogService::checkToken(self::$uid, 'mind_map');

        try {
            $mindMapInfo = MindMap::where('user_id', self::$uid)->findOrEmpty($params['id']);

            if ($mindMapInfo->isEmpty()) {

                throw new \Exception("信息异常");
            }

            $prompt = ChatPrompt::where('id', 2)->value('prompt_text') ?? '';

            if (!$prompt) {

                throw new \Exception("关键词丢失");
            }

            $request = [
                "messages" => [
                    [
                        'role'    => "system",
                        'content' => $prompt
                    ],
                    [
                        'role'    => "user",
                        'content' => $params['message']
                    ]
                ],
                'stream' => false,
                'message' => $params['message'],
                'task_id' => $mindMapInfo->task_id,
                'user_id' => self::$uid,
                'assistant_id' => 0,
                'chat_type' => AccountLogEnum::TOKENS_DEC_MIND_MAP,
                'now' => time(),
            ];

            $response = \app\common\service\ToolsService::Chat()->message($request);

            $reply = $response['data']['choices'][0]['message']['content'] ?? '';

            //计费
            $tokens = $response['data']['usage']['total_tokens'] ?? 0;

            if (!$reply || $tokens == 0) {

                throw new \Exception("分析失败");
            }

            $response = [
                'reply' => $reply,
                'usage_tokens' => $response['data']['usage'] ?? [],
            ];

            // 保存聊天记录
            ChatLogic::saveChatResponseLog($request, $response);

            //计算消耗tokens
            $points = $unit > 0 ? round($tokens / $unit,2) : 0;

            //token扣除
            User::userTokensChange(self::$uid, $points);

            $extra = ['总消耗tokens数' => $tokens, '算力单价' => $unit, '实际消耗算力' => $points];

            //扣费记录
            AccountLogLogic::recordUserTokensLog(true, self::$uid, AccountLogEnum::TOKENS_DEC_MIND_MAP, $points, $mindMapInfo->task_id, $extra);

            $mindMapInfo->ask = $request['message'];
            $mindMapInfo->reply = $reply;
            $mindMapInfo->task_time = time() - $request['now'];
            $mindMapInfo->save();

            self::$returnData = $mindMapInfo->toArray();
            return true;
        } catch (\think\exception\HttpResponseException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 详情
     * @param array $params
     * @return bool
     * @author L
     * @data 2024/7/1 15:30
     */
    public static function detail(array $params): bool
    {
        try {
            self::$returnData = MindMap::where('user_id', self::$uid)->findOrEmpty($params['id'])->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
