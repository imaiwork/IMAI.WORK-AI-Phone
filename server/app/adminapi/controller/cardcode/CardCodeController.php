<?php

namespace app\adminapi\controller\cardcode;
use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\cardcode\CardCodeLogic;
use app\adminapi\validate\cardcode\CardCodeValidate;
use app\common\enum\CardCodeEnum;


/**
 * 卡密控制器类
 * Class CardCodeController
 * @package app\adminapi\controller\cardcode
 */
class CardCodeController extends BaseAdminController
{

    /**
     * @notes 获取列表
     * @return array
     * @author kb
     * @date 2023/7/10 18:33
     */
    public function getOtherList()
    {
        return [
            'type_list'    => CardCodeEnum::getTypeDesc()
        ];
    }

    /**
     * @notes 列表类
     * @return mixed
     * @author kb
     * @date 2023/7/10 12:09
     */
    public function lists()
    {
        return $this->dataLists();
    }


    /**
     * @notes 添加卡密
     * @return mixed
     * @author kb
     * @date 2023/7/10 15:34
     */
    public function add()
    {
        $post = (new CardCodeValidate())->post()->goCheck('add');
        $result = (new CardCodeLogic())->add($post);
        if(true === $result) {
            return $this->success('添加成功',[],1,1);
        }
        return $this->fail($result);
    }


    /**
     * @notes 获取卡密详情
     * @return mixed
     * @author kb
     * @date 2023/7/10 16:27
     */
    public function detail()
    {
        (new CardCodeValidate())->goCheck('id');
        $detail = (new CardCodeLogic())->detail($this->request->get('id'));
        return $this->success('',$detail);
    }


    /**
     * @notes 删除卡吗
     * @return mixed
     * @author kb
     * @date 2023/7/10 17:32
     */
    public function del()
    {
        (new CardCodeValidate())->post()->goCheck('id');
        (new CardCodeLogic())->del($this->request->post('id'));
        return $this->success('删除成功',[],1,1);
    }


    /**
     * @notes 获取卡密设置
     * @return mixed
     * @author kb
     * @date 2023/7/11 11:50
     */
    public function getConfig()
    {
        $config = (new CardCodeLogic())->getConfig();
        return $this->success('',$config);
    }


    /**
     * @notes 卡密设置
     * @return mixed
     * @author kb
     * @date 2023/7/11 11:56
     */
    public function setConfig()
    {
        (new CardCodeLogic())->setConfig($this->request->post());
        return $this->success('设置成功');

    }


}