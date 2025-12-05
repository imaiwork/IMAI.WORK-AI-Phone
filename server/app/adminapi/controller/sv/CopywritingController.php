<?php
namespace app\adminapi\controller\sv;
use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\sv\CopywritingLists;
use app\adminapi\logic\sv\SvCopywritingLogic;
use app\adminapi\validate\sv\SvCopywritingValidate;

class CopywritingController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author Lee
     * @date 2025-05-14 16:40:09
     */
    public function lists()
    {  
       return $this->dataLists(new CopywritingLists());
    }

    public function delete()
    {
        try {
            $params = (new SvCopywritingValidate())->post()->goCheck('delete');
            $result = SvCopywritingLogic::deleteSvCopywriting($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvCopywritingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}