<?php

namespace app\adminapi\logic\shanjian;

use app\common\logic\BaseLogic;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;

class ShanjianVideoTaskLogic extends BaseLogic
{
    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                $task = ShanjianVideoTask::where('id', $id)->field('id,video_setting_id,status,shanjian_type')->findOrEmpty();
                $status = $task['status'] ?? null;
                if ($status === null){
                    self::setError('数据不存在');
                    return false;
                }
                if($status == 2) {
                    ShanjianVideoSetting::where('id', $task['video_setting_id'])->dec("error_num")->update();

                }elseif ($status == 3){
                    ShanjianVideoSetting::where('id', $task['video_setting_id'])->dec("success_num")->update();
                }
                ShanjianVideoSetting::where('id', $task['video_setting_id'])->dec("video_count")->update();
                if ((int)($task['shanjian_type'] ?? 0) === 5) {
                    // 派生包装任务不在创作记录单独展示，随源任务一并删除，避免孤儿
                    ShanjianVideoTask::deleteDerivedPackaging([(int)$task['id']]);
                }
                ShanjianVideoTask::destroy(['id' => $id]);
            } else {
                $tasks = ShanjianVideoTask::whereIn('id', $id)->field('id,video_setting_id,status,shanjian_type')->select();
                $video_count = 0;
                $type5Ids = [];
                foreach ($tasks as $task) {
                    $status = $task['status'] ?? null;
                    if ($status === null){
                        self::setError('数据不存在');
                        return false;
                    }
                    $video_count++;
                    if ((int)($task['shanjian_type'] ?? 0) === 5) {
                        $type5Ids[] = (int)$task['id'];
                    }
                    if($status == 2) {
                        ShanjianVideoSetting::where('id', $task['video_setting_id'])->dec("error_num")->update();

                    }elseif ($status == 3){
                        ShanjianVideoSetting::where('id', $task['video_setting_id'])->dec("success_num")->update();
                    }
                }
                if ( $video_count > 0) {
                    ShanjianVideoSetting::where('id', $task['video_setting_id'])->dec("video_count",$video_count)->update();
                }
                ShanjianVideoTask::deleteDerivedPackaging($type5Ids);
                ShanjianVideoTask::whereIn('id', $id)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
