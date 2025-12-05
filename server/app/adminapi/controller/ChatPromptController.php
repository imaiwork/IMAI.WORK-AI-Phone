<?php


namespace app\adminapi\controller;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\validate\PromptValidate;
use app\adminapi\logic\ChatPromptLogic;

/**
 * 聊天提示词
 */
class ChatPromptController extends BaseAdminController
{
    /**
     * @notes 获取提示词
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function getPrompt()
    {
        $result = ChatPromptLogic::getPrompt();
        return $this->data($result);
    }


    /**
     * @notes 更新提示词
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function updatePrompt()
    {
        $params = (new PromptValidate())->post()->goCheck('update');
        $result = ChatPromptLogic::updatePrompt($params);
        return $result ? $this->success() : $this->fail(ChatPromptLogic::getError());
    }
}
