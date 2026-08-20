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

        if (!empty($this->params['start_time']) && !empty($this->params['end_time'])) {
            $this->searchWhere[] = ['create_time','between',[strtotime($this->params['start_time']),strtotime($this->params['end_time'])]];
        }

        $lists = $model
            ->alias('kk')
            ->field([
                'kk.id,kk.image,kk.name,kk.intro,kk.is_enable,kk.update_time,kk.create_time',
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
                'avatar'   => FileService::getFileUrl($item['avatar']??'')
            ];

            $item['create_avatar'] = FileService::getFileUrl($item['create_avatar']??'');
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
            ->leftJoin('user uc', 'uc.id = kk.create_uid')
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
        $where[] = ['kk.name', '<>', '模型大管家'];
        // 用户信息查询: 同时匹配所属用户与创建人(昵称/编号/手机号)
        if (isset($this->params['user']) && $this->params['user']) {
            $where[] = ['u.nickname|u.sn|u.mobile|uc.nickname|uc.sn|uc.mobile', 'like', '%'.$this->params['user'].'%'];
        }

        // 后台创建筛选: is_bind=1 仅后台创建(user_id=0), is_bind=0 仅用户创建
        if (isset($this->params['is_bind']) && $this->params['is_bind'] !== '') {
            if (intval($this->params['is_bind']) === 1) {
                $where[] = ['kk.user_id', '=', 0];
            } else {
                $where[] = ['kk.user_id', '<>', 0];
            }
        }
        return $where;
    }
}