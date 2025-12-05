<?php

namespace app\adminapi\validate\setting;

use app\common\validate\BaseValidate;

/**
 * 模型参数验证器
 */
class ModelsValidate extends BaseValidate
{
    protected $rule = [
        'id'       => 'require|number',
        'type'     => 'require|in:1,2,11',
        'channel'  => 'require|max:100',
        'name'     => 'require|max:100',
        'configs'  => 'array',
        'models'   => 'require|array|checkModels',
        'orders'   => 'require|array|checkOrders',
    ];

    protected $message = [
        'id.require'      => 'id参数缺失',
        'id.number'       => 'id参数必须为数字',
        'type.require'    => '请指定模型的类型: [对话模型(1)/向量模型(2)]',
        'channel.require' => '请选择模型的通道',
        'channel.max'     => '模型通道选择异常',
        'name.require'    => '请填写模型的名称',
        'name.max'        => '模型名称不能超出100个字符',
        'configs.require' => '请正确配置模型的参数',
        'configs.array'   => '模型配置参数必须是数组',
        'models.require'  => '请正确配置模型或子模型',
        'models.array'    => '模型或子模型必须是数组'
    ];

    public function sceneId(): ModelsValidate
    {
        return $this->only(['id']);
    }

    public function sceneAdd(): ModelsValidate
    {
        return $this->remove('id',true)
            ->remove('orders', true);
    }

    public function sceneEdit(): ModelsValidate
    {
        return $this->remove('orders',true);
    }

    public function sceneSort(): ModelsValidate
    {
        return $this->only(['orders']);
    }

    public function checkModels($value): bool|string
    {
        foreach ($value as $item) {
            if (empty($item['name'])) {
                return '请填写模型的名称';
            }

            if (strlen($item['name']) > 100) {
                return '模型名称不能超出100个字符';
            }

            if (isset($item['price'])) {
                if (preg_match('/^\d+(.\d{1,4})?/', $item['price'])) {
                    if (str_contains($item['price'], '.') && strlen(substr(strrchr($item['price'], '.'), 1)) > 4) {
                        return "消耗电力值最多可保留4位小数";
                    }
                    if ($item['price'] < 0) {
                        return '消耗电力值设置不能少于0';
                    }
                } else {
                    if ($item['price'] != '') {
                        return "消耗电力值必须为数字";
                    }
                }
            }

            if (isset($item['sort']) and !is_numeric($item['sort'])) {
                return '排序号只支持数字';
            }

            if (isset($item['status']) and !in_array($item['status'], [0, 1])) {
                return '状态值设置异常: [0,1]';
            }
        }
        return true;
    }

    public function checkOrders($value): bool|string
    {
        foreach ($value as $item) {
            if (empty($item['id'])) {
                return 'id参数缺失';
            }

            if (!isset($item['sort'])) {
                return 'sort参数缺失';
            }

            if (!is_numeric($item['id'])) {
                return 'id参数必须为数字';
            }

            if ($item['id'] <= 0) {
                return 'id参数不能少于等于0';
            }

            if (!is_numeric($item['sort'])) {
                return '排序号必须为数字';
            }

            if ($item['sort'] < 0) {
                return '排序号不能少于0';
            }
        }
        return true;
    }
}