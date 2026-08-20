<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\PublishContentLogic;
use app\api\validate\sv\PublishContentValidate;
use think\exception\HttpResponseException;

/**
 * 今日待发布内容
 */
class PublishContentController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @desc 获取当天待发布内容
     */
    public function lists()
    {
        try {
            $params = (new PublishContentValidate())->get()->goCheck('lists');
            $result = PublishContentLogic::lists($params);
            if ($result) {
                return $this->data(PublishContentLogic::getReturnData());
            }
            return $this->fail(PublishContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 保存当天待发布内容
     */
    public function save()
    {
        try {
            $params = (new PublishContentValidate())->post()->goCheck('save');
            $result = PublishContentLogic::save($params);
            if ($result) {
                return $this->success(data: PublishContentLogic::getReturnData());
            }
            return $this->fail(PublishContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 保存当天待发布内容
     */
    public function update()
    {
        return $this->save();
    }

    /**
     * @desc 重新生成闪剪视频
     */
    public function regenerate()
    {
        try {
            $params = (new PublishContentValidate())->post()->goCheck('regenerate');
            $result = PublishContentLogic::regenerate($params);
            if ($result) {
                return $this->success(data: PublishContentLogic::getReturnData());
            }
            return $this->fail(PublishContentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
