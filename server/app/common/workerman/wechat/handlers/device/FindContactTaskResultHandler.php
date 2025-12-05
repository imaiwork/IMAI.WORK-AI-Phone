<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, FindContactTaskResultNoticeMessage, FriendMessage};

/**
 * 好友添加通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class FindContactTaskResultHandler extends BaseHandler
{

    /**
     * 处理好友添加通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new FindContactTaskResultNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::FindContactTaskResult, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param FindContactTaskResultNoticeMessage $request 好友添加通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(FindContactTaskResultNoticeMessage $request, string $deviceId): array
    {
        

        
        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'SearchText' => $request->getSearchText(),
            'Success' => $request->getSuccess(),
            'IsFriend' => $request->getIsFriend(),
            'UserName' => $request->getUserName(),
            'Alias' => $request->getAlias(),
            'NickName' => $request->getNickName(),
            'Gender' => $request->getGender(),
            'Country' => $request->getCountry(),
            'Province' => $request->getProvince(),
            'City' => $request->getCity(),
            'Avatar' => $request->getAvatar(),
        ];

        $this->logInfo('Find Contact Task Result', $content);

        return $content;
    }
}
