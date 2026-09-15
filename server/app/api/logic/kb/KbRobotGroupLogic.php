<?php


namespace app\api\logic\kb;

use app\common\logic\BaseLogic;
use app\common\model\coze\CozeAgent;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotGroup;
use Exception;

class KbRobotGroupLogic extends BaseLogic
{
    /**
     * @notes 智能体分组详情
     * @param $id
     * @param int $userId
     * @return array
     * @throws Exception
     * @author kb
     */
    public static function detail($id, int $userId): array
    {
        $modelKbRobotGroup = new KbRobotGroup();
        $detail            = $modelKbRobotGroup
            ->where(['id' => $id])
            ->findOrEmpty();
        if ($detail->isEmpty()) {
            self::setError('分组不存在');
            return [];
        }
        return $detail->toArray();
    }

    /**
     * @notes 智能体分组新增
     * @param array $post
     * @param int $userId
     * @return bool|array
     * @author kb
     */
    public static function add(array $post, int $userId): bool|array
    {
        $model = new KbRobotGroup();
        $model->startTrans();
        try {
            $knowGroup = KbRobotGroup::create([
                                                  'user_id' => $userId,
                                                  'name'    => $post['name'],
                                                  'sort'    => $post['sort'] ?? '',
                                              ]);
            $model->commit();
            return ['id' => $knowGroup['id']];
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 智能体分组编辑
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function update(array $post, int $userId): bool
    {
        try {
            $modelKbRobotGroup = new KbRobotGroup();
            $knowGroup         = $modelKbRobotGroup
                ->field(['id,user_id,name,sort,create_time'])
                ->where(['id' => intval($post['id'])])
                ->findOrEmpty()
                ->toArray();
            if (!$knowGroup) {
                throw new Exception('智能体分组不存在了!');
            }
            KbRobotGroup::update([
                                     'name' => $post['name'],
                                     'sort' => $post['sort'] ?? 0,
                                 ], ['id' => intval($post['id'])]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 智能体分组删除
     * @param int $id
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function del(int $id, int $userId): bool
    {
        $modelKbRobotGroup = new KbRobotGroup();
        $modelKbRobotGroup->startTrans();
        try {
            $knowGroup = $modelKbRobotGroup
                ->field(['id,user_id,name'])
                ->where(['id' => $id])
                ->findOrEmpty()
                ->toArray();
            if (!$knowGroup) {
                throw new Exception('智能体分组不存在了!');
            }
            // 删除智能体分组
            KbRobotGroup::destroy($id);
            $modelKbRobotGroup->commit();
            return true;
        } catch (Exception $e) {
            $modelKbRobotGroup->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 智能体添加分组
     * @param array $params
     * @param int $userId
     * @return bool
     * @throws Exception
     * @author kb
     */
    public static function join(array $params, int $userId): bool
    {
        $modelKbRobotGroup = new KbRobotGroup();
        if ($params['type'] == 'system_agent') {
            $model = new KbRobot();
        } else if ($params['type'] == 'coze_agent' || $params['type'] == 'coze_workflow') {
            $model = new CozeAgent();
        } else {
            throw new Exception('参数错误');
        }
        try {
            if ($params['group_id'] !== 0) {
                $knowGroup = $modelKbRobotGroup
                    ->field(['id,user_id,name'])
                    ->where(['id' => $params['group_id']])
                    ->findOrEmpty();
                if ($knowGroup->isEmpty()) {
                    throw new Exception('智能体分组不存在');
                }
            }

            $robot = $model
                ->field(['id,name,group_id'])
                ->where(['id' => $params['robot_id']])
                ->findOrEmpty();
            if ($robot->isEmpty()) {
                throw new Exception('智能体不存在');
            }
            if ($robot->user_id == 0){
                throw new Exception('系统智能体无法移动');
            }

            $robot->group_id = $params['group_id'];
            $robot->save();
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 智能体移除分组
     * @param array $params
     * @param int $userId
     * @return bool
     * @throws Exception
     * @author kb
     */
    public static function remove(array $params, int $userId): bool
    {
        if ($params['type'] == 'system_agent') {
            $model = new KbRobot();
        } else if ($params['type'] == 'coze_agent' || $params['type'] == 'coze_workflow') {
            $model = new CozeAgent();
        } else {
            throw new Exception('参数错误');
        }
        try {
            $robot = $model
                ->field(['id,name,group_id'])
                ->where(['id' => $params['robot_id']])
                ->findOrEmpty();
            if ($robot->isEmpty()) {
                throw new Exception('智能体不存在');
            }

            $robot->group_id = 0;
            $robot->save();
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 智能体分组编辑
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function top(array $post, int $userId): bool
    {
        try {
            $modelKbRobotGroup = new KbRobotGroup();
            $knowGroup         = $modelKbRobotGroup
                ->field(['id,user_id,name,sort,create_time'])
                ->where(['id' => intval($post['id'])])
                ->findOrEmpty()
                ->toArray();
            if (!$knowGroup) {
                throw new Exception('智能体分组不存在了!');
            }

            if (isset($post['type']) && $post['type'] == 1){
                $sort = 999;
            }else{
                $sort = 0;
            }

            KbRobotGroup::update([
                                     'sort'        => $sort,
                                     'update_time' => time(),
                                 ], ['id' => intval($post['id'])]);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}