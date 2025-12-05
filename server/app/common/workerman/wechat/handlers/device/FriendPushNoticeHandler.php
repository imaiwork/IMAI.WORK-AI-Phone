<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, FriendPushNoticeMessage, FriendMessage};

/**
 * 好友推送通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class FriendPushNoticeHandler extends BaseHandler
{

    /**
     * 处理好友推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new FriendPushNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::FriendPushNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param FriendPushNoticeMessage $request 好友推送通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(FriendPushNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type \Jubo\JuLiao\IM\Wx\Proto\FriendMessage[]|\Google\Protobuf\Internal\RepeatedField $Friends
         *           好友信息模型 多个
         *     @type int $Size
         *     @type int $Count
         *     @type int $Page
         *     @type int|string $TaskId
         */

        // 获取好友列表
        $friends = [];
        /**
         * 
         *     Optional. Data for populating the Message object.
         *
         *     @type string $FriendId
         *           wxid
         *     @type string $FriendNo
         *           微信号
         *     @type string $FriendNick
         *           昵称
         *     @type string $Memo
         *           备注
         *     @type int $Gender
         *          性别
         *     @type string $Country
         *          国家 (非必传)
         *     @type string $Province
         *          省份
         *     @type string $City
         *          城市
         *     @type string $Avatar
         *          头像
         *     @type string $Remark
         *           业务备注
         *     @type int $Type
         *           联系人类型，参考 (&512 消息免打扰）
         *     @type string $LabelIds
         *           标签Id
         *     @type string $Phone
         *           手机号
         *     @type string $Desc
         *           描述
         *     @type int $Source
         *           好友来源
         *     @type string $SourceExt
         *          来源扩展信息
         *     @type int|string $CreateTime
         *          加好友时间
         *     @type bool $IsUnusual
         *           是否异常
         * @var FriendMessage $friend
         */
        foreach ($request->getFriends() as $friend) {
            $friends[] = [
                'FriendId' => $friend->getFriendId(),
                'FriendNo' => $friend->getFriendNo(),
                'FriendNick' => $friend->getFriendNick(),
                'Memo' => $friend->getMemo(),
                'Gender' => $friend->getGender(),
                'Country' => $friend->getCountry(),
                'Province' => $friend->getProvince(),
                'City' => $friend->getCity(),
                'Avatar' => $friend->getAvatar(),
                'Remark' => $friend->getRemark(),
                'Type' => $friend->getType(),
                'LabelIds' => $friend->getLabelIds(),
                'Phone' => $friend->getPhone(),
                'Desc' => $friend->getDesc(),
                'Source' => $friend->getSource(),
                'SourceExt' => $friend->getSourceExt(),
                'CreateTime' => $friend->getCreateTime(),
                'IsUnusual' => $friend->getIsUnusual(),
            ];
        }

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Friends' => $friends,
            'Size' => $request->getSize(),
            'Count' => $request->getCount(),
            'Page' => $request->getPage(),
            'TaskId' => $request->getTaskId(),
        ];

        $this->logInfo('Friend push notice', array_merge($content, [
            'Friends' => count($friends),
        ]));

        return $content;
    }
}
