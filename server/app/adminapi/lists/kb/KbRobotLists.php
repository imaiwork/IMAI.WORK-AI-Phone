<?php


namespace app\adminapi\lists\kb;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\coze\AgentCate;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbRobot;
use app\common\model\user\User;
use app\common\service\AgentPermissionService;
use app\common\service\FileService;

/**
 * 机器人列表
 */
class KbRobotLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function lists(): array
    {
        $this->buildUserSourceWhere();

        $model = new KbRobot();
        $lists = $model
            ->alias('kr')
            ->field([
                        'kr.id,kr.kb_ids,kr.cate_id,kr.intro,kr.image,kr.bg_image,kr.name,kr.sort,kr.is_enable,kr.is_public',
                        'kr.permissions,kr.member_level_ids,kr.create_time,kr.user_id,u.nickname,u.avatar'
                    ])
            ->leftJoin('user u', 'u.id = kr.user_id')
            ->where($this->where())
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order('id desc')
            ->select()
            ->toArray();

        $modelKbKnow = new KbKnow();
        foreach ($lists as &$item) {
            $item['knows'] = [];
            if ($item['kb_ids']) {
                $kbIds = explode(',', $item['kb_ids']);
                $item['knows'] = $modelKbKnow->field(['id,name'])->whereIn('id', $kbIds)->select()->toArray();
            }

            $item['cate_id'] = $item['cate_id'] ?: '';
            $item['cate_name']   = $item['cate_id'] ? AgentCate::where('id', $item['cate_id'])->value('name') : '';
            $item['source']      = $item['user_id'] ? 1 : 0;
            $item['source_text'] = $item['source'] ? '用户' : '后台';
            $item['permissions_text'] = AgentPermissionService::getPermissionsText((int)($item['permissions'] ?? 0));
            $item['member_level_ids'] = AgentPermissionService::levelIdsToArray($item['member_level_ids'] ?? '');
            $item['avatar']      = $item['avatar'] ? FileService::getFileUrl($item['avatar']) : '';
            unset($item['kb_ids']);
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
        $this->buildUserSourceWhere();

        $model = new KbRobot();
        return $model
            ->alias('kr')
            ->field([
                        'kr.id,kr.kb_ids,kr.cate_id,kr.image,kr.name,kr.sort,kr.is_enable,kr.is_public',
                        'kr.create_time,kr.user_id,u.sn,u.nickname,u.avatar,u.mobile'
                    ])
            ->leftJoin('user u', 'u.id = kr.user_id')
            ->where($this->where())
            ->where($this->searchWhere)
            ->count();
    }

    /**
     * @notes 根据 nickname / user_id / source 构建查询条件（列表与统计共用，避免两处逻辑不一致）
     *        source=0：后台智能体；指定用户时返回该用户 + 后台智能体
     *        source=1：用户智能体；指定用户时仅返回该用户的智能体
     * @author kb
     */
    protected function buildUserSourceWhere(): void
    {
        $user   = $this->request->get('nickname');
        $userId = $this->request->get('user_id');
        $source = $this->request->get('source') ?? null;

        if ($user) {
            $userIds = User::where('nickname', 'like', '%' . $user . '%')->column('id');
            if ($source === 0 || $source === '0') {
                $userIds[]           = 0;
                $this->searchWhere[] = ['kr.user_id', 'in', $userIds];
            } else if ($source === 1 || $source === '1') {
                $this->searchWhere[] = ['kr.user_id', 'in', $userIds];
            }
        } else if ($userId) {
            if ($source === 0 || $source === '0') {
                $this->searchWhere[] = ['kr.user_id', 'in', [$userId, 0]];
            } else if ($source === 1 || $source === '1') {
                $this->searchWhere[] = ['kr.user_id', '=', $userId];
            }
        } else {
            if ($source === 1 || $source === '1') {
                $this->searchWhere[] = ['kr.user_id', '!=', 0];
            } else if ($source === 0 || $source === '0') {
                $this->searchWhere[] = ['kr.user_id', '=', 0];
            }
        }

        $keyword = $this->request->get('keyword');
        if ($keyword) {
            $this->searchWhere[] = ['kr.name', 'like', '%' . $keyword . '%'];
        }
    }

    public function setSearch(): array
    {
        return [
                   '='      => ['kr.is_enable', 'kr.is_public'],
                   '%like%' => ['kr.name']
               ] ?? [];
    }

    public function where(): array
    {
        $where = [];
        if (isset($this->params['keyword']) && $this->params['keyword']) {
            $where[] = ['u.nickname|u.sn|u.mobile', 'like', '%' . $this->params['keyword'] . '%'];
        }
        if (isset($this->params['cate_id']) && is_numeric($this->params['cate_id'])) {
            $where[] = ['kr.cate_id', '=', intval($this->params['cate_id'])];
        }

        return $where;
    }
}
