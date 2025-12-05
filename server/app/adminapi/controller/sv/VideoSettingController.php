<?php
namespace app\adminapi\controller\sv;
use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\sv\VideoSettingLists;
use app\adminapi\validate\sv\VideoSettingValidate;
use app\adminapi\logic\sv\VideoSettingLogic;
use think\exception\HttpResponseException;
class VideoSettingController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author Lee
     * @date 2025-05-14 09:40:09
     */
    public function lists()
    {  
       return $this->dataLists(new VideoSettingLists());
    }

      /**
     * @desc 详情
     */
    public function detail()
    {
        try {
            $params = (new VideoSettingValidate())->get()->goCheck('detail');
            $result = VideoSettingLogic::detail($params);
            if ($result) {
                return $this->data(VideoSettingLogic::getReturnData());
            }
            return $this->fail(VideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function del()
    {
        try {
            $params = (new VideoSettingValidate())->post()->goCheck('delete');
            $result = VideoSettingLogic::del($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(VideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}