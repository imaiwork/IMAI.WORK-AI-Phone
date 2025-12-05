<?php

namespace app\api\controller\shanjian;

use app\api\controller\BaseApiController;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use app\api\validate\shanjian\ShanjianVideoTaskValidate;
use app\common\service\FileService;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;

/**
 * ShanjianVideoTaskController
 * 闪剪视频任务控制器
 */
class ShanjianVideoTaskController extends BaseApiController
{
    public array $notNeedLogin = ['notify','composite'];


    /**
     * 删除视频任务
     */
    public function delete()
    {
        try {
            $params = $this->request->post();
            $result = ShanjianVideoTaskLogic::delete($params['id']);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(ShanjianVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 获取视频任务详情
     */
    public function detail()
    {
        try {
            $params = (new ShanjianVideoTaskValidate())->get()->goCheck('detail');
            $result = ShanjianVideoTaskLogic::detail($params['id']);
            if ($result) {
                return $this->success(data: ShanjianVideoTaskLogic::getReturnData());
            }
            return $this->fail(ShanjianVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * 异步接收闪剪回调
     */
    public function notify(): Json
    {
        try {
            $data = $this->request->all();
            Log::channel('shanjian')->write('接收闪剪参数'.json_encode($data));
            
            $key = md5(json_encode($data));
            $val = cache($key);
            if ($val) {
                echo 1;
               return false;
            }
            cache($key, 1, 20);

            $result = ShanjianVideoTaskLogic::notify($data);
            if (!$result) {
                return $this->fail(ShanjianVideoTaskLogic::getError());
            }

            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('shanjian')->write('闪剪回调失败'.$e->getMessage());
            return $this->fail('fail');
        }
    }


    public function copywriting(){
        $params = $this->request->post();
        return ShanjianVideoTaskLogic::copywriting($params) ? $this->data(ShanjianVideoTaskLogic::getReturnData()) : $this->fail(ShanjianVideoTaskLogic::getError());
    }


}