<?php


namespace app\adminapi\controller\finance;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\finance\RefundLogLists;
use app\adminapi\lists\finance\RefundRecordLists;
use app\adminapi\logic\finance\RefundLogic;

/**
 * 退款控制器
 * Class RefundController
 * @package app\adminapi\controller\finance
 */
class RefundController extends BaseAdminController
{


    /**
     * @notes 退还统计
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/3/3 12:10
     */
    public function stat()
    {
        $result = RefundLogic::stat();
        return $this->success('', $result);
    }


    /**
     * @notes 退款记录
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/3/1 9:47
     */
    public function record()
    {
        return $this->dataLists(new RefundRecordLists());
    }


    /**
     * @notes 退款日志
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/3/1 9:47
     */
    public function log()
    {
        $recordId = $this->request->get('record_id', 0);
        $result = RefundLogic::refundLog($recordId);
        return $this->success('', $result);
    }
}
