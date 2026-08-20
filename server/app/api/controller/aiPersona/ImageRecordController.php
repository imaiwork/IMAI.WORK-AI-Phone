<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\ImageRecordLists;
use app\api\logic\aiPersona\ImageRecordLogic;
use app\api\validate\aiPersona\ImageRecordValidate;
use think\exception\HttpResponseException;

/**
 * 人设内容记录 - 自动生成的图片（图文仿写记录）
 */
class ImageRecordController extends BaseApiController
{
    public function lists()
    {
        return $this->dataLists(new ImageRecordLists());
    }

    /**
     * 批量删除图片仿写记录
     */
    public function delete()
    {
        try {
            $params = (new ImageRecordValidate())->post()->goCheck('batchDelete');
            $result = ImageRecordLogic::batchDelete($params['ids']);
            if ($result) {
                return $this->success('删除成功', ImageRecordLogic::getReturnData());
            }
            return $this->fail(ImageRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
