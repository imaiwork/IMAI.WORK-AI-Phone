<?php
namespace app\api\validate\distributionAgent;

use app\common\validate\BaseValidate;

class DistributionAgentCardValidate extends BaseValidate
{
    protected $rule = [
        'package_id' => 'require|number',
        'count' => 'require|number|gt:0|max:99', // 每次最多生成 99 张卡
        'card_num' => 'require|number|gt:0|max:99', // 单张卡密可使用次数
        'id' => 'require|number', // record id for delete
    ];

    protected $message = [
        'package_id.require' => '请选择卡密套餐',
        'package_id.number' => '套餐参数异常',
        'count.require' => '请输入生卡数量',
        'count.number' => '生卡数量必须是数字',
        'count.gt' => '生卡数量必须大于0',
        'count.max' => '每次最多生成99张卡密',
        'card_num.require' => '请输入单张卡密可使用次数',
        'card_num.number' => '可使用次数必须是数字',
        'card_num.gt' => '可使用次数必须大于0',
        'card_num.max' => '单张卡密最多可使用99次',
        'id.require' => '请选择卡密',
        'id.number' => '卡密参数异常',
    ];

    public function sceneGenerate()
    {
        return $this->only(['package_id', 'count', 'card_num']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}
