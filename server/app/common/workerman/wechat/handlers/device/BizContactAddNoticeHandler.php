<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, BizContactAddNoticeMessage, BizContactMessage};

/**
 * 公众号新增消息推送通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class BizContactAddNoticeHandler extends BaseHandler
{
    /**
     * 处理公众号推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new BizContactAddNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::BizContactAddNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param BizContactAddNoticeMessage $request 公众号新增消息推送通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(BizContactAddNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type \Jubo\JuLiao\IM\Wx\Proto\BizContactMessage $Contact
         */

        $contact = $request->getContact();

        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $Username
         *           id
         *     @type string $Alias
         *           微信号
         *     @type string $Nickname
         *           昵称
         *     @type string $Avatar
         *          头像 
         *     @type string $Icon
         *           公众号图标
         *     @type string $Desc
         *           描述
         *     @type string $Company
         *           注册公司
         * @var BizContactMessage $contact
         */
        $contactInfo = [
            'Username' => $contact->getUsername(),
            'Alias' => $contact->getAlias(),
            'Nickname' => $contact->getNickname(),
            'Avatar' => $contact->getAvatar(),
            'Icon' => $contact->getIcon(),
            'Desc' => $contact->getDesc(),
            'Company' => $contact->getCompany(),
        ];

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Contact' => $contactInfo,
        ];

        $this->logInfo('Biz contact add notice', $content);

        return $content;
    }
}
