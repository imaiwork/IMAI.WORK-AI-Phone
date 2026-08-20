<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvVideoSetting;
use app\common\model\sv\SvVideoTask;
use think\facade\Db;

/**
 * SvVideoSettingLogic
 * @desc 视频设置逻辑处理
 */
class SvVideoSettingLogic extends SvBaseLogic
{
    private const DISABLED_MESSAGE = '矩阵数字人视频合成功能已下线，请使用闪剪数字人口播链路';

    /**
     * @desc 添加视频设置
     * @param array $params
     * @return bool
     */
    public static function addSvVideoSetting(array $params)
    {
        self::setError(self::DISABLED_MESSAGE);
        return false;
    }

    /**
     * @desc 获取视频设置详情
     * @param array $params
     * @return bool
     */
    public static function detailSvVideoSetting(array $params)
    {
        try {
            $setting = SvVideoSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }

            $jsonFields = ['anchor', 'voice', 'copywriting'];
            foreach ($jsonFields as $field) {
                if (!empty($setting[$field])) {
                    $setting[$field] = json_decode($setting[$field], true);
                } else {
                    $setting[$field] = [];
                }
            }

            self::$returnData = $setting;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新视频设置
     * @param array $params
     * @return bool
     */
    public static function updateSvVideoSetting(array $params)
    {
        try {
            $setting = SvVideoSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($setting->isEmpty()) {
                self::setError('视频设置不存在');
                return false;
            }

            if ((int)$setting->status !== 0) {
                unset($params['id'], $params['user_id'], $params['task_id'], $params['status']);
                SvVideoSetting::where('id', $setting->id)->update($params);
                self::$returnData = SvVideoSetting::find($setting->id)->toArray();
                return true;
            }

            $status = isset($params['status']) ? (int)$params['status'] : (int)$setting->status;
            if ($status === 1) {
                self::setError(self::DISABLED_MESSAGE);
                return false;
            }
            if (!in_array($status, [0, 2, 3, 4, 5], true)) {
                self::setError('非法操作：status参数不正确');
                return false;
            }

            unset($params['id'], $params['user_id'], $params['task_id']);
            if (isset($params['status'])) {
                $params['status'] = $status;
            }

            SvVideoSetting::where('id', $setting->id)->update($params);
            self::$returnData = SvVideoSetting::find($setting->id)->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除视频设置
     * @param array $params
     * @return bool
     */
    public static function deleteSvVideoSetting(array $params)
    {
        try {
            $setting = SvVideoSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }

            Db::startTrans();
            try {
                SvVideoTask::where('video_setting_id', $params['id'])->select()->delete();
                SvVideoSetting::destroy($params['id']);
                Db::commit();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function check()
    {
        try {
            SvVideoSetting::whereIn('status', [1, 2])
                ->where('create_time', '<=', strtotime('-1440 minutes'))
                ->select()
                ->each(function ($item) {
                    if ($item->success_num > 0) {
                        $update['error_num'] = $item->video_count - $item->success_num;
                        $update['status'] = 5;
                    } else {
                        $update['error_num'] = $item->video_count;
                        $update['status'] = 4;
                    }

                    SvVideoSetting::where('id', $item->id)->update($update);
                });

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
