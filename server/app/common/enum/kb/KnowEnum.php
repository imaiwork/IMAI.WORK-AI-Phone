<?php


namespace app\common\enum\kb;

class KnowEnum
{
    // 学习状态
    const RUN_WAIT = 0; // 等待学习
    const RUN_ING  = 1; // 学习中
    const RUN_OK   = 2; // 学习成功
    const RUN_FAIL = 3; // 学习失败

    // 拆分状态
    const QA_WAIT = 0; // 等待拆分
    const QA_ING  = 1; // 拆分中
    const QA_OK   = 2; // 拆分完成
    const QA_FAIL = 3; // 拆分失败

    // 权限类型
    const POWER_ALL  = 1; // 全部权限
    const POWER_EDIT = 2; // 编辑权限
    const POWER_VIEW = 3; // 查看权限

    // 身份类型
    const OWNED_SUPER  = 1; // 拥有者
    const OWNED_MEMBER = 2; // 成员


    /**
     * @notes 获取学习状态描述
     * @param bool|int $from
     * @return array|string
     * @alias fzr
     */
    public static function getRunStatusDesc(bool|int $from = true): array|string
    {
        $desc = [
            self::RUN_WAIT  => '等待学习',
            self::RUN_ING   => '学习中',
            self::RUN_OK    => '学习成功',
            self::RUN_FAIL  => '学习失败',
        ];
        if (true === $from) {
            return $desc;
        }
        return $desc[$from] ?? '';
    }

    /**
     * @notes 获取学习状态描述
     * @param bool|int $from
     * @return array|string
     * @alias fzr
     */
    public static function getQaStatusDesc(bool|int $from = true): array|string
    {
        $desc = [
            self::QA_WAIT  => '等待拆分',
            self::QA_ING   => '拆分中',
            self::QA_OK    => '拆分完成',
            self::QA_FAIL  => '拆分失败',
        ];
        if (true === $from) {
            return $desc;
        }
        return $desc[$from] ?? '';
    }
}