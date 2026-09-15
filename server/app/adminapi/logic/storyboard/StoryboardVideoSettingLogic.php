<?php

namespace app\adminapi\logic\storyboard;

use app\common\logic\BaseLogic;
use app\common\model\storyboard\StoryboardVideoSetting;
use app\common\model\storyboard\StoryboardVideoTask;

class StoryboardVideoSettingLogic extends BaseLogic
{
    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                StoryboardVideoSetting::destroy(['id' => $id]);
                StoryboardVideoTask::where('video_setting_id', $id)->select()->delete();
            } else {
                StoryboardVideoSetting::whereIn('id', $id)->select()->delete();
                StoryboardVideoTask::whereIn('video_setting_id', $id)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
