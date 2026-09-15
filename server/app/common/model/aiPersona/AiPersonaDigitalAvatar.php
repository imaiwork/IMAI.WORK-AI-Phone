<?php
namespace app\common\model\aiPersona;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaDigitalAvatar extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    /**
     * 可用于视频合成的人设形象：人设绑定记录、公共形象、闪剪形象都必须有效。
     */
    public static function availableQuery(string $alias = 'ad')
    {
        return self::alias($alias)
            ->join('digital_human_anchor dha', "{$alias}.dh_id = dha.id")
            ->join('shanjian_anchor sa', "{$alias}.dh_id = sa.dh_id")
            ->leftJoin('human_voice hv', "{$alias}.third_voice_id = hv.voice_id AND {$alias}.user_id = hv.user_id")
            ->whereNull("{$alias}.delete_time")
            ->whereNull('dha.delete_time')
            ->where('dha.status', '=', 2)
            ->whereNull('sa.delete_time')
            ->where('sa.status', '=', 6)
            ->where("{$alias}.third_avatar_id", '<>', '')
            ->where("{$alias}.third_voice_id", '<>', '')
            ->whereRaw("(({$alias}.is_original_voice = 1 AND sa.voice_id = {$alias}.third_voice_id) OR ({$alias}.is_original_voice = 0 AND hv.delete_time IS NULL AND hv.status = 1))");
    }

    /**
     * 列表展示口径：绑定记录本身没被删就显示。
     * 上游形象被会员到期冻结（status=9）时 availableQuery 会把整行滤掉，
     * 界面上看起来像「数字人自己没了」，可用户并没有删过，也无从知道为什么。
     * 展示走这条（不 join，避免一个 dh_id 对多条闪剪形象时把同一个形象列重复），
     * 能不能参与合成交给 markAvailability 标注。
     */
    public static function listQuery(string $alias = 'ad')
    {
        return self::alias($alias)->whereNull("{$alias}.delete_time");
    }

    /**
     * 给展示行标上 available / anchor_available / unavailable_reason。
     * 可用集合直接取 availableQuery，判定只有那一处，不会跟合成侧走岔。
     *
     * anchor_available 单独给出来，是因为「形象本身被冻结」和「只是绑的音色坏了」
     * 对用户是两件事：后者换一个可用音色就能救回来，前者换什么都没用，
     * 界面据此决定还要不要给「绑定声音」这个入口。
     */
    public static function markAvailability(array $list, int $userId): array
    {
        if (empty($list)) {
            return $list;
        }
        // 会员到期冻结的是闪剪形象与音色两张表，形象侧对应 digital_human_shanjian 实体
        $frozenAnchor = (int)(\app\common\service\MemberService::ENTITIES['digital_human_shanjian']['frozen_value'] ?? 9);
        $frozenVoice = (int)(\app\common\service\MemberService::ENTITIES['voice']['frozen_value'] ?? 9);
        $ids = array_filter(array_column($list, 'id'));
        $availableIds = $ids
            ? array_flip(self::availableQuery()->where('ad.user_id', $userId)->whereIn('ad.id', $ids)->column('ad.id'))
            : [];

        $okDhIds = [];
        $frozenDhIds = [];
        $frozenVoiceIds = [];
        $pendingDhIds = [];
        $pendingVoiceIds = [];
        foreach ($list as $item) {
            if (isset($availableIds[$item['id'] ?? 0])) {
                continue;
            }
            $pendingDhIds[] = (int)($item['dh_id'] ?? 0);
            if ((int)($item['is_original_voice'] ?? 0) === 0) {
                $pendingVoiceIds[] = (string)($item['third_voice_id'] ?? '');
            }
        }
        $pendingDhIds = array_values(array_filter(array_unique($pendingDhIds)));
        $pendingVoiceIds = array_values(array_filter(array_unique($pendingVoiceIds)));
        if ($pendingDhIds) {
            // 口径与 availableQuery 的 sa 条件一致：status=6 且未删才算形象本身可用
            $okDhIds = array_flip(\app\common\model\shanjian\ShanjianAnchor::whereIn('dh_id', $pendingDhIds)
                ->where('status', 6)
                ->whereNull('delete_time')
                ->column('dh_id'));
            $frozenDhIds = array_flip(\app\common\model\shanjian\ShanjianAnchor::whereIn('dh_id', $pendingDhIds)
                ->where('status', $frozenAnchor)
                ->column('dh_id'));
        }
        if ($pendingVoiceIds) {
            $frozenVoiceIds = array_flip(\app\common\model\human\HumanVoice::whereIn('voice_id', $pendingVoiceIds)
                ->where('user_id', $userId)
                ->where('status', $frozenVoice)
                ->column('voice_id'));
        }

        foreach ($list as &$item) {
            if (isset($availableIds[$item['id'] ?? 0])) {
                $item['available'] = true;
                $item['anchor_available'] = true;
                $item['unavailable_reason'] = '';
                continue;
            }
            $dhId = (int)($item['dh_id'] ?? 0);
            $anchorOk = isset($okDhIds[$dhId]);
            $voiceFrozen = (int)($item['is_original_voice'] ?? 0) === 0
                && isset($frozenVoiceIds[(string)($item['third_voice_id'] ?? '')]);
            $item['available'] = false;
            $item['anchor_available'] = $anchorOk;
            if (!$anchorOk) {
                $item['unavailable_reason'] = isset($frozenDhIds[$dhId])
                    ? '形象因会员权益到期已冻结，恢复会员后可继续使用'
                    : '形象已失效，合成时会跳过';
            } else {
                $item['unavailable_reason'] = $voiceFrozen
                    ? '绑定的音色因会员权益到期已冻结，换一个可用音色即可继续使用'
                    : '绑定的音色已失效，换一个可用音色即可继续使用';
            }
        }
        unset($item);

        return $list;
    }

    /**
     * 关联人设主表
     */
    public function persona()
    {
        return $this->belongsTo(\app\common\model\aiPersona\AiPersona::class, 'persona_id', 'id');
    }

    /**
     * 关联公共数字人形象
     */
    public function humanAnchor()
    {
        return $this->belongsTo(\app\common\model\digitalHuman\DigitalHumanAnchor::class, 'dh_id', 'id');
    }
}
