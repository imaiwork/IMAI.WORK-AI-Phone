<?php

namespace app\api\controller;
use app\api\logic\CardCodeLogic;

/**
 * 卡密控制器类
 * Class CardCodeController
 * @package app\api\controller
 */
class CardCodeController extends BaseApiController
{

    /**
     * @notes 验证卡密
     * @return mixed
     * @author kb
     * @date 2023/7/11 16:30
     */
    public function checkCard()
    {
        $sn = $this->request->get('sn', '');
        // scene=tokens：仅允许算力卡(含代理算力卡)，拒绝会员兑换码
        $scene = (string)$this->request->get('scene', '');
        $result = (new CardCodeLogic())->checkCard($sn, $this->userId, $scene);
        if (is_array($result)) {
            return $this->success('', $result);
        }
        return $this->fail($result);
    }


    /**
     * @notes 使用卡密
     * @return mixed
     * @author kb
     * @date 2023/7/11 16:50
     */
    public function useCard()
    {
        $sn = $this->request->post('sn', '');
        $scene = (string)$this->request->post('scene', '');
        $result = (new CardCodeLogic())->useCard($sn, $this->userId, $scene);
        if (true === $result) {
            return $this->success('兑换成功');
        }
        return $this->fail($result);

    }

}