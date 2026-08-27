<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDevice extends BaseModel
{
    /**
     * 合成场景：m=社媒 w=微信朋友圈
     */
    const SYNTHESIS_SCENE_SOCIAL = 'm';
    const SYNTHESIS_SCENE_WECHAT = 'w';

    /**
     * 标记设备当天视频合成完成（只设置属性，不落库，调用方负责 save()）
     * 布尔锁与完成日期必须一起写：布尔锁供旧逻辑与白天 reset_video_synthesis 使用，
     * 日期字段(synthesis_m_date/synthesis_w_date)是日期化改造后的判断依据，
     * 漏写日期会导致设备次日被重复挑选生成、重复扣算力
     */
    public function markSynthesisDone(string $scene): void
    {
        $field = $scene === self::SYNTHESIS_SCENE_WECHAT ? 'synthesis_w' : 'synthesis_m';
        $this->{$field} = 1;
        $this->{$field . '_date'} = date('Y-m-d');
    }

    /**
     * 按条件批量标记合成完成（query 形态写入点的统一入口，语义同 markSynthesisDone）
     * @param array $where 查询条件，如 ['device_code' => 'xxx']
     */
    public static function markSynthesisDoneWhere(array $where, string $scene): void
    {
        $field = $scene === self::SYNTHESIS_SCENE_WECHAT ? 'synthesis_w' : 'synthesis_m';
        self::where($where)->update([
            $field => 1,
            $field . '_date' => date('Y-m-d'),
        ]);
    }

    /**
     * 待合成的日期条件（IS NULL 或早于今天视为今天未完成，替代每日重置解锁）
     * 过渡期与旧布尔锁条件取交集使用，返回可拼入 whereRaw 的 SQL 片段
     *
     * 紧急回滚开关：.env 中设置 VIDEO_SYNTHESIS_DATE_MODE = false 时返回恒真条件，
     * 挑选逻辑即刻退回纯布尔锁模式（前提：reset_video_synthesis 定时任务仍在运行）。
     * 详见 docs/24h视频生成日期化改造回滚预案.md
     */
    public static function synthesisPendingDateSql(string $scene, string $alias = ''): string
    {
        if (!env('VIDEO_SYNTHESIS_DATE_MODE', true)) {
            return '1=1';
        }
        $field = ($scene === self::SYNTHESIS_SCENE_WECHAT ? 'synthesis_w' : 'synthesis_m') . '_date';
        $column = ($alias !== '' ? $alias . '.' : '') . $field;
        return "({$column} IS NULL OR {$column} < CURDATE())";
    }
}
