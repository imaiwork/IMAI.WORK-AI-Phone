<?php


namespace app\common\model\pay;


use app\common\model\BaseModel;
use app\common\service\FileService;


class PayWay extends BaseModel
{
    protected $name = 'dev_pay_way';

    public function getIconAttr($value, $data)
    {
        return FileService::getFileUrl($value);
    }

    /**
     * @notes 支付方式名称获取器
     * @param $value
     * @param $data
     * @return mixed
     * @author ljj
     * @date 2021/7/28 4:02 下午
     */
    public static function getPayWayNameAttr($value, $data)
    {
        return PayConfig::where('id', $data['pay_config_id'])->value('name');
    }

    /**
     * @notes 关联支配配置模型
     * @return \think\model\relation\HasOne
     * @author ljj
     * @date 2021/10/11 3:04 下午
     */
    public function payConfig()
    {
        return $this->hasOne(PayConfig::class, 'id', 'pay_config_id');
    }
}
