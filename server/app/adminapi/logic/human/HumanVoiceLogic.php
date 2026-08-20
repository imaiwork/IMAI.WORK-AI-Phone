<?php

namespace app\adminapi\logic\human;

use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\human\HumanVoice;
use think\facade\Db;

/**
 * 音色
 */
class HumanVoiceLogic extends BaseLogic
{


    /**
     * @notes 删除形象
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(array $data): bool
    {
        Db::startTrans();
        try {
            $ids = is_array($data['id'] ?? null) ? $data['id'] : [$data['id'] ?? 0];
            $ids = array_values(array_filter(array_map('intval', $ids)));
            if (empty($ids)) {
                throw new \Exception('请选择要删除的音色');
            }

            $thirdVoiceIds = HumanVoice::whereIn('id', $ids)
                ->where('voice_id', '<>', '')
                ->column('voice_id');
            HumanVoice::whereIn('id', $ids)->delete();
            AiPersonaDigitalVoice::whereIn('voice_id', $ids)->delete();
            if (!empty($thirdVoiceIds)) {
                AiPersonaDigitalAvatar::whereIn('third_voice_id', $thirdVoiceIds)
                    ->where('is_original_voice', 0)
                    ->delete();
            }

            Db::commit();
            return true;
        } catch (\Exception $exception) {
            Db::rollback();
            self::setError($exception->getMessage());
            return false;
        }
    }
}
