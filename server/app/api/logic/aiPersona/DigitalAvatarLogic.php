<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\human\HumanVoice;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\service\FileService;
use Exception;
use think\facade\Db;

class DigitalAvatarLogic extends ApiLogic
{
    /**
     * 新增AI人设关联形象
     * @param array $params
     * @param int $userId
     * @return bool
     */
    public static function add(array $params, int $userId): bool
    {
        Db::startTrans();
        try {
            $persona = AiPersona::where(['id' => $params['persona_id'], 'user_id' => $userId, 'delete_time' => null])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('IP人设不存在或无操作权限');
            }
            if (empty($params['dh_ids'])) {
                throw new Exception('请选择数字人');
            }
            $dhIds = is_array($params['dh_ids']) ? $params['dh_ids'] : [$params['dh_ids']];
            $ids   = [];
            // 选择弹窗按 digital_human_anchor.status 列形象，而入库要过 shanjian_anchor.status=6。
            // 两个口径不一致时（最常见是会员到期把闪剪形象冻结成 9），原来是静默 continue，
            // 接口照样回「创建成功」，用户看到的就是「加了没反应」。记下原因，一条都没加成时报出去。
            $skipped = [];
            foreach ($dhIds as $dhId) {
                $personaDigital = AiPersonaDigitalAvatar::where(['user_id' => $userId, 'dh_id' => $dhId, 'persona_id' => $params['persona_id'], 'delete_time' => null])
                                                        ->findOrEmpty();
                if (!$personaDigital->isEmpty()) {
                    $skipped[] = '「' . ($personaDigital['avatar_name'] ?: $dhId) . '」已经在该人设里了';
                    continue;
                }
                $avatar = DigitalHumanAnchor::where(['id' => $dhId, 'user_id' => $userId])->where('status',2)->findOrEmpty();
                if ($avatar->isEmpty()) {
                    $skipped[] = self::unavailableAnchorReason((int)$dhId, $userId);
                    continue;
                }

                $shanjian = ShanjianAnchor::where('dh_id', $avatar['id'])->where('status',6)->findOrEmpty();
                if ($shanjian->isEmpty()) {
                    $skipped[] = self::unavailableAnchorReason((int)$dhId, $userId, (string)$avatar['name']);
                    continue;
                }

                $personaDigitalAvatarData = [
                    'user_id'           => $userId,
                    'persona_id'        => $params['persona_id'],
                    'dh_id'             => $dhId,
                    'avatar_name'       => $avatar['name'],
                    'cover_url'         => $avatar['image'] ? FileService::setFileUrl($avatar['image']) : '',
                    'video_url'         => $avatar['result_url'] ? FileService::setFileUrl($avatar['result_url']) : '',
                    'width'             => $avatar['width'] ?? 0,
                    'height'            => $avatar['height'] ?? 0,
                    'third_avatar_id'   => $shanjian['anchor_id'] ?? '',
                    'third_voice_id'    => $shanjian['voice_id'],
                    'is_original_voice' => 1,
                    'voice_name'        => $avatar['name'],
                    'voice_url'         => $shanjian['voice_url'],
                    'create_time'       => time(),
                    'update_time'       => time()
                ];
                $ids[]                    = AiPersonaDigitalAvatar::create($personaDigitalAvatarData)->id;
            }
            if (empty($ids)) {
                throw new Exception($skipped ? implode('；', array_unique($skipped)) : '所选形象当前不可用，请更换');
            }
            Db::commit();
            // 新增成功，返回新增的ID
            self::$returnData = ['id' => $ids, 'skipped' => array_values(array_unique($skipped))];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 闪剪形象为什么用不了（加入人设、绑定原音共用）：会员冻结与「还没克隆好/已删除」
     * 要分开说，前者恢复会员就能用，后者只能换一个，提示混在一起用户没法处理。
     */
    private static function unavailableAnchorReason(int $dhId, int $userId, string $name = ''): string
    {
        if ($name === '') {
            $name = (string)DigitalHumanAnchor::where(['id' => $dhId, 'user_id' => $userId])->value('name');
        }
        $label = $name !== '' ? '「' . $name . '」' : '所选形象';

        // 会员到期冻结的是 shanjian_anchor（MemberService 的 digital_human_shanjian 实体），
        // digital_human_anchor.status 不参与冻结，所以只看闪剪那张表
        $isFrozen = ShanjianAnchor::where('dh_id', $dhId)
            ->where('status', ShanjianVideoSettingLogic::memberFrozenStatus())
            ->count() > 0;
        if ($isFrozen) {
            return $label . ShanjianVideoSettingLogic::frozenAnchorTip();
        }
        return $label . '当前不可用，请确认形象已克隆完成后再添加';
    }

    /**
     * 删除AI人设关联形象
     * @param array $ids
     * @param int $userId
     * @return bool
     */
    public static function delete(array $ids, int $userId): bool
    {
        Db::startTrans();
        try {
            $delIds = [];
            foreach ($ids as $id) {
                $persona = AiPersonaDigitalAvatar::where(['id' => $id, 'user_id' => $userId])->findOrEmpty();
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
    public static function bindPersonaVoice(array $params, int $userId): bool
    {
        Db::startTrans();
        try {
            $personaAvatar = AiPersonaDigitalAvatar::where(['id' => $params['persona_avatar_id'], 'user_id' => $userId])->findOrEmpty();
            if ($personaAvatar->isEmpty()) {
                throw new Exception('IP人设关联形象不存在或无操作权限');
            }
            if ($params['is_original_voice'] == 0 && $params['voice_id'] > 0){
                // 闪剪(V3=8 / S3=15) + MiniMax(10=hd / 11=turbo)
                $allowedVersions = array_merge(HumanVoice::SHANJIAN_VOICE_MODEL_VERSIONS, AiPersonaDigitalVoice::MINIMAX_MODEL_VERSIONS);
                $humanVoice = HumanVoice::where([
                    'id'      => $params['voice_id'],
                    'user_id' => $userId,
                    'status'  => 1,
                ])->whereIn('model_version', $allowedVersions)->findOrEmpty();
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
                    // 形象被会员到期冻结时也会落到这里。一句「壹传媒形象状态异常」既没说是哪个形象，
                    // 也没说该去做什么，用户只能反复点；换成按真实原因给的文案
                    throw new Exception(self::unavailableAnchorReason(
                        (int)$personaAvatar->dh_id,
                        $userId,
                        (string)$personaAvatar->avatar_name
                    ));
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
