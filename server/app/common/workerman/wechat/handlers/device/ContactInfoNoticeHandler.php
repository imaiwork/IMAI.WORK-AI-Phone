<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ContactInfoNoticeMessage, StrangerMessage};

/**
 * 群成员信息通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class ContactInfoNoticeHandler extends BaseHandler
{

    /**
     * 处理群成员信息通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new ContactInfoNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::ContactLabelInfoNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param ContactInfoNoticeMessage $request 群成员信息通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(ContactInfoNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *          
         *     @type \Jubo\JuLiao\IM\Wx\Proto\StrangerMessage $Contact
         *           联系人信息
         *     @type int|string $TaskId
         *     @type bool $Success
         *           是否成功
         *     @type string $ErrMsg
         *           错误内容描述 Success = true 忽略
         */

        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $Wxid
         *           微信号
         *     @type string $Alias
         *           微信别名
         *     @type string $Nickname
         *           好友的微信昵称
         *     @type string $Avatar
         *          微信头像 
         *     @type int $Type
         *           联系人类型，判断是否为好友
         *     @type int $Gender
         *          性别
         *     @type string $Country
         *          国家 (非必传)
         *     @type string $Province
         *          省份
         *     @type string $City
         *          城市
         *     @type string $Memo
         *           机主的备注
         *     @type string $Signature
         *          签名
         * @var StrangerMessage $contact
         */
        $contact = $request->getContact();
        $contactInfo = [];
        if ($request->getSuccess()) {

            $contactInfo = [
                'Wxid' => $contact->getWxid(),
                'Alias' => $contact->getAlias(),
                'Nickname' => $contact->getNickname(),
                'Avatar' => $contact->getAvatar(),
                'Gender' => $contact->getGender(),
                'Country' => $contact->getCountry(),
                'Province' => $contact->getProvince(),
                'City' => $contact->getCity(),
                'Memo' => $contact->getMemo(),
                'Signature' => $contact->getSignature(),
            ];
        }

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'TaskId'   => $request->getTaskId(),
            'Success'   => $request->getSuccess(),
            'ErrMsg'    => $request->getErrMsg(),
            'ContactInfo' => $contactInfo
        ];

        $this->logInfo('Contact info notice', $content);

        return $content;
    }
}
