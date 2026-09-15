<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\sv\CloudSvDevice;
use app\common\service\FileService;
use app\common\service\hotspot\VideoService as HotspotVideoService;

class MaterialUseLogLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * 只呈现自动任务生成的视频。热点追踪等用户手动发起的任务是 auto_type=0，
     * 它们确实用掉了人设素材，但不属于「素材被自动任务用了几次」这件事，
     * 混在这里会让用户以为自动合成跑了很多轮。素材列表的「已用N次」同口径过滤。
     */
    public const AUTO_TASK = 1;

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
        $this->searchWhere[] = ['task.auto_type', '=', self::AUTO_TASK];
        return  MaterialUseLog::alias('log')
            ->field('task.pic,task.video_result_url,task.device_code,task.create_time,task.name,task.status,task.remark,
             task.duration,log.id,log.use_scene, log.use_status, log.fail_reason, device.device_name, device.device_model')
            ->join('iw_shanjian_video_task task', 'task.id = log.task_id')
            // 无手机模式的任务挂虚拟设备号 cloud:{persona_id}，sv_device 里永远没有行；
            // 设备被删掉的老任务同样查不到。内连接会把这些生成记录整条丢掉，
            // 而素材列表的"已用N次"只数 use_log 不 join 设备，两边就对不上了
            ->leftJoin('iw_sv_device device', 'device.device_code = task.device_code')
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order('log.id', 'desc')                                // 排序
            ->select()->each(function ($item) {
                if($item['use_status'] == 1){
                    $item['video_result_url']         = trim($item['video_result_url']) ? FileService::getFileUrl($item['video_result_url']): "";
                }else{
                    $item['video_result_url']         = "";
                }
                $deviceCode = trim((string)$item['device_code']);
                if (CloudSvDevice::isCloudCode($deviceCode)) {
                    // 虚拟设备号只是内部分区键，不给用户看
                    $item['device_name'] = '云端合成';
                    $item['device_code'] = '';
                } elseif ($deviceCode === '') {
                    // 热点追踪这类用户手动发起的任务压根不挂设备，device_code 一直是空，
                    // 不能跟「设备号还在但设备行被删了」共用"未知设备"的兜底
                    $item['device_name'] = str_starts_with((string)$item['name'], HotspotVideoService::NAME_PREFIX)
                        ? '热点追踪'
                        : '手动创建';
                    $item['device_code'] = '';
                } elseif (trim((string)$item['device_name']) === '') {
                    $item['device_name'] = '未知设备';
                }
            })->toArray(); // 如果需要转为数组

    }

    public function count(): int
    {
        $this->searchWhere[] = ['log.user_id', '=', $this->userId];
        $this->searchWhere[] = ['task.auto_type', '=', self::AUTO_TASK];
        
        return MaterialUseLog::alias('log')
            ->join('iw_shanjian_video_task task', 'task.id = log.task_id')
            ->leftJoin('iw_sv_device device', 'device.device_code = task.device_code')
            ->where($this->searchWhere)
            ->count();
    }
}
