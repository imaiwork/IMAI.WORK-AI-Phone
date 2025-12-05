<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, CircleDetailNoticeMessage, CircleInformationMessage};

/**
 * 获取朋友圈详情推送处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class CircleDetailNoticeHandler extends BaseHandler
{
    /**
     * 处理获取朋友圈推送务通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new CircleDetailNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::CircleDetailNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param CircleDetailNoticeMessage $request 处理获取朋友圈任务通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(CircleDetailNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信号
         *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleInformationMessage $Circle
         *           朋友圈信息
         */
         
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信号
         *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleInformationMessage $Circle
         *           朋友圈信息
         *     @var CircleInformationMessage $circle
         */
        $circle = $request->getCircle();
        
        $circle = [
            
            'WeChatId' => $circle->getWeChatId(),
            'CircleId' => (string)$circle->getCircleId(),
            'Content'  => $this->parseCircleContent($circle->getContent()),
            'Likes'     => $this->parseCircleLikes($circle->getLikes()),
            'Comments'  => $this->parseCircleComments($circle->getComments())
        ];
         
        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'CircleId' => $circle,
        ];

        $this->logInfo('Circle detail notice', $content);

        return $content;
    }
}
