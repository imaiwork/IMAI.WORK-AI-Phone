<?php

namespace app\api\controller;

use app\api\lists\conversation\ConversationLists;
use app\api\validate\GptThreadValidate;
use app\common\logic\GptThreadLogic;
use Exception;
use think\db\concern\ResultOperation;
use think\response\Json;

class ConversationController extends BaseApiController
{
    /**
     * @notes 会话列表
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {
        return $this->dataLists(new ConversationLists());
    }

    /**
     * 添加会话
     * @return Json
     * @author L
     * @data 2024/6/11 12:03
     */
    public function add():Json
    {
        $params = (new GptThreadValidate())->post()->goCheck('add');
        $add = GptThreadLogic::add($params, $this->userId);
        return $add ? $this->success() : $this->fail(GptThreadLogic::getError());
    }


    /**
     * 删除
     * @return Json
     * @throws Exception
     * @author L
     * @data 2024/6/11 15:17
     */
    public function delete(): Json
    {
        $threadId = $this->request->post('id/d');
        if (empty($threadId)) {
            return $this->fail('参数丢失');
        }
        $delete = GptThreadLogic::delete($threadId, $this->userId);
        return $delete ? $this->success() : $this->fail(GptThreadLogic::getError());
    }
}