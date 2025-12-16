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
                ShanjianVideoTask::where('video_setting_id', $id)->select()->delete();
            } else {
                ShanjianVideoSetting::whereIn('id', $id)->select()->delete();
                ShanjianVideoTask::whereIn('video_setting_id', $id)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
