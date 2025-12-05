<?php

namespace app\adminapi\logic\sv;
use app\common\model\sv\SvVideoSetting;
use app\common\logic\BaseLogic;
/**
 * SvVideoSettingLogic
 * @desc 视频设置逻辑处理
 */
class VideoSettingLogic extends BaseLogic
{
    /**
     * @desc 获取视频设置详情
     * @param array $params
     * @return bool
     */
    public static function detail(array $params)
    {
        try {
            // 检查视频设置是否存在
            $setting = SvVideoSetting::alias("vt")
            ->field('vt.*,u.nickname,u.avatar')
            ->leftjoin('user u','u.id = vt.user_id')
            ->where('vt.id', $params['id'])->findOrEmpty();
            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }

            // 返回视频设置信息
            self::$returnData = $setting->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 删除
     * @param array $params
     * @return bool
     */
    public static function del(array $params)
    {
        try {
            if (is_string($params['id'])) {
                SvVideoSetting::destroy(['id' => $params['id']]);
            } else {
                SvVideoSetting::destroy($params['id']);
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

}