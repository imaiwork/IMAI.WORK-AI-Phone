<?php


namespace app\adminapi\lists\hd;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\hd\HdImageCases;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;


/**
 * HdImageCase列表  
 * Class HdImageCaseLists
 * @package app\adminapi\listshd
 */
class HdImageCaseLists extends BaseAdminDataLists implements ListsSearchInterface
{


    /**
     * @desc 设置搜索条件
     * @return array[]
     * @date 2024/5/23 11:52
     * @author dagouzi
     */
    public function setSearch(): array
    {
        return [
            '=' => ['aic.status'],
        ];
    }


    /**
     * @desc 获取列表
     * @return array
     * @date 2024/5/23 11:52
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public function lists(): array
    {

        return HdImageCases::alias('aic')
            ->leftJoin('user u', 'u.id = aic.user_id and aic.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('case_type', []), function ($query) {
                $query->whereIn('case_type', $this->request->get('case_type', []));
            })
            ->when($this->request->get('user', []), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user', []) . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('aic.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->field('aic.*, u.nickname, u.avatar')
            ->limit($this->limitOffset, $this->limitLength)
            ->order('id', 'desc')
            ->select()
            ->each(function ($item) {
                $params = json_decode($item['params'], true);

                foreach ($params['images'] ?? [] as $key => $value) {

                    $params['images'][$key] = $value ? FileService::getFileUrl($value) : "";
                }

                $item['params'] = $params;


                $item['nickname']   = $item['nickname'] ?? '';
                $item['avatar']     = $item['avatar'] ? FileService::getFileUrl($item['avatar']) : '';
                $item['result_image']     = $item['result_image'] ? FileService::getFileUrl($item['result_image']) : '';
            })
            ->toArray();
    }


    /**
     * @desc 获取数量
     * @return int
     * @date 2024/5/23 11:52
     * @throws \think\db\exception\DbException
     * @author dagouzi
     */
    public function count(): int
    {
        return HdImageCases::alias('aic')
            ->leftJoin('user u', 'u.id = aic.user_id and aic.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('case_type', []), function ($query) {
                $query->whereIn('case_type', $this->request->get('case_type', []));
            })
            ->when($this->request->get('user', []), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user', []) . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('aic.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->field('aic.*, u.nickname, u.avatar')
            ->count();
    }
}
