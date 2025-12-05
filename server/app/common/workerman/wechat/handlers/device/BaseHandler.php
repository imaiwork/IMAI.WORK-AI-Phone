<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use app\common\workerman\wechat\traits\{CacheTrait, LoggerTrait, DeviceTrait, ResponseTrait};
use Google\Protobuf\Internal\Message;
use app\common\workerman\wechat\exceptions\ResponseException;
use app\common\workerman\wechat\constants\ResponseCode;
use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, CircleCommentMessage, CircleLikeMessage, CircleInformationMessage\CircleContentMessage, CircleInformationMessage\CircleContentMessage\CircleNewsContentMessage, ChatRoomMessage\DisplayNameMessage};
use Google\Protobuf\Internal\RepeatedField;

/**
 * 基础处理器
 * 
 * 
 * @author Qasim
 * @package app\handlers\device
 */
class BaseHandler
{
    use LoggerTrait, DeviceTrait, ResponseTrait, CacheTrait;

    protected const TOKEN_TTL = 3600;

    protected function logInfo(string $title = '', array $context = []): void
    {
        $this->withChannel('wechat_socket')->withLevel('device')->withTitle($title)->withContext($context)->log();
    }

    /**
     * 构建JSON响应
     * 
     * @param int $msgType 消息类型
     * @param array $content 内容
     * @return array
     */
    protected function buildJsonResponse(int $msgType, array $content): array
    {
        $msgType = EnumMsgType::name($msgType);

        return $this->withMsgType($msgType)->withContent($content)->response();
    }


    /**
     * 构建Protobuf响应
     * 
     * @param int $msgType 消息类型
     * @param Message $content 内容
     * @return array
     */
    protected function buildProtobufResponse(int $msgType, Message $content): array
    {
        return $this->withMsgType($msgType)->withContent($content)->build();
    }


    /**
     * 静态调用转实例调用
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $instance = new static();
        return $instance->$name(...$arguments);
    }

    /**
     * 实例方法调用
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }

        throw new ResponseException(ResponseCode::HANDLER_METHOD_NOT_FOUND);
    }


    /**
     * 解析朋友圈内容
     *     Optional. Data for populating the Message object.
     *
     *     @type string $Text
     *           文本描述
     *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleInformationMessage\CircleContentMessage\CircleNewsContentMessage[]|\Google\Protobuf\Internal\RepeatedField $Images
     *           图片列表
     *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleInformationMessage\CircleContentMessage\CircleNewsContentMessage $Link
     *           链接、分享
     *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleInformationMessage\CircleContentMessage\CircleNewsContentMessage $Video
     *           视频
     *     @type string $Ext
     * 
     * @param CircleContentMessage $content
     * @return array
     */
    protected function parseCircleContent(CircleContentMessage $content): array
    {

        // 图片
        $images = [];

        /**
         * 
         *     Optional. Data for populating the Message object.
         *
         *     @type string $ThumbImg
         *           缩略图
         *     @type string $Url
         *           链接地址 视频地址等
         *     @type string $Description
         *           标题等
         *     @type string $mediaId
         *           媒体id
         * @var CircleNewsContentMessage $image
         */
        foreach ($content->getImages() as $image) {
            $images[] = [
                'ThumbImg' => $image->getThumbImg(),
                'Url' => $image->getUrl(),
                'Description' => $image->getDescription(),
                'MediaId' => $image->getMediaId(),
            ];
        }
        
        $linkInfo = $content->getLink();
        
        if($linkInfo){
            // 链接
            $link = [
                'Url' => $linkInfo->getUrl(),
                'Description' => $linkInfo->getDescription(),
                'MediaId' => $linkInfo->getMediaId(),
            ];
        }else{
            $link = [];
        }
        
        
        $videoInfo = $content->getVideo();
        
        if($videoInfo){
            // 视频
            $video = [
                'Url' => $videoInfo->getUrl(),
                'MediaId' => $videoInfo->getMediaId(),
            ];
        }else{
            
            $video = [];
        }

        return [
            'Text' => $content->getText(),
            'Images' => $images,
            'Link' => $link,
            'Video' => $video,
            'Ext' => $content->getExt(),
        ];
    }

    /**
     * 解析朋友圈点赞好友
     *     Optional. Data for populating the Message object.
     *
     *     @type string $FriendId
     *     @type int|string $PublishTime
     *     @type string $NickName
     *     @type int|string $CircleId
     *     @type int $Read
     * 
     * @param CircleLikeMessage[]|RepeatedField $likes
     * @return array
     */
    protected function parseCircleLikes(CircleLikeMessage|RepeatedField $likes): array
    {
        $content = [];

        foreach ($likes as $like) {
            $content[] = [
                'FriendId' => $like->getFriendId(),
                'PublishTime' => $like->getPublishTime(),
                'NickName' => $like->getNickName(),
                'CircleId' => (string)$like->getCircleId(),
                'Read' => $like->getRead(),
            ];
        }

        return $content;
    }

    /**
     * 解析朋友圈评论好友
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $CommentId
     *           微信本地数据库的id
     *     @type int|string $ReplyCommentId
     *           回复的微信本地数据库的id
     *     @type string $Content
     *           评论内容
     *     @type string $FromWeChatId
     *           评论人的微信号
     *     @type string $ToWeChatId
     *           评论对象的微信号
     *     @type int|string $PublishTime
     *           评论时间
     *     @type string $FromName
     *     @type string $ToName
     *     @type int|string $CircleId
     *     @type int $Read
     * 
     * @param CircleCommentMessage[]|RepeatedField $comments
     * @return array
     */
    protected function parseCircleComments(CircleCommentMessage|RepeatedField $comments): array
    {
        $content = [];

        foreach ($comments as $comment) {
            $content[] = [
                'CommentId' => $comment->getCommentId(),
                'ReplyCommentId' => $comment->getReplyCommentId(),
                'Content' => $comment->getContent(),
                'FromWeChatId' => $comment->getFromWeChatId(),
                'ToWeChatId' => $comment->getToWeChatId(),
                'PublishTime' => $comment->getPublishTime(),
                'FromName' => $comment->getFromName(),
                'ToName' => $comment->getToName(),
                'CircleId' => (string)$comment->getCircleId(),
                'Read' => $comment->getRead(),
            ];
        }

        return $content;
    }


    /**
     * 解析群聊成员显示名
     *     Optional. Data for populating the Message object.
     *
     *     @type string $UserName
     *           群成员
     *     @type string $ShowName
     *           群显示名
     *     @type string $Inviter
     *           邀请者
     *     @type int $Flag
     *           &2048 群管理员，其他未知
     * 
     * @param DisplayNameMessage[]|RepeatedField $names
     * @return array
     */
    protected function parseChatRoomShowNameList(DisplayNameMessage|RepeatedField $names): array
    {
        $content = [];

        foreach ($names as $name) {
            $content[] = [
                'UserName' => $name->getUserName(),
                'ShowName' => $name->getShowName(),
                'Inviter' => $name->getInviter(),
                'Flag' => $name->getFlag(),
            ];
        }

        return $content;
    }
}
