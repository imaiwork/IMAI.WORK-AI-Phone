<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\SvCopywritingContentLogic;
use app\api\validate\sv\SvCopywritingContentValidate;
use app\api\lists\sv\SvCopywritingContentLists;
use think\exception\HttpResponseException;

/**
 * CopywritingContentController
 */
class CopywritingContentController extends BaseApiController
{
    public array $notNeedLogin = [];

    public function lists()
    {
        return $this->dataLists(new SvCopywritingContentLists());
    }

    public function add()
    {
        try {
            $params = (new SvCopywritingContentValidate())->post()->goCheck('add');
            $result = SvCopywritingContentLogic::addSvCopywritingContent($params);
            if ($result) {
                return $this->success(data: SvCopywritingContentLogic::getReturnData());
            }
            return $this->fail(SvCopywritingContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new SvCopywritingContentValidate())->post()->goCheck('update');
            $result = SvCopywritingContentLogic::updateSvCopywritingContent($params);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(SvCopywritingContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new SvCopywritingContentValidate())->get()->goCheck('detail');
            $result = SvCopywritingContentLogic::detailSvCopywritingContent($params);
            if ($result) {
                return $this->data(SvCopywritingContentLogic::getReturnData());
            }
            return $this->fail(SvCopywritingContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new SvCopywritingContentValidate())->post()->goCheck('delete');
            $result = SvCopywritingContentLogic::deleteSvCopywritingContent($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvCopywritingContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}