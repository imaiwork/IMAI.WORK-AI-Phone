<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvRobot;

/**
 * RobotLogic
 * @desc 机器人
 * @author Qasim
 */
class RobotLogic extends SvBaseLogic
{

    /**
     * @desc 添加机器人
     * @param array $params
     * @return bool
     */
    public static function addRobot(array $params)
    {

        try {

            $params['user_id'] = self::$uid;
            // 添加
            $robot = SvRobot::create($params);
            $params['type'] = 1;
            \app\api\logic\KnowledgeLogic::bind($params, $robot);

            self::$returnData = $robot->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新机器人
     * @param array $params
     * @return bool
     */
    public static function updateRobot(array $params)
    {
        

        try {
            // 检查机器人是否存在
            $robot = SvRobot::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($robot->isEmpty()) {
                self::setError('机器人不存在');
                return false;
            }
            
            
            //挂载知识库
            $params['type'] = 1;
            \app\api\logic\KnowledgeLogic::bind($params, $robot);

            unset($params['index_id'], $params['rerank_min_score'], $params['type']);
            // 更新
            SvRobot::where('id', $robot->id)->update($params);

            self::$returnData = $robot->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取机器人详情
     * @param array $params
     * @return bool
     */
    public static function detailRobot(array $params)
    {
        try {
            // 检查机器人是否存在
            $robot = SvRobot::alias('ai')
            ->field('ai.*')
            ->where('ai.id', $params['id'])
            ->where('ai.user_id', self::$uid)
            ->findOrEmpty();
            if ($robot->isEmpty()) {
                self::setError('机器人不存在');
                return false;
            }
            $result = $robot->toArray();
            $result['index_id'] = \app\common\model\knowledge\KnowledgeBind::where('data_id', $robot->id)->where('type', 1)->value('index_id'); // 知识库id
            // $result['knowledge'] =  \app\common\model\knowledge\KnowledgeBind::alias('b')
            //     ->field('k.index_id, k.name, k.category_id, k.description, k.rerank_min_score, b.data_id, b.type, b.id as bind_id')
            //     ->where('b.data_id', $robot->id)
            //     ->join('knowledge k', 'k.index_id = b.index_id', 'LEFT')
            //     ->where('b.type', 1)
            //     ->limit(1)
            //     ->find();
            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除机器人
     * @param array $params
     * @return bool
     */
    public static function deleteRobot(array $params)
    {
        try {
            // 检查机器人是否存在
            $robot = SvRobot::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($robot->isEmpty()) {
                self::setError('机器人不存在');
                return false;
            }

            $robot->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
