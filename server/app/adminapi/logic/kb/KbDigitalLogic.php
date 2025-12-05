<?php


namespace app\adminapi\logic\kb;

use app\common\enum\VoiceEnum;
use app\common\logic\BaseLogic;
use app\common\model\kb\KbDigital;
use Exception;

class KbDigitalLogic extends BaseLogic
{
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

        switch ($detail['channel']) {
            case VoiceEnum::KDXF:
                $detail['dubbing_name'] = VoiceEnum::getKdxfPronounceList($detail['dubbing']);
                break;
            case VoiceEnum::OPENAI:
                $detail['dubbing_name'] = VoiceEnum::getOpenAiPronounceList($detail['dubbing']);
                break;
            case VoiceEnum::DOUBAO:
                $detail['dubbing_name'] = VoiceEnum::getDoubaoPronounceList($detail['dubbing']);
                break;
        }

        return $detail;
    }

    /**
     * @notes 数字人删除
     * @param int $id
     * @return bool
     * @author kb
     */
    public static function del(int $id): bool
    {
        try {
            $digital = (new KbDigital())
                ->field('id')
                ->where(['id'=>$id])
                ->findOrEmpty()
                ->toArray();

            if (!$digital) {
                throw new Exception('数字人不存在了!');
            }

            KbDigital::destroy($id);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 状态修改
     * @param int $id
     * @return bool
     * @author kb
     */
    public static function changeStatus(int $id): bool
    {
        try {
            $digital = (new KbDigital())
                ->field(['id,is_disable'])
                ->where(['id'=>$id])
                ->findOrEmpty()
                ->toArray();

            if (!$digital) {
                throw new Exception('数字人不存在了!');
            }

            KbDigital::update([
                'is_disable'  => !$digital['is_disable'],
                'update_time' => time()
            ], ['id'=>$id]);

            if (!$digital['is_disable']) {
                self::setError('禁用成功');
            } else {
                self::setError('启用成功');
            }

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}