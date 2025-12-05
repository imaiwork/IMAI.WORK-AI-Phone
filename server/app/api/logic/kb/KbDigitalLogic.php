<?php


namespace app\api\logic\kb;

use app\common\enum\VoiceEnum;
use app\common\logic\BaseLogic;
use app\common\model\kb\KbDigital;
use app\common\service\ConfigService;
use app\common\service\FileService;
use Exception;

class KbDigitalLogic extends BaseLogic
{
    /**
     * @notes 发音角色列表
     * @return array|string
     * @author kb
     */
    public static function dubbing(): array|string
    {
        $channel = ConfigService::get('voice_output','channel', 'kdxf');

        $lists = VoiceEnum::getOpenAiPronounceList();
        if ($channel === VoiceEnum::KDXF) {
            $lists = VoiceEnum::getKdxfPronounceList();
        }
        if ($channel === VoiceEnum::DOUBAO) {
            $lists = VoiceEnum::getDoubaoPronounceList();
        }

        $data = [];
        foreach ($lists as $key => $value) {
            $data[$key] = [
                'name'    => $value,
                'example' => FileService::getFileUrl('/resource/voice/'.$key.'.mp3')
            ];
        }

        return $data;
    }

    /**
     * @notes 数字人详情
     * @param int $id
     * @return array
     * @author kb
     */
    public static function detail(int $id): array
    {
        $detail = (new KbDigital())
            ->withoutField('user_id,delete_time')
            ->where(['id'=>$id])
            ->findOrEmpty()
            ->toArray();

        if (!$detail) {
            return [];
        }

        return $detail;
    }

    /**
     * @notes 数字人新增
     * @param array $post
     * @param int $userId
     * @return bool|array
     * @author kb
     */
    public static function add(array $post, int $userId): bool|array
    {
        try {
            $channel = '';
            if (!empty($post['dubbing'])) {
                if (VoiceEnum::getKdxfPronounceList($post['dubbing'])) {
                    $channel = VoiceEnum::KDXF;
                } elseif (VoiceEnum::getOpenAiPronounceList($post['dubbing'])) {
                    $channel = VoiceEnum::OPENAI;
                } elseif (VoiceEnum::getDoubaoPronounceList($post['dubbing'])) {
                    $channel = VoiceEnum::DOUBAO;
                } else {
                    throw new Exception('选择的配音角色错误!');
                }

                $channelConfig = ConfigService::get('voice_output', 'channel', 'kdxf');
                if ($channel !== $channelConfig) {
                    throw new Exception('当前配音角色渠道已关闭,请重新选择!');
                }
            }

            $digital = KbDigital::create([
                'user_id'             => $userId,
                'name'                => $post['name'],
                'avatar'              => FileService::getFileUrl($post['avatar']),
                'image'               => FileService::getFileUrl($post['image']),
                'channel'             => $channel,
                'dubbing'             => $post['dubbing']??'',
                'idle_reply'          => $post['idle_reply'] ?? '',
                'wide_stay_video'     => $post['wide_stay_video'] ?? 'resource/digital/wide_stay_video.mp4',
                'wide_talk_video'     => $post['wide_talk_video'] ?? 'resource/digital/wide_talk_video.mp4',
                'vertical_stay_video' => $post['vertical_stay_video'] ?? 'resource/digital/vertical_stay_video.mp4',
                'vertical_talk_video' => $post['vertical_talk_video'] ?? 'resource/digital/vertical_talk_video.mp4'
            ]);

            return ['id'=>$digital['id']];
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 数字人编辑
     * @param array $post
     * @param int $userId
     * @return bool
     */
    public static function edit(array $post, int $userId): bool
    {
        try {
            $digital = (new KbDigital())
                ->where(['id'=>intval($post['id'])])
                ->where(['user_id'=>$userId])
                ->findOrEmpty()
                ->toArray();

            if (!$digital) {
                throw new Exception('找不到对应的数字人');
            }

            if ($digital['is_disable']) {
                throw new Exception('数字人已被禁用,禁止操作');
            }

            if (VoiceEnum::getKdxfPronounceList($post['dubbing'])) {
                $channel = VoiceEnum::KDXF;
            } elseif (VoiceEnum::getOpenAiPronounceList($post['dubbing'])) {
                $channel = VoiceEnum::OPENAI;
            } elseif (VoiceEnum::getDoubaoPronounceList($post['dubbing'])) {
                $channel = VoiceEnum::DOUBAO;
            } else {
                throw new Exception('选择的配音角色异常!');
            }

            $channelConfig = ConfigService::get('voice_output','channel', 'kdxf');
            if ($channel !== $channelConfig) {
                throw new Exception('当前配音角色渠道已关闭,请重新选择!');
            }

            KbDigital::update([
                'name'                => $post['name'],
                'avatar'              => FileService::setFileUrl($post['avatar']),
                'image'               => FileService::setFileUrl($post['image']),
                'channel'             => $channel,
                'dubbing'             => $post['dubbing'],
                'idle_time'           => $post['idle_time'],
                'idle_reply'          => $post['idle_reply'] ?? '',
                'wide_stay_video'     => FileService::setFileUrl($post['wide_stay_video']),
                'wide_talk_video'     => FileService::setFileUrl($post['wide_talk_video']),
                'vertical_stay_video' => FileService::setFileUrl($post['vertical_stay_video']),
                'vertical_talk_video' => FileService::setFileUrl($post['vertical_talk_video'])
            ], ['id'=>intval($post['id'])]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 数字人删除
     * @param int $id
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function del(int $id, int $userId): bool
    {
        try {
            $digital = (new KbDigital())
                ->where(['id'=>$id])
                ->where(['user_id'=>$userId])
                ->findOrEmpty()
                ->toArray();

            if (!$digital) {
                throw new Exception('数字人已不存在了');
            }

            KbDigital::destroy($id);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}