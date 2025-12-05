<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\SvCopywritingLogic;
use app\api\logic\sv\SvVideoTaskLogic;
use app\api\validate\sv\SvCopywritingValidate;
use app\api\lists\sv\SvCopywritingLists;
use app\api\logic\sv\SvCopywritingContentLogic;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;

/**
 * CopywritingController
 */
class CopywritingController extends BaseApiController
{
    public array $notNeedLogin = ['notify'];

    public function lists()
    {
        return $this->dataLists(new SvCopywritingLists());
    }

    public function add()
    {
        try {
            $params = (new SvCopywritingValidate())->post()->goCheck('add');
            $params['channel'] =  $params['channel'] ?? 2 ;
            $result = SvCopywritingLogic::addSvCopywriting($params);
            if ($result) {
                return $this->success(data: SvCopywritingLogic::getReturnData());
            }
            return $this->fail(SvCopywritingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new SvCopywritingValidate())->get()->goCheck('detail');
            $result = SvCopywritingLogic::detailSvCopywriting($params);
            if ($result) {
                return $this->data(SvCopywritingLogic::getReturnData());
            }
            return $this->fail(SvCopywritingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new SvCopywritingValidate())->post()->goCheck('update');
            $result = SvCopywritingLogic::updateSvCopywriting($params);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(SvCopywritingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
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

    /**
     * 异步接收数字人回掉回调
     */
    public function notify(): Json
    {

        try {
            $data = $this->request->all();

            Log::channel('sv')->write('接收批量生产参数'.json_encode($data));

            $status = $data['MessageBody']['Status'] ?? '';
            
            SvCopywritingLogic::notify($data ,$status);

            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('sv')->write('接收批量生产参数'.json_encode($data).'数字人批量生产失败'.$e->getMessage());
            return $this->success('fail');
        }
    }

    public function addName()
    {
        try {
            $params = (new SvCopywritingValidate())->post()->goCheck('addname');
            $params['channel'] =  $params['channel'] ?? 2 ;
            $result = SvCopywritingLogic::addSvCopywritingName($params);
            if ($result) {
                return $this->success(data: SvCopywritingLogic::getReturnData());
            }
            return $this->fail(SvCopywritingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


}