<?php

namespace app\api\logic\sv;

use app\common\model\kb\KbRobot;
use app\common\model\sv\SvGreetStrategy;
use app\common\model\sv\SvReplyStrategy;
use app\common\service\FileService;

/**
 * StrategyLogic
 * @desc 机器人策略
 * @author Qasim
 */
class StrategyLogic extends SvBaseLogic
{

    /**
     * @desc 回复策略
     * @param array $params
     * @return bool
     */
    public static function replyStrategy(array $params)
    {
        try {

            $bot = KbRobot::where("id",$params['robot_id'])->where('user_id', self::$uid)->findOrEmpty();
            if ( $bot->isEmpty()) {
                self::setError('机器人不存在');
                return false;
            }
            // 查询
            $strategy = SvReplyStrategy::where('user_id', self::$uid)->where('robot_id',$params['robot_id'])->findOrEmpty();

            $params['stop_keywords'] = explode(';', $params['stop_keywords']);

            if ($strategy->isEmpty()) {

                $params['user_id'] = self::$uid;
                $strategy = SvReplyStrategy::create($params);
            } else {
                $params['stop_keywords'] = json_encode($params['stop_keywords'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $params['working_time'] = !empty($params['working_time']) ? json_encode($params['working_time'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
                SvReplyStrategy::where('id', $strategy->id)->update($params);
            }

            $strategy = $strategy->refresh()->toArray();
            $strategy['working_time'] = $strategy['working_time'] ? json_decode($strategy['working_time'], true) : [];
            self::$returnData = $strategy;
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
    public static function replyInfo($params)
    {
        try {

            $strategy = SvReplyStrategy::where('robot_id',$params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($strategy->isEmpty()) {
                self::$returnData = [
                    "user_id" => self::$uid,
                    "multiple_type" => 0,
                    "robot_id" => 0,
                    "number_chat_rounds" => 3,
                    "voice_enable" => 0,
                    "image_enable" => 0,
                    "image_reply" => "",
                    "stop_enable" => 0,
                    "stop_keywords" => "",
                    "bottom_enable" => 0,
                    "bottom_reply"      => "",
                    "paragraph_enable"  => 0,
                    "working_enable"    => 0,
                    "working_time"      => [],
                    "non_working_reply" => ""
                ];
                return true;
            }
            $strategy->working_time = json_decode($strategy->working_time, true);
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
            $strategy = SvGreetStrategy::where('user_id', self::$uid)->findOrEmpty();

            // 处理图片
            foreach ($params['greet_content'] as $key => $value) {
                if ($value['type'] == 1) {
                    $params['greet_content'][$key]['content'] = FileService::setFileUrl($value['content']);
                }
            }

            if ($strategy->isEmpty()) {

                $params['user_id'] = self::$uid;
                $strategy = SvGreetStrategy::create($params);
            } else {
                $params['greet_content'] = json_encode($params['greet_content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                SvGreetStrategy::where('id', $strategy->id)->update($params);
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

            $strategy = SvGreetStrategy::where('user_id', self::$uid)->findOrEmpty();

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
}
