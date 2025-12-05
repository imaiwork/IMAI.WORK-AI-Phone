<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;
use Jubo\JuLiao\IM\Wx\Proto\CircleCommentDeleteTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 朋友圈评论删除任务
 */
class CircleCommentDeleteTaskHandler extends BaseHandler
{

    /**
     * 朋友圈评论删除任务

     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {

        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::CircleCommentDeleteTask, $content);
    }

    protected function buildRequestContent(array $data) : CircleCommentDeleteTaskMessage
    {
        $request = new CircleCommentDeleteTaskMessage();

        $request->setWeChatId($data['WeChatId']);


        if(isset($data['CircleId'])){
            $request->setCircleId($data['CircleId']);
        }

        if(isset($data['CommentId'])){
            $request->setCommentId($data['CommentId']);
        }

        if(isset($data['PublishTime'])){
            $request->setPublishTime($data['PublishTime']);
        }
        

        if(isset($data['TaskId'])){
            $request->setTaskId($data['TaskId']);
        }


        return $request;
    }
}
