<?php

namespace app\adminapi\validate\deviceauth;

use app\adminapi\logic\deviceauth\DeviceAuthPlanLogic;
use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\model\deviceauth\DeviceAuthPlan;
use app\common\model\deviceauth\DeviceCdkCode;
use app\common\validate\BaseValidate;

class DeviceAuthPlanValidate extends BaseValidate
{
    protected $rule = [
        'id'           => 'require|checkId',
        'name'         => 'require|max:64',
        'type'         => 'require|checkType|checkTypeUnique|checkPoolHasCode',
        'duration_days'=> 'integer|egt:0',
        'price'        => 'float|egt:0',
        'tokens_price' => 'integer|egt:0',
        'is_recommend' => 'in:0,1',
        'sort'         => 'integer',
        'status'       => 'in:0,1',
        'remark'       => 'max:255',
        'product_id'   => 'max:64',
    ];

    protected $message = [
        'id.require'    => '参数缺失',
        'name.require'=> '请输入套餐名称',
        'type.require'=> '请选择授权类型',
    ];

    public function sceneAdd()
    {
        return $this->only(['name', 'type', 'duration_days', 'price', 'tokens_price', 'is_recommend', 'sort', 'status', 'remark', 'product_id']);
    }

    public function sceneEdit()
    {
        return $this->only(['id', 'name', 'duration_days', 'price', 'tokens_price', 'is_recommend', 'sort', 'status', 'remark', 'product_id'])
            ->append('id', 'checkPlanPoolHasCode');
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function sceneStatus()
    {
        return $this->only(['id', 'status'])
            ->append('id', 'checkPlanPoolHasCode');
    }

    protected function checkId($value)
    {
        if (DeviceAuthPlan::findOrEmpty($value)->isEmpty()) {
            return '套餐不存在';
        }
        return true;
    }

    protected function checkType($value)
    {
        if (!array_key_exists((int)$value, DeviceAuthCodeEnum::getTypeDesc())) {
            return '授权类型错误';
        }
        return true;
    }

    protected function checkTypeUnique($value, $rule, $data)
    {
        if (DeviceAuthPlanLogic::typeExists((int)$value, (int)($data['id'] ?? 0))) {
            return DeviceAuthPlanLogic::TYPE_UNIQUE_MESSAGE;
        }
        return true;
    }

    protected function checkPoolHasCode($value)
    {
        return self::validatePoolHasCode((int)$value);
    }

    protected function checkPlanPoolHasCode($value, $rule, $data)
    {
        $plan = DeviceAuthPlan::findOrEmpty($value);
        if ($plan->isEmpty()) {
            return '套餐不存在';
        }
        $targetStatus = isset($data['status']) ? (int)$data['status'] : (int)$plan['status'];
        if ($targetStatus !== 1) {
            return true;
        }
        return self::validatePoolHasCode((int)$plan['type']);
    }

    /**
     * @return true|string
     */
    private static function validatePoolHasCode(int $type)
    {
        $count = DeviceCdkCode::where([
            ['owner_user_id', '=', 0],
            ['status', '=', DeviceAuthCodeEnum::STATUS_UNUSED],
            ['type', '=', $type],
        ])->count();
        if ($count <= 0) {
            $typeDesc = DeviceAuthCodeEnum::getTypeDesc($type) ?: '该';
            return sprintf('码池中暂无「%s」类型设备CDK，请先在设备CDK池补充', $typeDesc);
        }
        return true;
    }
}
