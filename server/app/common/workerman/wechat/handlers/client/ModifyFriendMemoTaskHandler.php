<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\ModifyFriendMemoTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 修改好友备注任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class ModifyFriendMemoTaskHandler extends BaseHandler
{

    /**
     * 处理修改好友备注任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::ModifyFriendMemoTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return ModifyFriendMemoTaskMessage
     */
    protected function buildRequestContent(array $data): ModifyFriendMemoTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *          
         *     @type string $FriendId
         *           联系人微信号
         *     @type string $Memo
         *           备注
         *     @type int|string $TaskId
         *           任务ID
         *     @type string $Desc
         *           描述
         *     @type string $Phone
         *           电话号码
         *     @type int $DelFlag
         *           删除标志  1 删除备注 2 删除描述 4 删除号码；可组合
         */

        $request = new ModifyFriendMemoTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }
        
        $flag = 0;

        if (isset($data['Memo'])) {
            $request->setMemo($data['Memo']);
            
            if ($request->getMemo() === '') {
                $flag += 1;
            }
        }

        if (isset($data['Desc'])) {
            $request->setDesc($data['Desc']);
            
            if ($request->getDesc() === '') {
                $flag += 2;
            }
        }

        if (isset($data['Phone'])) {
            $request->setPhone($data['Phone']);
            
            if ($request->getPhone() === '') {
                $flag += 4;
            }
        }

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }
        
        if($flag != 0){
            
            $request->setDelFlag($flag);
        }

        return $request;
    }
}
