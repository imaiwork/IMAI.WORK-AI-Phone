<?php


namespace app\api\lists\draw;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\draw\DrawVideo;
use app\common\service\FileService;
use app\common\service\draw\DrawBillingService;


/**
 * 充值记录列表
 * Class RechargeLists
 * @package app\api\lists\draw
 */
class VideoRecordLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
    }
    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Rick
     * @date 2025/7/7 10:50
     */
    public function lists(): array
    {
        $where = [];
        $request = $this->request->get();
        if (isset($request['type']) && $request['type'] !='' && $request['type'] != '6') {
            $where['dv.type'] = $request['type'];
        }
        $where['dv.user_id'] = $this->userId;
        $result = DrawVideo::alias('dv')
                            ->join('user u', 'u.id = dv.user_id')
                            ->when($this->request->get('user'), function ($query) {
                                $query->where('nickname', 'like', '%' . $this->request->get('user') . '%');
                            })
                            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                                $query->whereBetween('dv.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
                            })
                            ->field("dv.*,u.nickname,u.avatar,6 as draw_type")
                            ->where($where)
                            ->order('dv.id desc')
                            ->limit($this->limitOffset, $this->limitLength)
                            ->select()
                            ->each(function($item) {
                                $item->video_url = $item->video_url ? FileService::getFileUrl($item->video_url) : '';
                                $item->avatar = $item->avatar ? FileService::getFileUrl($item->avatar) : '';
                                $amount = DrawBillingService::resolveRecordPoints(
                                    (int)$item->user_id,
                                    (string)($item->task_id ?? ''),
                                    (int)($item->draw_task_id ?? 0),
                                    DrawBillingService::videoRecordChangeTypes()
                                );
                                $pointsInfo = DrawBillingService::describeVideoRecordPoints($amount, (int)$item->task_status);
                                $item->points = $pointsInfo['points'];
                                $item->points_remark = $pointsInfo['points_remark'];
                                $item->type_name = $this->type_name($item->type);
                            })
                           ->toArray();
        return $result;
    }

    /**
     * @notes  获取数量
     * @return int
     * @author Rick
     * @date 2025/7/8 10:43
     */
    public function count(): int
    {
        $where = [];
        $request = $this->request->get();
        if (!empty($request['type'])) {
            $where['type'] = $request['type'];
        }
        $where['user_id'] = $this->userId;
        return DrawVideo::where($where)->count();
    }

    /**
     * @notes 类型格式化
     * @param $type
     * @return string
     * @author Rick
     * @date 2025/7/11 10:43
     */
    private function type_name($type): string
    {
        $arr = ['文生视频','图生视频'];
        return $arr[$type];
    }
}
