<?php


namespace app\common\enum\kb;

class RobotEnum
{
    // 分享类型
    const SHARE_TYPE_WEB = 1; // 网页
    const SHARE_TYPE_JS  = 2; // JS
    const SHARE_TYPE_OA  = 3; // 公众号
    const SHARE_TYPE_API = 4; // API
    const SHARE_TYPE_QWX = 5; // 企业微信
    const SHARE_TYPE_WX  = 6; // 个人微信
    const SHARE_TYPE_TD  = 7; // 影刀

    // 空回复类型
    const EMPTY_ANSWER_AI   = 1; // 检索为空: AI回复
    const EMPTY_ANSWER_NULL = 2; // 检索为空: 指定文本

    /**
     * @notes 获取密钥前缀
     * @param bool|string $from
     * @return array|string
     */
    public static function getSecretPrefix(bool|string $from = true): array|string
    {
        $desc = [
            self::SHARE_TYPE_WEB => 'web',
            self::SHARE_TYPE_JS  => 'js',
            self::SHARE_TYPE_OA  => 'oa',
            self::SHARE_TYPE_API => 'api',
            self::SHARE_TYPE_QWX => 'qwx',
            self::SHARE_TYPE_WX  => 'wx',
            self::SHARE_TYPE_TD  => 'yd'
        ];
        if(true === $from) {
            return $desc;
        }
        return $desc[$from] ?? '';
    }

    /**
     * @notes 获取密钥前缀
     * @param bool|string $from
     * @return array|string
     */
    public static function getSecretDesc(bool|string|int $from = true): array|string
    {
        if (is_numeric($from)) {
            $desc = [
                self::SHARE_TYPE_WEB => '网页',
                self::SHARE_TYPE_JS  => 'JS',
                self::SHARE_TYPE_API => 'API',
                self::SHARE_TYPE_OA  => '公众号',
                self::SHARE_TYPE_QWX => '企业微信',
                self::SHARE_TYPE_WX  => '个人微信',
                self::SHARE_TYPE_TD  => '影刀RPA'
            ];
        } else {
            $desc = [
                'web' => '网页',
                'js'  => 'JS',
                'api' => 'API',
                'oa'  => '公众号',
                'qwx' => '企业微信',
                'wx'  => '个人微信',
                'yd'  => '影刀RPA',
            ];
        }
        if(true === $from) {
            return $desc;
        }
        return $desc[$from] ?? '';
    }


    public static function getPromptTpl($type = ''): string
    {
        if ($type == 'chat') {
            $template = "使用 <Reference></Reference> 标记中的文件内容作为本次对话的参考:

                <Reference>
                [[document]]
                [[files]]
                </Reference>
                
                回答要求:
                    - 如果你不清楚答案，你需要澄清。
                    - 避免提及您是从 <Reference></Reference> 获取的知识。
                    - 保持答案与 <Reference></Reference> 中描述的一致。 
                    - 使用 Markdown 语法优化回答格式。
                    - 使用与问题相同的语言回答。
                    
                问题: [[question]]";
            return str_replace('                ', '', $template);
        }
        if ($type == 'question') {
            $template = "
                回答要求:
                    - 使用 Markdown 语法优化回答格式。
                    - 使用与问题相同的语言回答。
                    
                问题: [[question]]";
            return str_replace('                ', '', $template);
        }
        return "";
    }
}