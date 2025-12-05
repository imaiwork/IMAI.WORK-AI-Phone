<?php


namespace app\common\model\pay;

use app\common\enum\PayEnum;
use app\common\model\BaseModel;
use app\common\service\FileService;


class PayConfig extends BaseModel
{
    protected $name = 'dev_pay_config';

    // 设置json类型字段
    protected $json = ['config'];

    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

    /**
     * @notes 支付图标获取器 - 路径添加域名
     * @param $value
     * @return string
     * @author ljj
     * @date 2021/7/28 2:12 下午
     */
    public function getIconAttr($value)
    {
        return empty($value) ? '' : FileService::getFileUrl($value);
    }

    /**
     * @notes 支付方式名称获取器
     * @param $value
     * @param $data
     * @return string|string[]
     * @author ljj
     * @date 2021/7/31 2:24 下午
     */
    public function getPayWayNameAttr($value, $data)
    {
        return PayEnum::getPayDesc($data['pay_way']);
    }
}
