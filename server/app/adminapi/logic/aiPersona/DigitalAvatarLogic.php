<?php

namespace app\adminapi\logic\aiPersona;

use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\human\HumanVoice;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\service\FileService;
use Exception;
use think\facade\Db;

class DigitalAvatarLogic extends BaseLogic
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
            $userId = $persona['user_id'];
            if ($persona->isEmpty()) {
                throw new Exception('IP人设不存在或无操作权限');
            }
            if (empty($params['dh_ids'])) {
                throw new Exception('请选择数字人');
            }
            $dhIds = is_array($params['dh_ids']) ? $params['dh_ids'] : [$params['dh_ids']];
            $ids   = [];
            foreach ($dhIds as $dhId) {
                $personaDigital = AiPersonaDigitalAvatar::where(['user_id' => $userId, 'dh_id' => $dhId, 'persona_id' => $params['persona_id'], 'delete_time' => null])
                                                        ->findOrEmpty();
                if (!$personaDigital->isEmpty()) {
                    continue;
                }
                $avatar = DigitalHumanAnchor::where(['id' => $dhId, 'user_id' => $userId])->findOrEmpty();
                if ($avatar->isEmpty()) {
                    continue;
                }

                $shanjian = ShanjianAnchor::where('dh_id', $avatar['id'])->findOrEmpty();
                if ($shanjian->isEmpty()) {
                    continue;
                }
                // 检查数字人音色是否存在
                $voice = AiPersonaDigitalVoice::where(['user_id' => $userId, 'persona_id' => $params['persona_id'], 'third_voice_id' => $shanjian['voice_id'], 'delete_time' => null])->findOrEmpty();
                if ($voice->isEmpty()) {
                    // 1. 新增音色关联表
                    $personaDigitalVoiceData = [
                        'user_id'           => $userId,
                        'persona_id'        => $params['persona_id'],
                        'voice_id'          => 0,
                        'voice_name'        => $avatar['name'],
                        'provider'          => 'shanjian',
                        'preview_audio_url' => $avatar['result_url'] ? FileService::setFileUrl($avatar['result_url']) : '',
                        'third_voice_id'    => $shanjian['voice_id'] ?? '',
                        'is_original_voice' => 1,
                        'create_time'       => time(),
                        'update_time'       => time()
                    ];
                    $voiceId                 = AiPersonaDigitalVoice::create($personaDigitalVoiceData)->id;
                } else {
                    $voiceId = $voice['id'];
                }
                // 2. 新增主表
                $personaDigitalAvatarData = [
                    'user_id'                  => $userId,
                    'persona_id'               => $params['persona_id'],
                    'dh_id'                    => $dhId,
                    'avatar_name'              => $avatar['name'],
                    'cover_url'                => $avatar['image'] ? FileService::setFileUrl($avatar['image']) : '',
                    'video_url'                => $avatar['result_url'] ? FileService::setFileUrl($avatar['result_url']) : '',
                    'width'                    => $avatar['width'] ?? 0,
                    'height'                   => $avatar['height'] ?? 0,
                    'third_avatar_id'          => $shanjian['anchor_id'] ?? '',
                    'persona_digital_voice_id' => $voiceId,
                    'is_original_voice'        => 1,
                    'create_time'              => time(),
                    'update_time'              => time()
                ];
                $ids[]                    = AiPersonaDigitalAvatar::create($personaDigitalAvatarData)->id;
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
                $persona = AiPersonaDigitalAvatar::where(['id' => $id])->findOrEmpty();
                if ($persona->isEmpty()) {
                    continue;
                }
                AiPersonaDigitalAvatar::destroy($id);
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

    /**
     * 人设形象绑定AI人设下的音色
     * @param array $params
     * @param int $userId
     * @return bool
     */
    public static function bindPersonaVoice(array $params): bool
    {
        Db::startTrans();
        try {
            $personaAvatar = AiPersonaDigitalAvatar::where(['id' => $params['persona_avatar_id']])->findOrEmpty();
            if ($personaAvatar->isEmpty()) {
                throw new Exception('IP人设关联形象不存在或无操作权限');
            }
            $userId = $personaAvatar['user_id'];
            if ($params['is_original_voice'] == 0 && $params['voice_id'] > 0){
                $humanVoice = HumanVoice::where(['id' => $params['voice_id'], 'user_id' => $userId, 'model_version' => 8, 'status' => 1])->findOrEmpty();
                if ($humanVoice->isEmpty()) {
                    throw new Exception('音色状态异常');
                }
                $personaAvatar->third_voice_id = $humanVoice['voice_id'];
                $personaAvatar->is_original_voice = 0;
                $personaAvatar->voice_name = $humanVoice['name'];
                $personaAvatar->voice_url = $humanVoice['voice_urls'];
            }else{
                $shanjian = ShanjianAnchor::where('dh_id', $personaAvatar->dh_id)->where('status',6)->findOrEmpty();
                if ($shanjian->isEmpty()){
                    throw new Exception('闪剪形象状态异常');
                }
                $personaAvatar->third_voice_id = $shanjian['voice_id'];
                $personaAvatar->is_original_voice = 1;
                $personaAvatar->voice_name = $shanjian['name'];
                $personaAvatar->voice_url = $shanjian['voice_url'];
            }

            $personaAvatar->save();
            Db::commit();
            self::$returnData = ['persona_avatar_id' => $personaAvatar->id];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }
}