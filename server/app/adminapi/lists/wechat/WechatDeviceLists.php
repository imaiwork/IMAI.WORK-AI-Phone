<?php


namespace app\adminapi\lists\wechat;

use  app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatDevice;
use app\common\service\FileService;


/**
 * 设备列表
 */
class WechatDeviceLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "=" => ['d.device_code', 'd.device_status', 'w.wechat_id']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function lists(): array
    {
        return AiWechatDevice::alias('d')
            ->join('ai_wechat w', 'w.device_code = d.device_code')
            ->join('user u', 'u.id = d.user_id')
            ->field('d.id,d.device_code,w.wechat_id,w.wechat_no,w.wechat_nickname,d.sdk_version,d.device_status,d.create_time,u.nickname,u.avatar')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('d.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->order(['d.create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['avatar'] = FileService::getFileUrl($item['avatar']);

            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        return AiWechatDevice::alias('d')
            ->join('ai_wechat w', 'w.device_code = d.device_code')
            ->join('user u', 'u.id = d.user_id')
            ->field('d.id,d.device_code,w.wechat_id,w.wechat_no,w.wechat_nickname,d.sdk_version,d.device_status,u.nickname,u.avatar')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('d.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }
}
