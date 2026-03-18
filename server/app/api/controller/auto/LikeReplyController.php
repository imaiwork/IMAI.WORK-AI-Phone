<?php


namespace app\api\controller\auto;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\auto\LikeReplyValidate;
use app\api\logic\auto\LikeReplyLogic;

/**
 * LikeReplyController
 * @desc 设备自动点赞评论任务
 * @author Qasim
 */
class LikeReplyController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];
    public function update()
    {
        try {
            $params = (new LikeReplyValidate())->post()->goCheck('update');
            $result = LikeReplyLogic::update($params);
            if ($result) {
                return $this->success(data: LikeReplyLogic::getReturnData());
            }
            return $this->fail(LikeReplyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new LikeReplyValidate())->get()->goCheck('detail');
            $result = LikeReplyLogic::detail($params);
            if ($result) {
                return $this->success(data: LikeReplyLogic::getReturnData());
            }
            return $this->fail(LikeReplyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            LikeReplyLogic::autoLikeReplyTaskCron();
        } catch (HttpResponseException $e) {
            print_r($e->__toString());
            die;
        }
    }
}
