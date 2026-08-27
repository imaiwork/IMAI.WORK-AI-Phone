<?php

namespace app\api\controller\hotspot;

use app\api\controller\BaseApiController;
use app\api\logic\hotspot\HotspotLogic;
use app\api\validate\hotspot\HotspotValidate;

class HotspotController extends BaseApiController
{
    public array $notNeedLogin = [
        'health',
        'platforms',
        'historyDates',
        'options',
    ];

    public function health()
    {
        return $this->data(HotspotLogic::health());
    }

    public function platforms()
    {
        return $this->data(HotspotLogic::platforms());
    }

    public function hot()
    {
        $params = (new HotspotValidate())->get()->goCheck('hot');
        $result = HotspotLogic::hot($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->data($result);
    }

    public function historyDates()
    {
        $params = (new HotspotValidate())->get()->goCheck('historyDates');
        $result = HotspotLogic::historyDates($params);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->data($result);
    }

    public function insight()
    {
        $params = (new HotspotValidate())->get()->goCheck('insight');
        $result = HotspotLogic::insight($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->data($result);
    }

    public function hotWords()
    {
        $params = (new HotspotValidate())->get()->goCheck('hotWords');
        $result = HotspotLogic::hotWords($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->data($result);
    }

    public function research()
    {
        set_time_limit(120);
        $params = $this->checkedPost('research');
        $result = HotspotLogic::research($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->data($result);
    }

    public function analyze()
    {
        set_time_limit(120);
        $params = $this->checkedPost('analyze');
        $result = HotspotLogic::analyze($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->data($result);
    }

    public function script()
    {
        set_time_limit(120);
        $params = $this->checkedPost('script');
        $result = HotspotLogic::script($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->data($result);
    }

    public function options()
    {
        return $this->data(HotspotLogic::options());
    }

    public function personas()
    {
        return $this->data(HotspotLogic::personas((int)$this->userId));
    }

    public function avatars()
    {
        $params = (new HotspotValidate())->get()->goCheck('avatars');
        $result = HotspotLogic::avatars($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->data($result);
    }

    public function clipMaterials()
    {
        $params = (new HotspotValidate())->get()->goCheck('clipMaterials');
        $result = HotspotLogic::clipMaterials($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->data($result);
    }

    public function lastFlow()
    {
        $params = (new HotspotValidate())->get()->goCheck('lastFlow');
        return $this->data(HotspotLogic::lastFlow($params, (int)$this->userId));
    }

    public function tasks()
    {
        $params = (new HotspotValidate())->get()->goCheck('tasks');
        return $this->data(HotspotLogic::tasks((int)$this->userId, $params));
    }

    public function add()
    {
        $params = $this->checkedPost('taskAdd');
        $result = HotspotLogic::add($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->success('操作成功', $result);
    }

    public function detail()
    {
        $params = (new HotspotValidate())->get()->goCheck('taskId');
        $result = HotspotLogic::detail($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->data($result);
    }

    public function delete()
    {
        $input = $this->jsonInput();
        $id = (string)$this->request->param('id', '');
        if ($id === '') {
            $id = (string)($input['id'] ?? '');
        }
        $params = (new HotspotValidate())->post()->goCheck('taskId', [
            'id' => $id,
        ]);
        $result = HotspotLogic::delete($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->success('操作成功');
    }

    public function retry()
    {
        $input = $this->jsonInput();
        $id = (string)$this->request->param('id', '');
        if ($id === '') {
            $id = (string)($input['id'] ?? '');
        }
        $params = (new HotspotValidate())->post()->goCheck('taskId', [
            'id' => $id,
        ]);
        $result = HotspotLogic::retry($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(HotspotLogic::getError());
        }
        return $this->success('操作成功', $result);
    }

    private function checkedPost(string $scene): array
    {
        return (new HotspotValidate())->post()->goCheck($scene, $this->jsonInput());
    }

    private function jsonInput(): array
    {
        $raw = trim((string)$this->request->getContent());
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
