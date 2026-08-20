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
