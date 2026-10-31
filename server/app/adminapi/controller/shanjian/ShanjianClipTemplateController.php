<?php

namespace app\adminapi\controller\shanjian;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\shanjian\ShanjianClipTemplateLists;
use app\common\model\shanjian\ShanjianClipTemplate;
use think\facade\Db;
use think\response\Json;

/**
 * 闪剪视频模板（content 由模板中心运营、每 5 分钟同步到本站）。
 *
 * 后台只维护两个「本站覆盖位」，同步任务永不覆盖：
 *   - status        前台显隐（0 隐藏后，用户端列表、手动创作、24 小时创作、热点/爆款复刻都不再使用）
 *   - sort_override 本站排序（NULL = 跟随模板中心下发的 sort）
 * 也供人设合成等后台模板选择器复用（默认只出可见模板）。
 */
class ShanjianClipTemplateController extends BaseAdminController
{
    public array $notNeedLogin = [];

    public function lists(): Json
    {
        return $this->dataLists(new ShanjianClipTemplateLists());
    }

    /** 本站显隐：status 1显示 0隐藏（不影响模板中心与其他接入方） */
    public function changeStatus(): Json
    {
        $id = trim((string)$this->request->post('id', ''));
        $status = (int)$this->request->post('status', -1);
        if ($id === '' || !in_array($status, [0, 1], true)) {
            return $this->fail('参数错误');
        }

        $exists = ShanjianClipTemplate::where('id', $id)->count() > 0;
        if (!$exists) {
            return $this->fail('模板不存在或已被模板中心下架');
        }

        // 不写 update_time：该列表示「内容最近一次被模板中心更新」，与显隐操作无关
        ShanjianClipTemplate::where('id', $id)->update(['status' => $status]);

        return $this->success(
            $status === 1 ? '已设为前台显示' : '已隐藏，用户端不再使用该模板（同步不会自动恢复）',
            ['id' => $id, 'status' => $status],
            1,
            1
        );
    }

    /**
     * 本站排序：传空串/null = 恢复跟随模板中心；传整数 = 写入 sort_override。
     * 数值越大越靠前（与模板中心 sort 语义一致），实际生效值见 effective_sort。
     */
    public function updateSort(): Json
    {
        $id = trim((string)$this->request->post('id', ''));
        $sort = $this->request->post('sort', '');
        if ($id === '') {
            return $this->fail('参数错误');
        }

        $template = ShanjianClipTemplate::where('id', $id)->find();
        if ($template === null) {
            return $this->fail('模板不存在或已被模板中心下架');
        }

        if ($sort === '' || $sort === null) {
            // 恢复跟随：必须显式写 NULL（写 0 会被当成"本站排到 0"）
            ShanjianClipTemplate::where('id', $id)->update(['sort_override' => Db::raw('NULL')]);
            $effective = (int)$template['sort'];
        } else {
            if (!is_numeric($sort)) {
                return $this->fail('排序必须是整数');
            }
            $sortValue = (int)$sort;
            ShanjianClipTemplate::where('id', $id)->update(['sort_override' => $sortValue]);
            $effective = $sortValue;
        }

        return $this->success('排序已更新', [
            'id' => $id,
            'sort_override' => $sort === '' || $sort === null ? null : (int)$sort,
            'effective_sort' => $effective,
        ], 1, 1);
    }
}
