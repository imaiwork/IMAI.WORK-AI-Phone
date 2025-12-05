<?php


namespace app\adminapi\controller;

use app\adminapi\controller\BaseAdminController;
use app\common\service\ConfigService;

/**
 *系统更新
 */
class UpdateController extends BaseAdminController
{
    /**
     * @notes 检查更新
     * @author 段誉 
     * @date 2022/9/20 15:30
     */
    public function check()
    {

        $version = ConfigService::get('website', 'version', []);

        $response = \app\common\service\ToolsService::Auth()->checkUpdate([
            'version' => $version['version_number'] ?? '100',
        ]);

        return $this->success('success', $response['data'] ?? [], show: 0);
    }

    /**
     * @notes 版本列表
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {

        $response = \app\common\service\ToolsService::Auth()->versionList();

        return $this->data($response['data'] ?? []);
    }

    /**
     * @notes 执行更新
     * @author 段誉 
     * @date 2022/9/20 15:30
     */
    public function exec()
    {

        $version = ConfigService::get('website', 'version', []);

        $nextVersion = $this->request->post('version', 0);

        if ($version['version_number'] >= $nextVersion) {

            return $this->fail('当前版本已是最新');
        }

        if ($nextVersion < $version['version_number']) {

            return $this->fail('版本必须逐个更新，也不可回退版本');
        }

        $response = \app\common\service\ToolsService::Auth()->execUpdate([
            'version' => $version['version_number'] ?? '100',
            'next_version' => $nextVersion,
        ]);

        $version['version_name']    = $response['data']['version_name'];
        $version['version_number']  = $nextVersion;
        $version['update_time']     = date('Y-m-d H:i:s');

        ConfigService::set('website', 'version', $version);
        return $this->success('success');
    }
}
