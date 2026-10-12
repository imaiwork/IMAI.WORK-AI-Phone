<?php

namespace app\common\service\digitalHuman;

use app\api\logic\shanjian\ShanjianAnchorLogic;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\human\HumanAnchor;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\service\FileService;
use think\facade\Log;

/**
 * 公共形象尚未写入渠道表时的后台列表补行。
 * 用户端列表读 digital_human_anchor；后台形象列表读 human_anchor / shanjian_anchor。
 * 转码排队(status=0)、部分完成(1)、分发前失败(3)、AI视频合成中(4) 若还没有对应渠道行，
 * 用户端能看到、后台原本看不到。这里把这些行补进对应渠道列表。
 */
class PendingAnchorTaskService
{
    /** 仍在排队、尚未分发 */
    public const REMARK = '排队中：等待视频转码后分发训练';

    /** 用户端能看到、但还没落到渠道表的公共形象状态 */
    public const VISIBLE_STATUSES = [0, 1, 3, 4];

    /**
     * 后台虚拟行 id 为负，绝对值是公共形象主键。正数返回 0。
     */
    public static function publicIdFromVirtual(int $id): int
    {
        return $id < 0 ? -$id : 0;
    }

    /**
     * 公共形象状态映射成渠道列表用的状态。
     * 蝉镜：0 生成中，2 生成失败。闪剪：1 形象合成中，2 形象合成失败。
     */
    public static function channelStatus(string $channel, int $publicStatus): int
    {
        $failed = $publicStatus === 3;
        if ($channel === 'shanjian') {
            return $failed ? 2 : 1;
        }
        return $failed ? 2 : 0;
    }

    public static function channelRemark(int $publicStatus, string $remark): string
    {
        if ($publicStatus === 3) {
            $remark = trim($remark);
            return $remark !== '' ? $remark : '形象生成失败';
        }
        if ($publicStatus === 4) {
            return 'AI视频合成中';
        }
        if ($publicStatus === 1) {
            return '部分完成，等待其余渠道';
        }
        return self::REMARK;
    }

    /**
     * @param string $channel human=蝉镜(human_anchor,model_version=7)；shanjian=闪剪(shanjian_anchor)
     */
    public static function pendingQuery(string $channel)
    {
        $query = DigitalHumanAnchor::alias('dh')
            ->join('user u', 'u.id = dh.user_id')
            ->whereIn('dh.status', self::VISIBLE_STATUSES);

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

        return $query->field('dh.id,dh.user_id,dh.name,dh.status,dh.remark,dh.image,dh.result_url,dh.authorized_pic,dh.authorized_url,dh.clone_mode,dh.create_time,dh.update_time,u.nickname,u.avatar');
    }

    /**
     * 把还没落到渠道表的公共形象写成真实渠道行，后台列表用自增主键，不再用 -公共形象id。
     */
    public static function materialize(string $channel): void
    {
        $rows = self::pendingQuery($channel)->order('dh.id', 'desc')->select()->toArray();
        foreach ($rows as $row) {
            try {
                if ($channel === 'shanjian') {
                    self::materializeShanjian($row);
                } else {
                    self::materializeHuman($row);
                }
            } catch (\Throwable $e) {
                Log::channel('digital')->write(
                    '公共形象落入渠道表失败：channel=' . $channel
                    . ' dh_id=' . ($row['id'] ?? 0)
                    . ' ' . $e->getMessage()
                );
            }
        }
    }

    private static function materializeHuman(array $row): void
    {
        $dhId   = (int)$row['id'];
        $userId = (int)$row['user_id'];
        $exists = HumanAnchor::where('dh_id', $dhId)
            ->where('user_id', $userId)
            ->where('model_version', 7)
            ->find();
        if (!empty($exists)) {
            return;
        }
        $publicStatus = (int)($row['status'] ?? 0);
        $now          = self::unixTime($row['create_time'] ?? 0);
        HumanAnchor::create([
            'user_id'       => $userId,
            'dh_id'         => $dhId,
            'name'          => mb_substr((string)($row['name'] ?? ''), 0, 50),
            'model_version' => 7,
            'status'        => self::channelStatus('human', $publicStatus),
            'task_id'       => generate_unique_task_id(),
            'anchor_id'     => '',
            'gender'        => 'male',
            'type'          => 0,
            'pic'           => FileService::setFileUrl((string)($row['image'] ?? '')),
            'url'           => FileService::setFileUrl((string)($row['result_url'] ?? '')),
            'remark'        => self::channelRemark($publicStatus, (string)($row['remark'] ?? '')),
            'create_time'   => $now,
            'update_time'   => self::unixTime($row['update_time'] ?? 0) ?: $now,
        ]);
    }

    private static function materializeShanjian(array $row): void
    {
        $dhId      = (int)$row['id'];
        $userId    = (int)$row['user_id'];
        $cloneMode = (int)($row['clone_mode'] ?? 2);
        $types     = [ShanjianAnchorLogic::CLONE_TYPE_FAST];
        if ($cloneMode === 3) {
            $types[] = ShanjianAnchorLogic::CLONE_TYPE_PRO;
        }
        $publicStatus = (int)($row['status'] ?? 0);
        $now          = self::unixTime($row['create_time'] ?? 0);
        foreach ($types as $cloneType) {
            $exists = ShanjianAnchor::where('dh_id', $dhId)
                ->where('user_id', $userId)
                ->where('clone_type', $cloneType)
                ->find();
            if (!empty($exists)) {
                continue;
            }
            ShanjianAnchor::create([
                'user_id'        => $userId,
                'dh_id'          => $dhId,
                'name'           => mb_substr((string)($row['name'] ?? ''), 0, 50),
                'status'         => self::channelStatus('shanjian', $publicStatus),
                'task_id'        => '',
                'pic'            => FileService::setFileUrl((string)($row['image'] ?? '')),
                'anchor_url'     => FileService::setFileUrl((string)($row['result_url'] ?? '')),
                'authorized_pic' => FileService::setFileUrl((string)($row['authorized_pic'] ?? '')),
                'authorized_url' => FileService::setFileUrl((string)($row['authorized_url'] ?? '')),
                'clone_type'     => $cloneType,
                'remark'         => self::channelRemark($publicStatus, (string)($row['remark'] ?? '')),
                'create_time'    => $now,
                'update_time'    => self::unixTime($row['update_time'] ?? 0) ?: $now,
            ]);
        }
    }

    private static function unixTime($value): int
    {
        if (is_numeric($value)) {
            $n = (int)$value;
            return $n > 1_000_000_000 ? $n : time();
        }
        $ts = strtotime((string)$value);
        return $ts ? $ts : time();
    }
}
