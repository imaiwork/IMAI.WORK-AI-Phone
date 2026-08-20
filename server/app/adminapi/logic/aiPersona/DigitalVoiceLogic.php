<?php

namespace app\adminapi\logic\aiPersona;

use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\human\HumanVoice;
use Exception;
use think\facade\Db;

class DigitalVoiceLogic extends BaseLogic
{
    /**
     * 新增AI人设关联形象
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            $persona = AiPersona::where(['id' => $params['persona_id']])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('IP人设不存在或无操作权限');
            }
            $userId = $persona['user_id'];
            if (empty($params['voice_ids'])) {
                throw new Exception('请选择音色');
            }

            $voiceIds = is_array($params['voice_ids']) ? $params['voice_ids'] : [$params['voice_ids']];
            $ids      = [];
            foreach ($voiceIds as $voiceId) {
                // 检查数字人音色是否存在
                $voice = AiPersonaDigitalVoice::where([
                                                          'user_id'     => $userId,
                                                          'persona_id'  => $params['persona_id'],
                                                          'voice_id'    => $voiceId,
                                                          'delete_time' => null
                                                      ])->findOrEmpty();
                if (!$voice->isEmpty()) {
                    continue;
                }
                //音色库
                $humanVoice = HumanVoice::where(['id' => $voiceId, 'user_id' => $userId])->where('status', 1)->findOrEmpty();
                if ($humanVoice->isEmpty()) {
                    continue;
                }

                $personaDigitalVoiceData = [
                    'user_id'           => $userId,
                    'persona_id'        => $params['persona_id'],
                    'voice_id'          => $voiceId,
                    'voice_name'        => $humanVoice['name'] ?? '',
                    'provider'          => AiPersonaDigitalVoice::resolveProviderFromHumanVoice($humanVoice),
                    'preview_audio_url' => $humanVoice['voice_urls'],
                    'third_voice_id'    => $humanVoice['voice_id'],
                    'is_original_voice' => 0,
                    'create_time'       => time(),
                    'update_time'       => time()
                ];
                
                $ids[] = AiPersonaDigitalVoice::create($personaDigitalVoiceData)->id;
            }

            Db::commit();
            // 新增成功，返回新增的ID
            self::$returnData = ['id' => $ids];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除AI人设关联形象
     * @param array $ids
     * @return bool
     */
    public static function delete(array $ids): bool
    {
        Db::startTrans();
        try {
            $delIds = [];
            foreach ($ids as $id) {
                $persona = AiPersonaDigitalVoice::where(['id' => $id])->findOrEmpty();
                if ($persona->isEmpty()) {
                    continue;
                }
                AiPersonaDigitalVoice::destroy($id);
                $delIds[] = $id;
            }
            Db::commit();
            self::$returnData = ['id' => $delIds];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }
}
