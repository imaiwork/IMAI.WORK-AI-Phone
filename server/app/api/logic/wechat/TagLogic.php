<?php

namespace app\api\logic\wechat;

use app\common\model\wechat\AiWechatFriendTag;
use app\common\model\wechat\AiWechatTag;
use app\common\model\wechat\AiWechatTagStrategy;
use think\facade\Db;

/**
 * TagLogic
 * @desc 标签
 * @author Qasim
 */
class TagLogic extends WechatBaseLogic
{

    /**
     * @desc 批量删除标签策略
     * @param array $tagIds 标签ID集合
     * @param array $friendIds 好友ID集合
     * @return bool
     */
    public static function tagFriends(array $tagIds, array $friendIds, string $wechatId)
    {
        Db::startTrans();
        try {
            if (!$wechatId){
                self::setError('微信不存在');
                return false;
            }
            $tagIds = array_unique($tagIds);
            $friendIds = array_unique($friendIds);

            // 移除标签
            if (!$tagIds) {

                AiWechatFriendTag::whereIn('friend_id', $friendIds)->where('wechat_id', $wechatId)->delete();
            } // 打标签
            else {
                // 检查标签
                $tagIds = AiWechatTag::whereIn('id', $tagIds)
                    ->where('user_id', self::$uid)
                    ->column('id');

                if (!$tagIds) {
                    self::setError('标签不存在');
                    return false;
                }

                // 移除标签
                AiWechatFriendTag::whereIn('friend_id', $friendIds)
                    ->whereIn('tag_id', $tagIds)
                    ->where('wechat_id', $wechatId)
                    ->delete();

                $tagFirends = [];

                foreach ($tagIds as $tagId) {

                    foreach ($friendIds as $friendId) {

                        $tagFirends[] = [
                            'friend_id' => $friendId,
                            'tag_id'    => $tagId,
                            'wechat_id' => $wechatId,
                        ];
                    }
                }

                if ($tagFirends) {
                    AiWechatFriendTag::insertAll($tagFirends);
                }
            }

            Db::commit();
            self::$returnData = [];
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除标签
     * @param int $id
     * @return bool
     */
    public static function tagDelete(int $id)
    {
        try {
            // 检查旧标签是否存在
            $tag = AiWechatTag::where('user_id', self::$uid)
                ->where('id', $id)
                ->findOrEmpty();

            if ($tag->isEmpty()) {
                self::setError('标签不存在');
                return false;
            }

            // 存在标签好友
            if (AiWechatFriendTag::where('tag_id', $id)->count() > 0) {
                AiWechatFriendTag::where('tag_id', $id)->delete();
            }
            // 存在标签策略
            AiWechatTagStrategy::where('tag_name', $tag->tag_name)->select()->delete();

            $tag->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新标签
     * @param array $params
     * @return bool
     */
    public static function tagUpdate(array $params)
    {
        try {
            // 检查旧标签是否存在
            $tag = AiWechatTag::where('user_id', self::$uid)
                ->where('id', $params['id'])
                ->findOrEmpty();

            if ($tag->isEmpty()) {
                self::setError('标签不存在');
                return false;
            }

            if ($tag->tag_name !== $params['tag_name']) {

                // 检查新标签名是否已存在
                $existingTag = AiWechatTag::where('user_id', self::$uid)
                    ->where('tag_name', $params['tag_name'])
                    ->findOrEmpty();

                if (!$existingTag->isEmpty()) {
                    self::setError('此标签名已存在');
                    return false;
                }
            }

            // 更新标签名
            $tag->tag_name = $params['tag_name'];
            $tag->save();
            self::$returnData = $tag->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     *
     * 给好友打标签
     * @param int $matchModel
     * @param string $message
     * @param int $userId
     * @param string $wechatId
     * @param string $friendId
     * @return bool
     */
    public static function friendTag(int $matchModel, int $userId, string $wechatId, string $friendId, string $message)
    {
        try {
            AiWechatTagStrategy::where('user_id', $userId)
                ->where('match_mode', $matchModel)
                ->select()
                ->each(function ($item) use ($message, $wechatId, $friendId) {
                    $matchKeywords = explode(',', $item->match_keywords) ?? [];

                    foreach ($matchKeywords as $keyword) {

                        $tagName = "";
                        // 模糊匹配
                        if ($item->match_type == 0 && str_contains($message, $keyword)) {
                            // 打标签
                            $tagName = $item->tag_name;
                        } // 精确匹配
                        else if ($item->match_type == 1 && $message === $keyword) {
                            // 打标签
                            $tagName = $item->tag_name;
                        }

                        // 获取标签ID
                        $tagId = AiWechatTag::where('user_id', $item->user_id)
                            ->where('tag_name', $tagName)
                            ->value('id', "");

                        if ($tagName && $tagId && AiWechatFriendTag::where('wechat_id', $wechatId)
                            ->where("friend_id", $friendId)
                            ->where("tag_id", $tagId)
                            ->count() == 0
                        ) {

                            AiWechatFriendTag::create([
                                'wechat_id' => $wechatId,
                                'friend_id' => $friendId,
                                'tag_id'    => $tagId,
                            ]);
                        }
                    }
                });

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @desc 查看好友标签
     * @param array $params
     * @return bool
     */
    public static function friendTagDetail(array $params)
    {
        try {
            // 验证必要参数
            if (!isset($params['wechat_id']) || !isset($params['friend_id'])) {
                throw new \Exception('缺少必要参数: wechat_id或friend_id');
            }
            $friendTags = AiWechatFriendTag::alias('ft')
                ->join('ai_wechat_tag t', 'ft.tag_id = t.id')
                ->field('ft.wechat_id,ft.friend_id,ft.tag_id,t.tag_name')
                ->where('ft.wechat_id', $params['wechat_id'])
                ->where('ft.friend_id', $params['friend_id'])
                ->select();
            if ($friendTags) {
                self::$returnData = $friendTags->toArray();
            } else {
                self::$returnData = [];
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 编辑好友标签
     * @param array $params
     * @return bool
     */
    public static function friendTagUpdate(array $params)
    {
        try {
            Db::startTrans();
            // 验证必要参数
            if (!isset($params['wechat_id']) || !isset($params['friend_id'])) {
                throw new \Exception('缺少必要参数: wechat_id或friend_id');
            }
            $tagIds = (array) $params['tag_id'];
            $friendTagsIds = AiWechatFriendTag::field('tag_id')
                ->where('wechat_id', $params['wechat_id'])
                ->where('friend_id', $params['friend_id'])
                ->column('tag_id');
            $createTagIds = array_diff($tagIds, $friendTagsIds);
            $deleteTagIds = array_diff($friendTagsIds, $tagIds);
            foreach ($createTagIds as $createTagId) {
                $create['wechat_id'] = $params['wechat_id'];
                $create['friend_id'] = $params['friend_id'];
                $create['tag_id'] = $createTagId;
                AiWechatFriendTag::create($create);
            }
            foreach ($deleteTagIds as $deleteTagId) {
                AiWechatFriendTag::where('wechat_id', $params['wechat_id'])
                    ->where('friend_id', $params['friend_id'])
                    ->where('tag_id', $deleteTagId)
                    ->delete();
            }
            self::$returnData = [];
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除好友标签
     * @param array $params
     * @return bool
     */
    public static function friendTagDelete(array $params)
    {
        try {
            // 验证必要参数
            if (!isset($params['wechat_id']) || !isset($params['friend_id'])) {
                throw new \Exception('缺少必要参数: wechat_id或friend_id');
            }

           $sql = AiWechatFriendTag::where('wechat_id', $params['wechat_id']);
            if(is_array($params['friend_id'])){
                $sql =  $sql->whereIn('friend_id', $params['friend_id']);
            }else{
                $sql =  $sql->whereIn('friend_id', $params['friend_id']);
            };

            if(is_array($params['tag_id'])){
                $sql =  $sql->whereIn('tag_id', $params['tag_id']);
            }else{
                $sql =  $sql ->where('tag_id', $params['tag_id']);
            };

            $sql->delete();
            self::$returnData = [];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
