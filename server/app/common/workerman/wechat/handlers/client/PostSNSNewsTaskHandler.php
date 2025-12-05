<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\{
    PostSNSNewsTaskMessage, 
    PostSNSNewsTaskMessage\AttachmentMessage,
    PostSNSNewsTaskMessage\PoiMessage,
    PostSNSNewsTaskMessage\VisibleMessage

};
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 发送朋友圈任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class PostSNSNewsTaskHandler extends BaseHandler
{

    /**
     * 处理发送朋友圈任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::PostSNSNewsTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return PostSNSNewsTaskMessage
     */
    protected function buildRequestContent(array $data): PostSNSNewsTaskMessage
    {
        /**
        *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号 (手机端不需要)
         *     @type string $Content
         *          发布的文案
         *     @type \Jubo\JuLiao\IM\Wx\Proto\PostSNSNewsTaskMessage\AttachmentMessage $Attachment
         *          携带的图片、视频、链接等资源
         *     @type string $Comment
         *           评论
         *     @type int|string $TaskId
         *          任务id 在TaskResult中回传
         *     @type \Jubo\JuLiao\IM\Wx\Proto\PostSNSNewsTaskMessage\VisibleMessage $Visible
         *           可见范围
         *     @type bool $SendSlow
         *           慢速发送，根据文案字数，最多耗时40秒
         *     @type \Jubo\JuLiao\IM\Wx\Proto\PostSNSNewsTaskMessage\PoiMessage $Poi
         *           位置信息
         *     @type string[]|\Google\Protobuf\Internal\RepeatedField $ExtComment
         *          多条评论
         */

        $request = new PostSNSNewsTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['Content'])) {
            $request->setContent($data['Content']);
        }
        
        if (isset($data['Attachment'])) {
            
            $attachment = new AttachmentMessage();
           
            if(isset($data['Attachment']['Type'])){
                
                $attachment->setType($data['Attachment']['Type']);
            }
            
            if(isset($data['Attachment']['Content'])){
                
                $attachment->setContent($data['Attachment']['Content']);
            }
            
            $request->setAttachment($attachment);
        }
        
        if (isset($data['Comment'])) {
            $request->setComment($data['Comment']);
        }
        
        if (isset($data['SendSlow'])) {
            $request->setSendSlow($data['SendSlow']);
        }
        
        // TODO 待实现
        if (isset($data['Poi'])) {
            $poi = new PoiMessage();
            if(isset($data['Poi']['City'])){
                $poi->setCity($data['Poi']['City']);
            }
            if(isset($data['Poi']['Name'])){
                $poi->setName($data['Poi']['Name']);
            }

            if(isset($data['Poi']['Address'])){
                $poi->setAddress($data['Poi']['Address']);
            }

            if(isset($data['Poi']['Lat'])){
                $poi->setLat($data['Poi']['Lat']);
            }

            if(isset($data['Poi']['Lng'])){
                $poi->setLng($data['Poi']['Lng']);
            }
        }
        
        if (isset($data['Visible'])) {
            $visible = new VisibleMessage();
            if(isset($data['visible']['Type'])){
                $visible->setType($data['visible']['Type']);
            }

            if(isset($data['visible']['Labels'])){
                $visible->setLabels($data['visible']['Labels']);
            }

            if(isset($data['visible']['Friends'])){
                $visible->setFriends($data['visible']['Friends']);
            }
        }
        
        if (isset($data['ExtComment'])) {
            $request->setExtComment($data['ExtComment']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}
