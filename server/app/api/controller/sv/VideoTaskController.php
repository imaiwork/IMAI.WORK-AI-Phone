<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\SvVideoTaskLogic;
use app\api\validate\sv\SvVideoTaskValidate;
use app\api\lists\sv\SvVideoTaskLists;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;
/**
 * VideoSettingController
 */
class VideoTaskController extends BaseApiController
{

    public array $notNeedLogin = ['notify','clipnotify'];

    public function lists()
    {
        return $this->dataLists(new SvVideoTaskLists());
    }
    /**
     * 异步接收数字人回掉回调
     */
    public function notify(): Json
    {
        try {
            $type = $this->request->param('human_type');
            $modelVersion = $this->request->param('model_version');
            $data = $this->request->all();
            Log::channel('sv')->write('接收数字人参数'.json_encode($data));
            $key = md5(json_encode($data));
            $val =  cache($key);
            if ($val){
                echo 1;die;
            }
            cache($key, 1, 20);

            if (isset($data['data'])) {

                $data = $this->request->param('data');
            }

            switch ($type) {
                case 'avatar':
                    SvVideoTaskLogic::updateAnchor($data, $modelVersion);
                    break;
                case 'voice':
                    SvVideoTaskLogic::updateVoice($data, $modelVersion);
                    break;
                case 'video':
                    SvVideoTaskLogic::updateVideo($data, $modelVersion);
                    break;
                default:
            }

            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('sv')->write('数字人参数'.json_encode($data).'数字人回调失败'.$e->getMessage());
            return $this->success('fail');
        }
    }

    public function delete()
    {
        try {
            $params = (new SvVideoTaskValidate())->post()->goCheck('delete');
            $result = SvVideoTaskLogic::deleteSvVideoTask($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 平台
     */
    public function update()
    {
        try {
            $params = (new SvVideoTaskValidate())->post()->goCheck('update');
            $result = SvVideoTaskLogic::updateSvVideoTask($params);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(SvVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 重试
     * @return \think\response\Json
     */
    public function retry()
    {
        try {
            $params = (new SvVideoTaskValidate())->get()->goCheck('retry');
            $result = SvVideoTaskLogic::retrySvVideoTask($params);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(SvVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function clipnotify(): Json
    {
        $data = $this->request->all();
        try {
            $result = SvVideoTaskLogic::updateClipVideo($data);
            if (!$result){
                return   $this->fail(SvVideoTaskLogic::getError());
            }
            Log::channel('clip')->write('剪辑回调接收数字人参数'.json_encode($data));
            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('clip')->write('剪辑回调接收数字人参数'.json_encode($data).'数字人回调失败'.$e->getMessage());
            return $this->fail('fail');
        }
    }
}