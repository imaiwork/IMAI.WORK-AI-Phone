<?php


namespace app\adminapi\lists\draw;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\draw\DrawVideo;
use app\common\service\FileService;


/**
 * 充值记录列表
 * Class RechargeLists
 * @package app\api\lists\draw
 */
class VideoRecordLists extends BaseAdminDataLists implements ListsSearchInterface
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
        if (isset($request['type']) && $request['type'] !='') {
            $where['dv.type'] = $request['type'];
        }
        $result = DrawVideo::alias('dv')
                            ->join('user u', 'u.id = dv.user_id')
                            ->with([
                                      'userTokensLog' => function($query) {
                                          $query->field('task_id, change_amount')->where('task_id', '<>', '');
                                      }
                                  ])
                            ->when($this->request->get('user'), function ($query) {
                                $query->where('nickname', 'like', '%' . $this->request->get('user') . '%');
                            })
                            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                                $query->whereBetween('dv.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
                            })
                            ->field('dv.*,u.nickname,u.avatar')
                            ->where($where)
                            ->order('dv.id desc')
                            ->limit($this->limitOffset, $this->limitLength)
                            ->select()
                            ->each(function($item) {
                                $item->video_url = $item->video_url ? FileService::getFileUrl($item->video_url) : '';
                                $item->avatar = $item->avatar ? FileService::getFileUrl($item->avatar) : '';
                                $item->points = ($item->task_status == 1 || 2 ? '-' : ($item->task_status == -1 ? '+' : '')) . ($item->userTokensLog ? $item->userTokensLog->change_amount : 0);
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
