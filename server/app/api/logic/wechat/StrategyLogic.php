<?php

namespace app\api\logic\wechat;

use app\common\model\wechat\AiWechatReplyStrategy;
use app\common\service\FileService;
use app\common\model\wechat\AiWechatGreetStrategy;
use app\common\model\wechat\AiWechatTagStrategy;
use app\common\model\wechat\AiWechatAcceptFriendStrategy;
use app\common\model\wechat\AiWechatCircleReplyLikeStrategy;
use app\common\model\wechat\AiWechatContact;
use app\common\model\wechat\AiWechatFriendTag;
use app\common\model\wechat\AiWechatTag;
use think\facade\Db;

use Channel\Client as ChannelClient;

/**
 * StrategyLogic
 * @desc 微信机器人策略
 * @author Qasim
 */
class StrategyLogic extends WechatBaseLogic
{

    /**
     * @desc 回复策略
     * @param array $params
     * @return bool
     */
    public static function replyStrategy(array $params)
    {
        try {

            // 查询
            $strategy = AiWechatReplyStrategy::where('user_id', self::$uid)->findOrEmpty();

            $params['stop_keywords'] = explode(';', $params['stop_keywords']);

            if ($strategy->isEmpty()) {

                $params['user_id'] = self::$uid;
                $strategy = AiWechatReplyStrategy::create($params);
            } else {
                $params['stop_keywords'] = json_encode($params['stop_keywords'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                AiWechatReplyStrategy::where('id', $strategy->id)->update($params);
            }

            self::$returnData = $strategy->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 回复策略信息
     * @return bool
     */
    public static function replyInfo()
    {
        try {

            $strategy = AiWechatReplyStrategy::where('user_id', self::$uid)->findOrEmpty();
            if ($strategy->isEmpty()) {
                self::$returnData = [
                    "user_id" => self::$uid,
                    "multiple_type" => 0,
                    "number_chat_rounds" => 3,
                    "voice_enable" => 0,
                    "image_enable" => 0,
                    "image_reply" => "",
                    "stop_enable" => 0,
                    "stop_keywords" => "",
                    "bottom_enable" => 0,
                    "bottom_reply" => ""
                ];
                return true;
            }

            self::$returnData = $strategy->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 打招呼策略
     * @param array $params
     * @return bool
     */
    public static function greetStrategy(array $params)
    {

        try {

            // 查询
            $strategy = AiWechatGreetStrategy::where('user_id', self::$uid)->findOrEmpty();

            // 处理图片
            foreach ($params['greet_content'] as $key => $value) {
                if ($value['type'] == 1) {
                    $params['greet_content'][$key]['content'] = FileService::setFileUrl($value['content']);
                }
            }

            if ($strategy->isEmpty()) {

                $params['user_id'] = self::$uid;
                $strategy = AiWechatGreetStrategy::create($params);
            } else {
                $params['greet_content'] = json_encode($params['greet_content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                AiWechatGreetStrategy::where('id', $strategy->id)->update($params);
            }

            self::$returnData = $strategy->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 打招呼策略信息
     * @return bool
     */
    public static function greetInfo()
    {
        try {

            $strategy = AiWechatGreetStrategy::where('user_id', self::$uid)->findOrEmpty();

            $content = $strategy->greet_content;
            // 处理图片
            foreach ($content as $key => $value) {
                if ($value['type'] == 1) {

                    $content[$key]['content'] = FileService::getFileUrl($value['content']);
                }
            }

            $strategy->greet_content = $content;

            self::$returnData = $strategy->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 标签策略
     * @param array $params
     * @return bool
     */
    public static function tagStrategy(array $params)
    {
        try {

            $params['match_keywords'] = explode(',', $params['match_keywords']);
            $params['user_id'] = self::$uid;
            $strategy = AiWechatTagStrategy::create($params);

            // 检查标签是否存在
            $tag = AiWechatTag::where('user_id', self::$uid)->where('tag_name', $params['tag_name'])->findOrEmpty();
            if ($tag->isEmpty()) {
                AiWechatTag::create([
                    'tag_name' => $params['tag_name'],
                    'user_id' => self::$uid,
                ]);
            }

            self::$returnData = $strategy->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 标签策略信息
     * @return bool
     */
    public static function tagInfo(int $id)
    {
        try {

            $strategy = AiWechatTagStrategy::where('user_id', self::$uid)->where('id', $id)->findOrEmpty();

            if ($strategy->isEmpty()) {
                self::setError('标签策略不存在');
                return false;
            }

            self::$returnData = $strategy->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 标签策略删除
     * @return bool
     */
    public static function tagDelete(int $id)
    {
        try {

            $strategy = AiWechatTagStrategy::where('user_id', self::$uid)->where('id', $id)->findOrEmpty();

            if ($strategy->isEmpty()) {
                self::setError('标签策略不存在');
                return false;
            }

            $strategy->delete();
            self::$returnData = [];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 标签策略更新
     * @return bool
     */
    public static function tagUpdate(array $params)
    {
        try {

            $strategy = AiWechatTagStrategy::where('user_id', self::$uid)->where('id', $params['id'])->findOrEmpty();

            if ($strategy->isEmpty()) {
                self::setError('标签策略不存在');
                return false;
            }

            $params['match_keywords'] = explode(',', $params['match_keywords']);

            // 检查标签是否存在
            $tag = AiWechatTag::where('user_id', self::$uid)->where('tag_name', $params['tag_name'])->findOrEmpty();
            if ($tag->isEmpty()) {
                AiWechatTag::create([
                    'tag_name' => $params['tag_name'],
                    'user_id' => self::$uid,
                ]);
            }

            $strategy->save($params);

            self::$returnData = $strategy->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 标签策略导入
     * @param array $params
     * @return bool
     */
    public static function tagImport(array $params)
    {
        Db::startTrans();
        try {
            $fileContent = file_get_contents($params['file']);

            // 将csv文件内容转换为数组
            $fileContent = explode("\r\n", $fileContent);

            $content = [];

            foreach ($fileContent as $key => $value) {

                if ($key == 0) {
                    continue;
                }

                if ($value) {
                    $content[] = explode(",", $value);
                }
            }

            //插入数据
            foreach ($content as $key => $value) {

                $match_type = $value[0] ?? '';
                $match_mode = $value[1] ?? '';
                $match_keywords = $value[2] ?? '';
                $tag_name = $value[3] ?? '';

                if (!$match_keywords || !($match_keywords = explode('、', $match_keywords)) || !$tag_name) {

                    continue;
                }

                $fields = [
                    'match_type' => $match_type == '精确匹配' ? 1 : 0,
                    'match_mode' => $match_mode == 'AI消息' ? 1 : 0,
                    'match_keywords' => $match_keywords,
                    'tag_name' => $tag_name,
                    'user_id' => self::$uid,
                ];

                AiWechatTagStrategy::create($fields);
                // 检查标签是否存在
                $tag = AiWechatTag::where('user_id', self::$uid)->where('tag_name', $tag_name)->findOrEmpty();
                if ($tag->isEmpty()) {
                    AiWechatTag::create([
                        'tag_name' => $tag_name,
                        'user_id' => self::$uid,
                    ]);
                }
                Db::commit();
            }
            self::$returnData = [];
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 自动通过好友策略
     * @param array $params
     * @return bool
     */
    public static function acceptFriendStrategy(array $params)
    {
        try {
            // 查询
            $strategy = AiWechatAcceptFriendStrategy::where('user_id', self::$uid)->findOrEmpty();
            $params['interval_time'] = $params['add_interval_time'] ?? 0;
            if ($strategy->isEmpty()) {

                $params['user_id'] = self::$uid;
                $strategy = AiWechatAcceptFriendStrategy::create($params);
            } else {
                $strategy->save($params);
            }
            self::$returnData = $strategy->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 自动通过好友策略信息
     * @return bool
     */
    public static function acceptFriendInfo()
    {
        try {

            $strategy = AiWechatAcceptFriendStrategy::where('user_id', self::$uid)->findOrEmpty();

            if ($strategy->isEmpty()) {
                self::$returnData = [];
                return true;
            }
            $strategy->add_interval_time = $strategy->interval_time;
            self::$returnData = $strategy->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 朋友圈点赞策略
     * @param array $params
     * @return bool
     */
    public static function circleReplyLikeStrategy(array $params)
    {
        try {

            // 查询
            $strategy = AiWechatCircleReplyLikeStrategy::where('user_id', self::$uid)->findOrEmpty();
            if ($strategy->isEmpty()) {
                $params['user_id'] = self::$uid;
                $strategy = AiWechatCircleReplyLikeStrategy::create($params);
            } else {
                $strategy->save($params);
            }

            self::$returnData = $strategy->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 朋友圈点赞策略信息
     * @return bool
     */
    public static function circleReplyLikeInfo()
    {
        try {
            $strategy = AiWechatCircleReplyLikeStrategy::where('user_id', self::$uid)->findOrEmpty();
            if ($strategy->isEmpty()) {
                self::$returnData = [
                    "is_enable_reply" => 0,
                    "reply_interval_time" => 0,
                    "reply_numbers" => 0,
                    "reply_prompt" => "",
                    "reply_tag_ids" => [],
                    'reply_robot_id' => '',
                    "is_enable_like" => 0,
                    "like_interval_time" => 0,
                    "like_numbers" => 0,
                    "like_tab_ids" => []
                ];
                return true;
            }
            $strategy->reply_robot_id = $strategy->reply_robot_id == 0 ? '' : $strategy->reply_robot_id;
            self::$returnData = $strategy->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function execCircleReplyLikeStrategy()
    {
        echo '自动微信朋友圈点赞评论';
        $where = [];

        \think\facade\Cache::store('redis')->select(env('redis.WX_SELECT', 8));
        $key = "circle_reply_max_strategy";
        $reply_ids = \think\facade\Cache::store('redis')->sMembers($key);

        $key = "circle_like_max_strategy";
        $like_ids = \think\facade\Cache::store('redis')->sMembers($key);

        $intersect = array_intersect($reply_ids, $like_ids);
        if (!empty($intersect)) {
            $where[] = ['id', 'not in', $intersect];
        }

        $strategys = AiWechatCircleReplyLikeStrategy::where($where)->select()->toArray();
        if (empty($strategys)) {
            return true;
        }

        foreach ($strategys as $key => $strategy) {

            if ($strategy['is_enable_reply'] == 0 && $strategy['is_enable_like'] == 0) {
                continue;
            }

            $wechat = \app\common\model\wechat\AiWechat::where('user_id', $strategy['user_id'])->findOrEmpty();
            if ($wechat->isEmpty()) {
                continue;
            }

            $reply_tag_ids = [];
            if ($strategy['is_enable_reply'] !== 0) {
                $reply_tag_ids = $strategy['reply_tag_ids'];
            }
            $like_tag_ids = [];
            if ($strategy['is_enable_like'] !== 0) {
                $like_tag_ids = $strategy['like_tag_ids'];
            }
            $tagids = array_values(array_unique(array_merge($reply_tag_ids, $like_tag_ids)));
            $friend_tagids = AiWechatFriendTag::where('tag_id', 'in', $tagids)->group('friend_id')->column('friend_id');

            $where = [];
            if (!empty($friend_tagids)) {
                $where[] = ['c.friend_id', 'in', $friend_tagids];
            }
            $friends = AiWechatContact::alias('c')
                ->field('c.*, w.wechat_id, w.device_code')
                ->join('ai_wechat w', 'w.wechat_id = c.wechat_id', 'left')
                ->where('w.user_id', $strategy['user_id'])
                ->where($where)
                ->limit(10)
                ->order('c.update_time', 'asc')
                ->select()->toArray();

            foreach ($friends as $key => $friend) {

                $deviceId = $friend['device_code'];
                // 3. 构建消息发送请求
                $content = \app\common\workerman\wechat\handlers\client\PullFriendCircleTaskHandler::handle([
                    'WeChatId' => $friend['wechat_id'],
                    'FriendId' => $friend['friend_id'],
                    'StartTime' => time(),
                    'RefTime' => strtotime(date('Y-m-d 00:00:00', time())),
                    'RefSnsId' => 0,
                    'Count' => 20,
                    'TaskId' => time(),
                ]);

                // 4. 构建protobuf消息
                $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
                $message->setMsgType($content['MsgType']);
                $any = new \Google\Protobuf\Any();
                $any->pack($content['Content']);
                $message->setContent($any);
                $data = $message->serializeToString();

                // 5. 发送到设备端
                $channel = "socket.{$deviceId}.message";
                ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
                ChannelClient::publish($channel, [
                    'data' => $data
                ]);
                AiWechatContact::where('id', $friend['id'])->update(['update_time' => time()]);
            }
        }
        return false;
    }
}
