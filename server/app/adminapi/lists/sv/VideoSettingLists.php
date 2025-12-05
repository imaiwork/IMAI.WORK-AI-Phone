<?php
namespace app\adminapi\lists\sv;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\ModelConfig;
use app\common\model\sv\SvVideoSetting;
use app\common\model\sv\SvVideoTask;
use app\common\model\user\User;

class VideoSettingLists extends BaseAdminDataLists implements ListsSearchInterface
{

    public function setSearch(): array
    {
        return [
            '=' => [ 'type', 'ai_type'],
            '%like%' => ['name'],
            'between' => ['create_time'],
            'in' => ['status']
            // 其他搜索条件
        ];
    }
     /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author Lee
     * @date 2025-05-14 15:15:09
     */
     public function lists(): array
     {
        $lists = SvVideoSetting::alias('sv')
            ->join('user u', 'u.id = sv.user_id')

            ->field('sv.id,sv.name,sv.video_count,sv.create_time,sv.status,sv.update_time,sv.success_num,
            sv.automatic_clip,sv.model_version,sv.error_num,sv.user_id,u.nickname')
            ->order('sv.id','desc')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('sv.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item){
            $item['anchor_token'] = SvVideoTask::where('video_setting_id',$item['id'])->sum('anchor_token') ?? 0;
            $item['voice_token'] = SvVideoTask::where('video_setting_id',$item['id'])->sum('voice_token')?? 0;
            $item['audio_token'] = SvVideoTask::where('video_setting_id',$item['id'])->sum('audio_token')?? 0;
            $item['video_token'] = SvVideoTask::where('video_setting_id',$item['id'])->sum('video_token')?? 0;
            $item['clip_token'] = 0;
            if ($item['automatic_clip'] == 1){
                $item['clip_token'] = SvVideoTask::where('video_setting_id',$item['id'])->sum('clip_token');
            }
            $item['all_token'] = $item['anchor_token'] + $item['voice_token']+$item['audio_token']+$item['video_token'] + $item['clip_token'] ;
            $latest_time = SvVideoTask::where('video_setting_id',$item['id'])->where('status',6)
                ->max('update_time') ?? '';
            $item['latest_time'] = '暂无视频合成成功';
            if ($latest_time){
                $item['latest_time'] = date('Y-m-d H:i:s',$latest_time);
            }

        }

         return $lists;
     }
     /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvVideoSetting::alias('sv')
            ->join('user u', 'u.id = sv.user_id')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('sv.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }

}