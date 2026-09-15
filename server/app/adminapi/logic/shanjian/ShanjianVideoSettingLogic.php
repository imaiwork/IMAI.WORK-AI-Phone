<?php

namespace app\adminapi\logic\shanjian;

use app\common\logic\BaseLogic;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;

class ShanjianVideoSettingLogic extends BaseLogic
{
    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                ShanjianVideoSetting::destroy(['id' => $id]);
                // 派生包装任务挂在自己的 setting 下，按 setting 删除时须显式级联，避免孤儿
                $type5Ids = ShanjianVideoTask::where('video_setting_id', $id)
                    ->where('shanjian_type', 5)
                    ->column('id');
                ShanjianVideoTask::deleteDerivedPackaging($type5Ids);
                ShanjianVideoTask::where('video_setting_id', $id)->select()->delete();
            } else {
                ShanjianVideoSetting::whereIn('id', $id)->select()->delete();
                $type5Ids = ShanjianVideoTask::whereIn('video_setting_id', $id)
                    ->where('shanjian_type', 5)
                    ->column('id');
                ShanjianVideoTask::deleteDerivedPackaging($type5Ids);
                ShanjianVideoTask::whereIn('video_setting_id', $id)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
