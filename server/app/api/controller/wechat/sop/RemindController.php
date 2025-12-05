<?php
declare (strict_types = 1);

namespace app\api\controller\wechat\sop;

use app\api\controller\BaseApiController;
use app\api\logic\wechat\sop\RemindLogic;
use app\api\validate\wechat\sop\RemindValidate;
use think\exception\HttpResponseException;

/**
 * RemindController
 * @desc 跟进提醒管理
 */
class RemindController extends BaseApiController
{
    /**
     * @notes 创建跟进提醒
     */
    public function add()
    {
        try {
            $params = (new RemindValidate())->scene('createRemind')->post()->goCheck();
            $result = RemindLogic::createRemind($params);
            if ($result) {
                return $this->success(data: RemindLogic::getReturnData());
            }
            return $this->fail(RemindLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 更新跟进提醒
     */
    public function update()
    {
    
        try {
            $params = (new RemindValidate())->post()->goCheck('updateRemind');
            $result = RemindLogic::updateRemind($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(RemindLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 删除跟进提醒
     */
    public function delete()
    {
        try {
            $params = (new RemindValidate())->post()->goCheck('deleteRemind');
            $result = RemindLogic::deleteRemind($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(RemindLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

} 