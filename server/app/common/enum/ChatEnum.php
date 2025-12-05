<?php

declare (strict_types = 1);

namespace app\common\enum;

class ChatEnum
{
    const MODEL_TYPE_CHAT = 1;       // 对话模型
    const MODEL_TYPE_EMB  = 2;       // 向量模型
    const MODEL_TYPE_DRAW  = 3;       // 生图模型
    const MODEL_TYPE_HUMAN  = 4;       // 数字人模型
    const MODEL_TYPE_RANKING  = 11;  // 重排模型
    const API2D35 = 'api2d3.5'; // api2d3.5
    const API2D40 = 'api2d4.0'; // api2d4.0
    const ZHUPI   = 'zhipu';    // ChatGLM
    const XUNFEI  = 'xunfei';   // 讯飞星火
    const BAIDU   = 'baidu';    // 文言一心
    const QWEN    = 'qwen';     // 通义千问
    const DOUBAO  = 'doubao';   // 字节豆包
    const OLLAMA  = 'ollama';   // ollama
    const MINIMAX = 'minimax';  // MiniMax

    /**
     * 获取模型名称
     *
     * @param bool|string $from
     * @return array|string
     * @author kb
     */
    public static function getAiModelName(bool|string $from = true): array|string
    {
        $desc = [
            self::API2D35 => 'api2d3.5',
            self::API2D40 => 'api2d4.0',
            self::ZHUPI   => '智普AI',
            self::XUNFEI  => '讯飞星火',
            self::BAIDU   => '文心一言',
            self::QWEN    => '通义千问',
            self::DOUBAO  => '字节豆包',
            self::OLLAMA  => 'Ollama',
            self::MINIMAX => 'MiniMax',
        ];
        if(true === $from) {
            return $desc;
        }
        return $desc[$from] ?? '';
    }
}