<?php


namespace app\common\model\refund;


use app\common\enum\RefundEnum;
use app\common\model\BaseModel;


/**
 * 退款记录模型
 * Class RefundRecord
 * @package app\common\model\refund
 */
class RefundRecord extends BaseModel
{

    /**
     * @notes 退款类型描述
     * @param $value
     * @param $data
     * @return string|string[]
     * @author 段誉
     * @date 2022/12/1 10:41
     */
    public function getRefundTypeTextAttr($value, $data)
    {
        return RefundEnum::getTypeDesc($data['refund_type']);
    }


    /**
     * @notes 退款状态描述
     * @param $value
     * @param $data
     * @return string|string[]
     * @author 段誉
     * @date 2022/12/1 10:44
     */
    public function getRefundStatusTextAttr($value, $data)
    {
        return RefundEnum::getStatusDesc($data['refund_status']);
    }


    /**
     * @notes 退款方式描述
     * @param $value
     * @param $data
     * @return string|string[]
     * @author 段誉
     * @date 2022/12/6 11:08
     */
    public function getRefundWayTextAttr($value, $data)
    {
        return RefundEnum::getWayDesc($data['refund_way']);
    }
}
