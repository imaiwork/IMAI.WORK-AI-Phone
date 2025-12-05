<?php


namespace app\adminapi\logic;


use app\common\model\ChatPrompt;
use app\common\logic\BaseLogic;

/**
 * 聊天提示词类逻辑层
 * Class ChatPromptLogic
 * @package app\adminapi\logic\human
 */
class ChatPromptLogic extends BaseLogic
{
    /**
     * @notes 获取配置
     * @return array
     * @author 段誉
     * @date 2021/12/31 11:03
     */
    public static function getPrompt(): array
    {

        $config = ChatPrompt::select()->toArray();

        return $config;
    }

    /**
     * @notes 更新提示词
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2024/12/19 10:35
     */
    public static function updatePrompt(array $data): bool
    {
        //查询是否存在
        $prompt = ChatPrompt::where('id', $data['id'])->findOrEmpty();
        if ($prompt->isEmpty()) {
            self::setError('提示词不存在');
            return false;
        }
        $prompt->prompt_name = $data['prompt_name'];
        $prompt->prompt_text = $data['prompt_text'];
        $prompt->save();
        return true;
    }
}
