<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\service\FileService;

class MaterialUseLogLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['log.persona_id', 'log.material_id', 'log.task_id', 'log.use_scene', 'log.use_status'],
            'like' => ['log.fail_reason'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['log.user_id', '=', $this->userId];
        return  MaterialUseLog::alias('log')
            ->field('task.pic,task.video_result_url,task.device_code,task.create_time,task.name,task.status,task.remark,
             task.duration,log.id,log.use_scene, log.use_status, log.fail_reason, device.device_name, device.device_model')
            ->join('iw_shanjian_video_task task', 'task.id = log.task_id')
            ->join('iw_sv_device device', 'device.device_code = task.device_code')
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order('log.id', 'desc')                                // 排序
            ->select()->each(function ($item) {
                if($item['use_status'] == 1){
                    $item['video_result_url']         = trim($item['video_result_url']) ? FileService::getFileUrl($item['video_result_url']): "";
                }else{
                    $item['video_result_url']         = "";
                }
            })->toArray(); // 如果需要转为数组

    }

    public function count(): int
    {
        $this->searchWhere[] = ['log.user_id', '=', $this->userId];
        
        return MaterialUseLog::alias('log')
            ->join('iw_shanjian_video_task task', 'task.id = log.task_id')
            ->join('iw_sv_device device', 'device.device_code = task.device_code')
            ->where($this->searchWhere)
            ->count();
    }
}
