<?php

declare(strict_types=1);

namespace app\adminapi\controller\playVideo;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\playVideo\PlayVideoTemplateLists;
use think\response\Json;

/**
 * 爆款玩法模板（后台）。内容编辑在模板中心（定时任务每小时同步到本站）；
 * 本页提供**本地显隐开关**：关闭后该模板不在本站前台展示，且不被同步覆盖。
 */
class PlayVideoTemplateController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new PlayVideoTemplateLists());
    }

    /** 本地显隐：status 1显示 0隐藏（不影响模板中心与其他接入方） */
    public function changeStatus(): Json
    {
        $id = trim((string)$this->request->post('id', ''));
        $status = (int)$this->request->post('status', -1);
        if ($id === '' || !in_array($status, [0, 1], true)) {
            return $this->fail('参数错误');
        }
        $updated = \think\facade\Db::name('play_video_template')
            ->where('id', $id)
            ->update(['status' => $status, 'update_time' => time()]);
        if ($updated === 0) {
            return $this->fail('模板不存在或状态未变化');
        }
        return $this->success('已' . ($status === 1 ? '显示' : '隐藏'), [], 1, 1);
    }
}
