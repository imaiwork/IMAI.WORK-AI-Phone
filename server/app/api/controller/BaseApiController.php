<?php


namespace app\api\controller;

use app\common\controller\BaseCommonAdminController;
use app\common\service\JsonService;
use think\response\Json;

class BaseApiController extends BaseCommonAdminController
{
    protected int $userId = 0;
    protected int $terminal = 0;
    protected array $userInfo = [];

    public function initialize()
    {
        $this->terminal = intval($this->request->header('terminal', 4));
        if (isset($this->request->userInfo) && $this->request->userInfo) {
            $this->userInfo = $this->request->userInfo;
            $this->userId = $this->request->userInfo['user_id'];
        }
    }

    /**
     * @notes 操作失败
     * @param string $msg
     * @param array $data
     * @param int $code
     * @param int $show
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/27 14:21
     */
    protected function success(string $msg = 'success', array $data = [], int $code = 1, int $show = 0)
    {
        return JsonService::success($msg, $data, $code, $show);
    }

    /**
     * 失败返回
     * @param string $msg 错误信息
     * @param array $data 返回数据
     * @param int $code 错误码
     * @param int $show 是否显示
     * @return Json
     */
    public function fail(string $msg = 'fail', array $data = [], int $code = 0, int $show = 0): Json
    {
        // 如果传入的错误码为0，则尝试从逻辑层获取
        return JsonService::fail($msg, $data, $code, $show);
    }

    /**
     * pc失败返回
     * @param string $msg 错误信息
     * @param array $data 返回数据
     * @param int $code 错误码
     * @param int $show 是否显示
     * @return Json
     */
    public function pc_fail(string $msg = 'fail', array $data = [], int $code = 0, int $show = 0): Json
    {
        // 如果传入的错误码为0，则尝试从逻辑层获取
        return JsonService::pc_fail($msg, $data, $code, $show);
    }
}
