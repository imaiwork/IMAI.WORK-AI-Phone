<?php


namespace app\common\model\refund;


use app\common\enum\RefundEnum;
use app\common\model\auth\Admin;
use app\common\model\BaseModel;


/**
 * 退款日志模型
 * Class RefundLog
 * @package app\common\model\refund
 */
class RefundLog extends BaseModel
{

    /**
     * @notes 操作人描述
     * @param $value
     * @param $data
     * @return mixed
     * @author 段誉
     * @date 2022/12/1 10:55
     */
    public function getHandlerAttr($value, $data)
    {
        return Admin::where('id', $data['handle_id'])->value('name');
    }


    /**
     * @notes 退款状态描述
     * @param $value
     * @param $data
     * @return string|string[]
     * @author 段誉
     * @date 2022/12/1 10:55
     */
    public function getRefundStatusTextAttr($value, $data)
    {
        return RefundEnum::getStatusDesc($data['refund_status']);
    }
}
