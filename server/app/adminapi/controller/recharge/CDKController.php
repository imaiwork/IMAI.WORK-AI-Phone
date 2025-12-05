<?php


namespace app\adminapi\controller\recharge;

use app\adminapi\controller\BaseAdminController;
use app\common\service\ConfigService;

/**
 * CDK控制器
 * Class CDKController
 * @package app\adminapi\controller\recharge
 */
class CDKController extends BaseAdminController
{

    /**
     * @notes CDK兑换算力
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/12/28 16:48
     */
    public function cdkExchangeTokens()
    {
        try {
            $cdkey = $this->request->post('cdkey');

            $response = \app\common\service\ToolsService::DataCenter()->tokensCdk([
                'cdkey' => $cdkey,
            ]);

            if (isset($response['code']) && $response['code'] !== 10000) {

                return $this->fail('兑换失败');
            }

            return $this->success('兑换成功', []);
        } catch (\think\exception\HttpResponseException $e) {

            return $this->fail($e->getResponse()->getData()['msg'] ?? '更新失败');
        } catch (\Exception $e) {

            return $this->fail('更新失败');
        }
    }

    /**
     * @notes CDK替换授权码
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/12/28 16:48
     */
    public function cdkReplaceAuth()
    {

        try {
            $key = ConfigService::get('model', 'key');

            $cdkey = $this->request->post('cdkey', $key['api_key']);

            if ($key['api_key'] != $cdkey) {


                $response = \app\common\service\ToolsService::Auth()->cdkReplace([
                    'cdkey' => $cdkey,
                ]);

                if (isset($response['code']) && $response['code'] !== 10000) {

                    return $this->fail('更新失败');
                }
            }

            //更新数据库
            ConfigService::set('model', 'key', [
                'api_key' => $cdkey,
            ]);

            return $this->success('更新成功', []);
        } catch (\think\exception\HttpResponseException $e) {

            return $this->fail($e->getResponse()->getData()['msg'] ?? '更新失败');
        } catch (\Exception $e) {

            return $this->fail('更新失败');
        }
    }
}
