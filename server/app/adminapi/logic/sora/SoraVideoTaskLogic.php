<?php

namespace app\adminapi\logic\sora;

use app\common\logic\BaseLogic;
use app\common\model\sora\SoraVideoSetting;
use app\common\model\sora\SoraVideoTask;

class SoraVideoTaskLogic extends BaseLogic
{
    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                $task = SoraVideoTask::where('id', $id)->field('video_setting_id,status')->findOrEmpty();
                $status = $task['status'] ?? null;
                if ($status === null){
                    self::setError('数据不存在');
                    return false;
                }
                if($status == 2) {
                    SoraVideoSetting::where('id', $task['video_setting_id'])->dec("error_num")->update();

                }elseif ($status == 3){
                    SoraVideoSetting::where('id', $task['video_setting_id'])->dec("success_num")->update();
                }
                SoraVideoSetting::where('id', $task['video_setting_id'])->dec("video_count")->update();
                SoraVideoTask::destroy(['id' => $id]);
            } else {
                $tasks = SoraVideoTask::whereIn('id', $id)->field('video_setting_id,status')->select();
                $video_count = 0;
                foreach ($tasks as $task) {
                    $status = $task['status'] ?? null;
                    if ($status === null){
                        self::setError('数据不存在');
                        return false;
                    }
                    $video_count++;
                    if($status == 2) {
                        SoraVideoSetting::where('id', $task['video_setting_id'])->dec("error_num")->update();

                    }elseif ($status == 3){
                        SoraVideoSetting::where('id', $task['video_setting_id'])->dec("success_num")->update();
                    }
                }
                if ( $video_count > 0) {
                    SoraVideoSetting::where('id', $task['video_setting_id'])->dec("video_count",$video_count)->update();
                }
                SoraVideoTask::whereIn('id', $id)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
