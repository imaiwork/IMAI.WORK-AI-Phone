<?php

namespace app\common\service\digitalHuman;

use app\common\model\digitalHuman\DigitalHumanAnchor;

/**
 * 公共形象克隆任务排队查询：已提交(digital_human_anchor.status=0)但尚未分发到
 * 渠道表(human_anchor/shanjian_anchor)的任务。
 * 用于后台形象列表在视频转码/分发完成前，就能看到用户刚提交的克隆任务。
 */
class PendingAnchorTaskService
{
    /** 虚拟行统一备注 */
    public const REMARK = '排队中：等待视频转码后分发训练';

    /**
     * @param string $channel human=蝉镜(human_anchor,model_version=7)；shanjian=闪剪(shanjian_anchor)
     */
    public static function pendingQuery(string $channel)
    {
        $query = DigitalHumanAnchor::alias('dh')
            ->join('user u', 'u.id = dh.user_id')
            ->where('dh.status', 0);

        if ($channel === 'shanjian') {
            // ai_type=1(AI授权)只走蝉镜、不建闪剪子任务，不属于闪剪渠道
            $query->where('dh.ai_type', 0)
                ->whereNotExists(function ($sub) {
                    $sub->name('shanjian_anchor')
                        ->whereColumn('dh_id', 'dh.id')
                        ->whereNull('delete_time');
                });
        } else {
            $query->whereNotExists(function ($sub) {
                $sub->name('human_anchor')
                    ->whereColumn('dh_id', 'dh.id')
                    ->where('model_version', 7)
                    ->whereNull('delete_time');
            });
        }

        return $query->field('dh.id,dh.user_id,dh.name,dh.image,dh.result_url,dh.authorized_pic,dh.authorized_url,dh.clone_mode,dh.create_time,dh.update_time,u.nickname,u.avatar');
    }
}
