<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\SphPostTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;
use Jubo\JuLiao\IM\Wx\Proto\SphPostTaskMessage\PoiMessage;

/**
 * 视频号 发布消息任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class SphPostTaskHandler extends BaseHandler
{

    /**
     * 处理视频号 发布消息任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::SphPostTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return SphPostTaskMessage
     */
    protected function buildRequestContent(array $data): SphPostTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信号
         *     @type string $Content
         *           发送给那个好友
         *     @type int $MediaType
         *           发送消息类型 Text Picture Voice Video Link File NameCard WeApp Quote Emoji ShiPinHao
         *     @type string $Medias
         *           发送消息内容 文本; 图片，视频，语音，文件url; 链接json; 名片wxid; Emoji的md5或Emoji的详细json
         *     @type string $Cover
         *           其他备注信息，群聊&#64;别人；Quote（引用消息）：引用消息的msgSvrId字符串
         *     @type int|string $TaskId
         *           发送给手机端的时候需要赋值，用于TalkToFriendTaskResultNotice中
         *     @type bool $Poi
         *          是否发送位置
         */

        $request = new SphPostTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['Content'])) {
            $request->setContent($data['Content']);
        }

        if (isset($data['MediaType'])) {
            $request->setMediaType($data['MediaType']);
        }

        if (isset($data['Medias'])) {
            $request->setMedias($data['Medias']);
        }

        if (isset($data['Cover'])) {
            $request->setCover($data['Cover']);
        }

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

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
            if(isset($data['Poi']['PoiId'])){
                $poi->setPoiId($data['Poi']['PoiId']);
            }
            $request->setPoi($poi);
        }

        return $request;
    }
}
