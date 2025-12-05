<?php

namespace app\api\logic\wechat;

use app\common\model\wechat\AiWechatRobotKeyword;
use app\common\model\wechat\AiWechatRobot;
use app\common\service\FileService;

/**
 * RobotKeywordLogic
 * @desc 微信机器人关键词
 * @author Qasim
 */
class RobotKeywordLogic extends WechatBaseLogic
{

    /**
     * @desc 添加机器人关键词
     * @param array $params
     * @return bool
     */
    public static function addRobotKeyword(array $params)
    {
        try {
            $params['user_id'] = self::$uid;

            // 检查机器人是否存在
            $robot = AiWechatRobot::where('id', $params['robot_id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($robot->isEmpty()) {
                self::setError('机器人不存在');
                return false;
            }

            // 检查关键词是否已添加
            $keyword = AiWechatRobotKeyword::where('user_id', self::$uid)->where('robot_id', $params['robot_id'])->where('keyword', $params['keyword'])->findOrEmpty();

            if (!$keyword->isEmpty()) {
                self::setError('关键词已添加');
                return false;
            }

            // 处理图片
            foreach ($params['reply'] as $key => $value) {
                if ($value['type'] == 1) {
                    $params['reply'][$key]['content'] = FileService::setFileUrl($value['content']);
                }
            }

            // 添加
            $robot = AiWechatRobotKeyword::create($params);

            self::$returnData = $robot->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新机器人关键词
     * @param array $params
     * @return bool
     */
    public static function updateRobotKeyword(array $params)
    {

        try {
            // 检查机器人是否存在
            $robot = AiWechatRobotKeyword::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($robot->isEmpty()) {
                self::setError('关键词不存在');
                return false;
            }

            // 处理图片
            foreach ($params['reply'] as $key => $value) {
                if ($value['type'] == 1) {
                    $params['reply'][$key]['content'] = FileService::setFileUrl($value['content']);
                }
            }

            $params['reply'] = json_encode($params['reply'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // 更新
            AiWechatRobotKeyword::where('id', $robot->id)->update($params);

            self::$returnData = $robot->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除机器人关键词
     * @param array $params
     * @return bool
     */
    public static function deleteRobotKeyword(array $params)
    {
        try {
            // 检查机器人关键词是否存在
            $robot = AiWechatRobotKeyword::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($robot->isEmpty()) {
                self::setError('关键词不存在');
                return false;
            }

            $robot->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 导入机器人关键词
     * @param array $params
     * @return bool
     */
    public static function importRobotKeyword(array $params)
    {
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
                $keyword = $value[1] ?? '';
                $reply = $value[2] ?? '';

                $encoding = array('UTF-8', 'ASCII', 'GB2312', 'GBK');
                $keyword = mb_convert_encoding($keyword, "UTF-8", mb_detect_encoding($keyword, $encoding));
                $reply = mb_convert_encoding($reply, "UTF-8", mb_detect_encoding($reply, $encoding));
                
                if (!$keyword || !$reply) {
                    continue;
                }
            

                $fields = [
                    'match_type' => $match_type == '精确匹配' ? 1 : 0,
                    'keyword' => $keyword,
                    'reply' => [
                        [
                            'type' => 0,
                            'content' => $reply
                        ]
                    ],
                    'user_id' => self::$uid,
                    'robot_id' => $params['robot_id']
                ];

                // 是否存在
                $keyword = AiWechatRobotKeyword::where('user_id', self::$uid)->where('robot_id', $fields['robot_id'])->where('keyword', $fields['keyword'])->findOrEmpty();
                if (!$keyword->isEmpty()) {
                    AiWechatRobotKeyword::where('id', $keyword->id)->update($fields);
                } else {
                    AiWechatRobotKeyword::create($fields);
                }
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
