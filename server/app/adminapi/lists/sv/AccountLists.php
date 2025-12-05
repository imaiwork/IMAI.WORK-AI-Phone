<?php
namespace app\adminapi\lists\sv;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvAccount;
/**
 * 账号列表
 * Class AccountLists
 * @package app\adminapi\lists\sv
 * @author Lee
 */
class AccountLists extends BaseAdminDataLists implements ListsSearchInterface
{ 
     /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-10 09:40:09
     */
     public function lists(): array
     {
        return SvAccount::field('id,nickname,avatar,extra')
            ->where('type',$this->request->param('type'))
            ->where($this->searchWhere)
            ->order('id','desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                if (!empty($item['extra'])) {
                    $item['extra'] = json_decode($item['extra']);
                }

            })
            ->toArray();
     }
      /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvAccount::field('id,device_code,account,nickname,avatarunt,status,create_time')
            ->where($this->searchWhere)
            ->count();
    }

     /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function setSearch(): array
    {
        return [
            "%like%" =>  ['nickname'],
        ];
    }
 
}