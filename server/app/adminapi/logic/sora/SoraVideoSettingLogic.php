<?php

namespace app\adminapi\logic\sora;

use app\common\logic\BaseLogic;
use app\common\model\sora\SoraVideoSetting;
use app\common\model\sora\SoraVideoTask;

class SoraVideoSettingLogic extends BaseLogic
{
    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                SoraVideoSetting::destroy(['id' => $id]);
                SoraVideoTask::where('video_setting_id', $id)->select()->delete();
            } else {
                SoraVideoSetting::whereIn('id', $id)->select()->delete();
                SoraVideoTask::whereIn('video_setting_id', $id)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
