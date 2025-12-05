<?php


namespace app\adminapi\lists\kb;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbKnowFiles;
use app\common\service\FileService;

/**
 * 知识库列表
 */
class KbKnowLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     */
    public function lists(): array
    {
        $model = new KbKnow();

        // 临时代码,同步创建者
        if (empty($this->params['page_no']) || $this->params['page_no'] == 1) {
            $model->where(['create_uid'=>0])->update([
                'create_uid' => app('db')->raw('user_id')
            ]);
        }

        if (!empty($this->params['start_time']) && !empty($this->params['end_time'])) {
            $this->searchWhere[] = ['create_time','between',[strtotime($this->params['start_time']),strtotime($this->params['end_time'])]];
        }

        $lists = $model
            ->alias('kk')
            ->field([
                'kk.id,kk.image,kk.name,kk.is_enable,kk.update_time,kk.create_time',
                'kk.user_id,u.sn,u.nickname,u.avatar,u.mobile,uc.nickname as create_user',
                'uc.avatar as create_avatar'
            ])
            ->leftJoin('user u', 'u.id = kk.user_id')
            ->leftJoin('user uc', 'uc.id = kk.create_uid')
            ->where($this->where())
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order('id desc')
            ->select()
            ->each(function ($item) {
                $item['file_counts'] = KbKnowFiles::where('know_id',$item['id'])->count();
            })
            ->toArray();

        foreach ($lists as &$item) {
            $item['user'] = [
                'id'       => $item['user_id'],
                'sn'       => $item['sn'],
                'nickname' => $item['nickname'],
                'mobile'   => $item['mobile'],
                'avatar'   => FileService::getFileUrl($item['avatar'])
            ];

            $item['create_avatar'] = FileService::getFileUrl($item['create_avatar']);
            unset($item['user_id']);
            unset($item['sn']);
            unset($item['nickname']);
            unset($item['mobile']);
            unset($item['avatar']);
        }

        return $lists;
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public function count(): int
    {
        $model = new KbKnow();
        return $model
            ->alias('kk')
            ->leftJoin('user u', 'u.id = kk.user_id')
            ->where($this->where())
            ->where($this->searchWhere)
            ->count();
    }

    public function setSearch(): array
    {
        return [
             '='     => ['kk.is_enable'],
            '%like%' => ['kk.name']
        ]??[];
    }

    public function where(): array
    {
        $where = [];
        if (isset($this->params['user']) && $this->params['user']) {
            $where[] = ['u.nickname|u.sn|u.mobile', 'like', '%'.$this->params['user'].'%'];
        }
        return $where;
    }
}