<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, QwConversPushNoticeMessage, ConversMessage};

/**
 * 企微会话列表推送通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class QwConversPushNoticeHandler extends BaseHandler
{

    /**
     * 处理企微会话列表推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new QwConversPushNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::QwConversPushNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param QwConversPushNoticeMessage $request 企微会话列表推送通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(QwConversPushNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           客服个微全局唯一识别码
         *     @type \Jubo\JuLiao\IM\Wx\Proto\QwConversMessage[]|\Google\Protobuf\Internal\RepeatedField $Convers
         *     @type int $Size
         *     @type int $Count
         *     @type int $Page
         *     @type int|string $TaskId
         */

        // 获取会话列表
        $conversations = [];
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $UserName
         *           全局唯一识别码
         *     @type string $Digest
         *           消息概要显示
         *     @type string $DigestUser
         *           消息发送者id
         *     @type bool $IsSend
         *           最后消息是否自己发送
         *     @type int $MsgCnt
         *           消息条数
         *     @type int $UnreadCnt
         *           未读消息条数
         *     @type int|string $UpdateTime
         *           最后消息的时间
         *     @type bool $IsTop
         *           是否置顶
         *     @type bool $IsSilent
         *           是否消息免打扰
         *     @type string $ShowName
         *          int32 ChatMode = 10; //是否可以发消息
         *     @type string $Avatar
         *           头像
         *     @type int $AtCount
         *     @type string $Remark
         *          string Parent = 14; //上级会话
         *     @type bool $IsUnusual
         *           是否异常
         * @var ConversMessage $conver
         */
        foreach ($request->getConvers() as $conver) {
            $conversations[] = [
                'UserName' => $conver->getUserName(),
                'Digest' => $conver->getDigest(),
                'DigestUser' => $conver->getDigestUser(),
                'IsSend' => $conver->getIsSend(),
                'MsgCnt' => $conver->getMsgCnt(),
                'UnreadCnt' => $conver->getUnreadCnt(),
                'UpdateTime' => $conver->getUpdateTime(),
                'IsTop' => $conver->getIsTop(),
                'IsSilent' => $conver->getIsSilent(),
                'ShowName' => $conver->getShowName(),
                'Avatar' => $conver->getAvatar(),
                'AtCount' => $conver->getAtCount(),
                'Remark' => $conver->getRemark(),
                'IsUnusual' => $conver->getIsUnusual(),
            ];
        }

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Convers' => $conversations,
            'Size' => $request->getSize(),
            'Count' => $request->getCount(),
            'Page' => $request->getPage(),
            'TaskId' => $request->getTaskId(),
        ];

        $this->logInfo('Qw convers push notice', array_merge($content, [
            'Convers' => count($conversations),
        ]));

        return $content;
    }
}
