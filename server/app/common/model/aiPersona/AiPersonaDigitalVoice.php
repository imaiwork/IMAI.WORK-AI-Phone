<?php
namespace app\common\model\aiPersona;


use app\common\model\BaseModel;
use app\common\model\human\HumanVoice;
use think\model\concern\SoftDelete;

/**
 * @property int $id 主键ID
 * @property int $user_id 用户ID
 * ...其他字段
 */
class AiPersonaDigitalVoice extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    /** 闪剪音色 */
    public const PROVIDER_SHANJIAN = 'shanjian';
    /** MiniMax 音色 */
    public const PROVIDER_MINIMAX = 'minimax';

    /** MiniMax human_voice.model_version：10=hd，11=turbo */
    public const MINIMAX_MODEL_VERSIONS = [10, 11];

    /**
     * 可用于视频合成的人设音色：人设绑定记录和来源音色都必须有效。
     */
    public static function availableQuery(string $alias = 'ad')
    {
        return self::alias($alias)
            ->leftJoin('human_voice hv', "{$alias}.voice_id = hv.id")
            ->leftJoin('shanjian_anchor sa', "{$alias}.third_voice_id = sa.voice_id AND {$alias}.user_id = sa.user_id")
            ->whereNull("{$alias}.delete_time")
            ->where("{$alias}.third_voice_id", '<>', '')
            ->whereRaw("(({$alias}.voice_id > 0 AND hv.delete_time IS NULL AND hv.status = 1) OR ({$alias}.voice_id = 0 AND sa.delete_time IS NULL AND sa.status = 6))");
    }

    /**
     * 列表展示口径：绑定记录本身没被删就显示。
     * 上游音色被会员到期冻结（status=9）时 availableQuery 会把整行滤掉，
     * 界面上看起来像「音色自己没了」，可用户并没有删过，也无从知道为什么。
     * 展示走这条（不 join，避免同一个 voice_id 对多条闪剪形象时列重复），
     * 能不能参与合成交给 markAvailability 标注。
     */
    public static function listQuery(string $alias = 'ad')
    {
        return self::alias($alias)->whereNull("{$alias}.delete_time");
    }

    /**
     * 给展示行标上 available / unavailable_reason。
     * 可用集合直接取 availableQuery，判定只有那一处，不会跟合成侧走岔；
     * 不可用的再分「会员到期冻结」与「已失效」，两者用户能做的事不一样。
     */
    public static function markAvailability(array $list, int $userId): array
    {
        if (empty($list)) {
            return $list;
        }
        // 音色库那份看 human_voice，形象原音那份看 shanjian_anchor，两个实体各有自己的冻结值
        $frozenVoice = (int)(\app\common\service\MemberService::ENTITIES['voice']['frozen_value'] ?? 9);
        $frozenAnchor = (int)(\app\common\service\MemberService::ENTITIES['digital_human_shanjian']['frozen_value'] ?? 9);
        $ids = array_filter(array_column($list, 'id'));
        $availableIds = $ids
            ? array_flip(self::availableQuery()->where('ad.user_id', $userId)->whereIn('ad.id', $ids)->column('ad.id'))
            : [];

        $pendingLibIds = [];
        $pendingClipVoiceIds = [];
        foreach ($list as $item) {
            if (isset($availableIds[$item['id'] ?? 0])) {
                continue;
            }
            if ((int)($item['voice_id'] ?? 0) > 0) {
                $pendingLibIds[] = (int)$item['voice_id'];
            } else {
                $pendingClipVoiceIds[] = (string)($item['third_voice_id'] ?? '');
            }
        }
        $pendingLibIds = array_values(array_unique($pendingLibIds));
        $pendingClipVoiceIds = array_values(array_filter(array_unique($pendingClipVoiceIds)));
        $frozenLibIds = $pendingLibIds
            ? array_flip(HumanVoice::whereIn('id', $pendingLibIds)->where('status', $frozenVoice)->column('id'))
            : [];
        $frozenClipVoiceIds = $pendingClipVoiceIds
            ? array_flip(\app\common\model\shanjian\ShanjianAnchor::whereIn('voice_id', $pendingClipVoiceIds)
                ->where('user_id', $userId)
                ->where('status', $frozenAnchor)
                ->column('voice_id'))
            : [];

        foreach ($list as &$item) {
            if (isset($availableIds[$item['id'] ?? 0])) {
                $item['available'] = true;
                $item['unavailable_reason'] = '';
                continue;
            }
            $isFrozen = (int)($item['voice_id'] ?? 0) > 0
                ? isset($frozenLibIds[(int)$item['voice_id']])
                : isset($frozenClipVoiceIds[(string)($item['third_voice_id'] ?? '')]);
            $item['available'] = false;
            $item['unavailable_reason'] = $isFrozen
                ? '会员权益到期已冻结，恢复会员后可继续使用'
                : '音色已失效，合成时会跳过';
        }
        unset($item);

        return $list;
    }

    /**
     * 视频合成可选的音色 provider（闪剪 + MiniMax）
     * @return string[]
     */
    public static function synthesisProviders(): array
    {
        return [self::PROVIDER_SHANJIAN, self::PROVIDER_MINIMAX];
    }

    /**
     * 根据音色库记录解析绑定 provider：MiniMax(10/11)=minimax，其余默认 shanjian
     */
    public static function resolveProviderFromHumanVoice($humanVoice): string
    {
        $modelVersion = 0;
        if ($humanVoice instanceof HumanVoice) {
            $modelVersion = (int)($humanVoice->getAttr('model_version') ?? 0);
        } elseif (is_array($humanVoice)) {
            $modelVersion = (int)($humanVoice['model_version'] ?? 0);
        } elseif (is_object($humanVoice)) {
            $modelVersion = (int)($humanVoice->model_version ?? 0);
        }

        if (in_array($modelVersion, self::MINIMAX_MODEL_VERSIONS, true)) {
            return self::PROVIDER_MINIMAX;
        }
        return self::PROVIDER_SHANJIAN;
    }

    /**
     * 关联人设主表
     */
    public function persona()
    {
        return $this->belongsTo(\app\common\model\aiPersona\AiPersona::class, 'persona_id', 'id');
    }

    /**
     * 关联音色列表
     */
    public function humanVoice()
    {
        return $this->belongsTo(\app\common\model\human\HumanVoice::class, 'voice_id', 'id');
    }
}
