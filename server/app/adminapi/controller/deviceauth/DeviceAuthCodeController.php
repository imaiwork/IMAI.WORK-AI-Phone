<?php

namespace app\adminapi\controller\deviceauth;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\deviceauth\DeviceAuthCodeLogic;
use app\adminapi\validate\deviceauth\DeviceAuthCodeValidate;
use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\service\deviceauth\DeviceAuthCodeSyncService;

class DeviceAuthCodeController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists();
    }

    public function statistics()
    {
        $data = (new DeviceAuthCodeLogic())->statistics();
        return $this->success('', $data);
    }

    public function generate()
    {
        $post = (new DeviceAuthCodeValidate())->post()->goCheck('generate');
        $result = (new DeviceAuthCodeLogic())->generate($post, $this->adminId);
        if ($result === true) {
            return $this->success('生成成功', [], 1, 1);
        }
        return $this->fail($result);
    }

    public function import()
    {
        try {
            $file = $this->request->file('file');
            if (!$file) {
                return $this->fail('请上传文件');
            }
            $defaultType = (int)$this->request->post('type', 0);
            $result = (new DeviceAuthCodeLogic())->import($file, $this->adminId, $defaultType);
            return $this->success('导入完成', $result, 1, 1);
        } catch (\Exception $e) {
            return $this->fail('导入失败: ' . $e->getMessage());
        }
    }

    public function detail()
    {
        (new DeviceAuthCodeValidate())->goCheck('id');
        $detail = (new DeviceAuthCodeLogic())->detail((int)$this->request->get('id'));
        return $this->success('', $detail);
    }

    public function disable()
    {
        try {
            (new DeviceAuthCodeValidate())->post()->goCheck('id');
            (new DeviceAuthCodeLogic())->disable((int)$this->request->post('id'));
            return $this->success('作废成功', [], 1, 1);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function del()
    {
        try {
            (new DeviceAuthCodeValidate())->post()->goCheck('id');
            (new DeviceAuthCodeLogic())->del((int)$this->request->post('id'));
            return $this->success('删除成功', [], 1, 1);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 转移设备CDK给用户
     */
    public function transfer()
    {
        try {
            $params = (new DeviceAuthCodeValidate())->post()->goCheck('transfer');
            (new DeviceAuthCodeLogic())->transfer($params);
            return $this->success('操作成功', [], 1, 1);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function getConfig()
    {
        $config = (new DeviceAuthCodeLogic())->getConfig();
        return $this->success('', $config);
    }

    public function setConfig()
    {
        (new DeviceAuthCodeValidate())->post()->goCheck('setConfig');
        (new DeviceAuthCodeLogic())->setConfig($this->request->post());
        return $this->success('设置成功', [], 1, 1);
    }

    public function getOtherList()
    {
        return [
            'type_list'   => DeviceAuthCodeEnum::getTypeDesc(),
            'status_list' => DeviceAuthCodeEnum::getStatusDesc(),
            'source_list' => DeviceAuthCodeEnum::getSourceDesc(),
        ];
    }

    public function syncFromPlatform()
    {
        try {
            $lastSync = (int)$this->request->post('updated_since', 0);
            $params = [];
            if ($lastSync > 0) {
                $params['updated_since'] = $lastSync;
            }
            $status = (int)$this->request->post('status', 0);
            if ($status > 0) {
                $params['status'] = $status;
            }
            $result = DeviceAuthCodeSyncService::pullFromPlatform($params);
            return $this->success('同步成功', $result, 1, 1);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function syncBothEnds()
    {
        try {
            $result = DeviceAuthCodeSyncService::syncBothEnds();
            return $this->success('双端同步成功', $result, 1, 1);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
