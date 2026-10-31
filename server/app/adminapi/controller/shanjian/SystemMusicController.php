<?php

namespace app\adminapi\controller\shanjian;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\shanjian\SystemMusicLists;
use app\common\enum\FileEnum;
use app\common\model\shanjian\SystemMusic;
use app\common\model\shanjian\SystemMusicGroup;
use app\common\service\FileService;
use app\common\service\UploadService;
use Exception;
use think\facade\Db;
use think\response\Json;

/**
 * 公共音乐库（后台）。两个来源共用一个菜单、两个 tab：
 *   - center：模板中心运营，每 5 分钟同步到本站，内容只读，本站只维护显隐与排序；
 *   - local ：站长自己上传，可改名、删除、显隐、排序，同步任务永不触碰。
 * 两种来源只要 status=1 且 enabled=1，用户端手动选曲与自动出片都能用到。
 *
 * 分组（group_id）是本站设置：模板中心同步只写内容字段，永远不碰分组。
 */
class SystemMusicController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new SystemMusicLists());
    }

    /** 上传音频文件（只落存储与文件记录，曲目名称等信息由 save 落库） */
    public function upload(): Json
    {
        try {
            $result = UploadService::audio(
                0,
                0,
                FileEnum::SOURCE_ADMIN,
                'uploads/system_music',
                true,
                SystemMusic::LOCAL_ALLOWED_EXTS
            );

            return $this->success('上传成功', [
                // uri 是完整可访问地址（含站点/存储域名），与模板中心下发的 audio_url 同构，直接存库
                'audio_url' => (string)$result['uri'],
                // 站内相对路径，便于后台排查与后续迁移
                'path' => (string)$result['url'],
                'name' => (string)$result['name'],
            ], 1, 1);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 新增本站曲目（id 为空）或修改曲目名称（id 非空）。
     * 模板中心同步的曲目不允许改名——name 属于同步维护的内容字段，改了下一轮就被覆盖。
     */
    public function save(): Json
    {
        $id = trim((string)$this->request->post('id', ''));
        $name = trim((string)$this->request->post('name', ''));
        if ($name === '') {
            return $this->fail('请输入曲目名称');
        }
        $name = mb_substr($name, 0, 128);

        // 分组可以不选（0=无分组）；传了就必须是已存在的分组
        $groupRaw = $this->request->post('group_id', null);
        $groupId = null;
        if ($groupRaw !== null && $groupRaw !== '') {
            $groupId = (int)$groupRaw;
            if ($groupId > 0 && !SystemMusicGroup::where('id', $groupId)->count()) {
                return $this->fail('分组不存在或已被删除');
            }
            $groupId = max(0, $groupId);
        }

        if ($id !== '') {
            $music = SystemMusic::where('id', $id)->find();
            if ($music === null) {
                return $this->fail('曲目不存在或已被删除');
            }
            if ((string)$music['source'] !== SystemMusic::SOURCE_LOCAL) {
                return $this->fail('模板中心同步的曲目不能改名，只能设置显隐、排序与分组');
            }
            // 不写 update_time：该列表示「曲目内容最近一次更新」，改名不算内容同步
            $update = ['name' => $name];
            if ($groupId !== null) {
                $update['group_id'] = $groupId;
            }
            SystemMusic::where('id', $id)->update($update);

            return $this->success('保存成功', [
                'id' => $id,
                'name' => $name,
                'group_id' => $groupId,
            ], 1, 1);
        }

        $audioUrl = trim((string)$this->request->post('audio_url', ''));
        $durationSec = max(0, (int)$this->request->post('duration_sec/d', 0));
        if ($audioUrl === '') {
            return $this->fail('请先上传音频文件');
        }
        // 兼容相对地址（本地上传且 APP_HOST 为空时 upload 返回 /uploads/...），统一补成绝对地址：
        // 曲库地址会直接交给闪剪去拉，相对路径它取不到
        $audioUrl = FileService::getFileUrl($audioUrl);
        if (!preg_match('#^https?://#i', $audioUrl)) {
            return $this->fail('音频地址不完整，请检查站点域名配置后重新上传');
        }

        $path = (string)(parse_url($audioUrl, PHP_URL_PATH) ?? '');
        $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, SystemMusic::LOCAL_ALLOWED_EXTS, true)) {
            return $this->fail('仅支持 ' . implode(' / ', SystemMusic::LOCAL_ALLOWED_EXTS) . ' 格式的音频');
        }
        if (strpos($path, '/uploads/') === false) {
            return $this->fail('只能使用本站上传的音频文件');
        }

        $now = time();
        $newId = 'local_' . substr(bin2hex(random_bytes(16)), 0, 26);
        SystemMusic::create([
            'id' => $newId,
            'name' => $name,
            'audio_url' => mb_substr($audioUrl, 0, 512),
            'duration_sec' => $durationSec,
            'enabled' => 1,
            // sort=0：本站曲目默认排在最前（生效排序为 IFNULL(sort_override, sort) ASC），
            // 站长觉得不合适再用「本站排序」调整；sort_override 留空表示不覆盖
            'sort' => 0,
            'status' => 1,
            'source' => SystemMusic::SOURCE_LOCAL,
            'group_id' => $groupId ?? SystemMusicGroup::NO_GROUP,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        return $this->success('添加成功', ['id' => $newId, 'group_id' => $groupId ?? 0], 1, 1);
    }

    /**
     * 改分组（列表页行内下拉）。0 = 无分组。
     * 模板中心同步来的曲目也允许改：分组是本站设置，同步不会覆盖。
     */
    public function changeGroup(): Json
    {
        $id = trim((string)$this->request->post('id', ''));
        $groupId = (int)$this->request->post('group_id', 0);
        if ($id === '') {
            return $this->fail('参数错误');
        }
        if ($groupId > 0 && !SystemMusicGroup::where('id', $groupId)->count()) {
            return $this->fail('分组不存在或已被删除');
        }
        $music = SystemMusic::where('id', $id)->find();
        if ($music === null) {
            return $this->fail('曲目不存在或已被模板中心下架');
        }

        // 不写 update_time：该列表示「曲目内容最近一次被模板中心更新」
        SystemMusic::where('id', $id)->update([
            'group_id' => max(0, $groupId),
        ]);

        return $this->success('分组已更新', [
            'id' => $id,
            'group_id' => max(0, $groupId),
        ], 1, 1);
    }

    /** 删除：只允许删本站上传的曲目（模板中心的行由同步维护，删了下一轮还会被插回） */
    public function delete(): Json
    {
        $id = trim((string)$this->request->post('id', ''));
        if ($id === '') {
            return $this->fail('参数错误');
        }

        $music = SystemMusic::where('id', $id)->find();
        if ($music === null) {
            return $this->fail('曲目不存在或已被删除');
        }
        if ((string)$music['source'] !== SystemMusic::SOURCE_LOCAL) {
            return $this->fail('模板中心同步的曲目不能删除，如需下架请关闭前台显示');
        }

        // 只删曲库记录：文件仍留在文件表/存储上，避免误删被其他业务引用的同一文件
        SystemMusic::where('id', $id)->delete();

        return $this->success('删除成功', [], 1, 1);
    }

    /** 本站显隐：status 1显示 0隐藏（隐藏后前台列表、随机选曲、指定曲目都不可用） */
    public function changeStatus(): Json
    {
        $id = trim((string)$this->request->post('id', ''));
        $status = (int)$this->request->post('status', -1);
        if ($id === '' || !in_array($status, [0, 1], true)) {
            return $this->fail('参数错误');
        }

        $exists = SystemMusic::where('id', $id)->count() > 0;
        if (!$exists) {
            return $this->fail('曲目不存在或已被模板中心下架');
        }

        // 不写 update_time：该列表示「曲目内容最近一次被模板中心更新」
        SystemMusic::where('id', $id)->update(['status' => $status]);

        return $this->success(
            $status === 1 ? '已设为前台显示' : '已隐藏，用户端不再使用该曲目（同步不会自动恢复）',
            ['id' => $id, 'status' => $status],
            1,
            1
        );
    }

    /** 本站排序：传空串/null = 清除本站覆盖；传整数 = 写入 sort_override（小的在前） */
    public function updateSort(): Json
    {
        $id = trim((string)$this->request->post('id', ''));
        $sort = $this->request->post('sort', '');
        if ($id === '') {
            return $this->fail('参数错误');
        }

        $music = SystemMusic::where('id', $id)->find();
        if ($music === null) {
            return $this->fail('曲目不存在或已被模板中心下架');
        }

        if ($sort === '' || $sort === null) {
            SystemMusic::where('id', $id)->update(['sort_override' => Db::raw('NULL')]);
            $effective = (int)$music['sort'];
        } else {
            if (!is_numeric($sort)) {
                return $this->fail('排序必须是整数');
            }
            $sortValue = (int)$sort;
            SystemMusic::where('id', $id)->update(['sort_override' => $sortValue]);
            $effective = $sortValue;
        }

        return $this->success('排序已更新', [
            'id' => $id,
            'sort_override' => $sort === '' || $sort === null ? null : (int)$sort,
            'effective_sort' => $effective,
        ], 1, 1);
    }
}
