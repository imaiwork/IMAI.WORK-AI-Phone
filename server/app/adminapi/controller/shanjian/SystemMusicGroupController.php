<?php

namespace app\adminapi\controller\shanjian;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\shanjian\SystemMusicGroupLists;
use app\common\model\shanjian\SystemMusic;
use app\common\model\shanjian\SystemMusicGroup;
use think\facade\Db;
use think\response\Json;

/**
 * 公共音乐分组（后台）。
 *
 * 分组只影响「用户端选曲怎么分 tab」，不参与模板中心同步：
 * 同步只写曲目的内容字段，永远不会改动曲目的 group_id。
 */
class SystemMusicGroupController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new SystemMusicGroupLists());
    }

    /** 下拉用：所有分组（不分页），列表页筛选与上传弹窗共用 */
    public function all(): Json
    {
        $rows = SystemMusicGroup::field('id,name')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'id' => (int)$row['id'],
                'name' => (string)$row['name'],
            ];
        }
        return $this->success('', $list, 1, 1);
    }

    /** 新增（无 id）或改名（有 id） */
    public function save(): Json
    {
        $id = (int)$this->request->post('id', 0);
        $name = trim((string)$this->request->post('name', ''));
        if ($name === '') {
            return $this->fail('请输入分组名称');
        }
        $name = mb_substr($name, 0, SystemMusicGroup::NAME_MAX_LENGTH);

        // 名称唯一：否则用户端会出现两个同名 tab，站长自己都分不清
        $exists = SystemMusicGroup::where('name', $name)
            ->when($id > 0, function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->count();
        if ($exists > 0) {
            return $this->fail('分组名称已存在');
        }

        if ($id > 0) {
            $group = SystemMusicGroup::where('id', $id)->find();
            if ($group === null) {
                return $this->fail('分组不存在或已被删除');
            }
            SystemMusicGroup::where('id', $id)->update([
                'name' => $name,
                'update_time' => time(),
            ]);
            return $this->success('保存成功', ['id' => $id, 'name' => $name], 1, 1);
        }

        // 新建分组排到最后：sort 取当前最大值 + 10，站长再按需调整
        $maxSort = (int)SystemMusicGroup::max('sort');
        $now = time();
        $group = SystemMusicGroup::create([
            'name' => $name,
            'sort' => $maxSort + 10,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        return $this->success('添加成功', [
            'id' => (int)$group->id,
            'name' => $name,
        ], 1, 1);
    }

    /**
     * 删除分组：分组里的曲目退回「无分组」而不是跟着删。
     * 曲目是内容资产，删一个分组不该把歌一起删掉。
     */
    public function delete(): Json
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) {
            return $this->fail('参数错误');
        }
        $group = SystemMusicGroup::where('id', $id)->find();
        if ($group === null) {
            return $this->fail('分组不存在或已被删除');
        }

        Db::startTrans();
        try {
            $moved = (int)SystemMusic::where('group_id', $id)->update([
                'group_id' => SystemMusicGroup::NO_GROUP,
            ]);
            SystemMusicGroup::where('id', $id)->delete();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->fail('删除失败，请稍后重试');
        }

        return $this->success(
            $moved > 0 ? "删除成功，{$moved} 首曲目已退回「无分组」" : '删除成功',
            ['moved' => $moved],
            1,
            1
        );
    }

    /** 分组排序：小的在前，决定用户端 tab 的先后 */
    public function updateSort(): Json
    {
        $id = (int)$this->request->post('id', 0);
        $sort = $this->request->post('sort', '');
        if ($id <= 0) {
            return $this->fail('参数错误');
        }
        if ($sort === '' || $sort === null || !is_numeric($sort)) {
            return $this->fail('排序必须是整数');
        }
        $exists = SystemMusicGroup::where('id', $id)->count() > 0;
        if (!$exists) {
            return $this->fail('分组不存在或已被删除');
        }

        SystemMusicGroup::where('id', $id)->update([
            'sort' => (int)$sort,
            'update_time' => time(),
        ]);

        return $this->success('排序已更新', ['id' => $id, 'sort' => (int)$sort], 1, 1);
    }
}
