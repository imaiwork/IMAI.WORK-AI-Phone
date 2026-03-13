<?php

namespace app\api\logic;

use app\common\model\human\HumanVideoTask;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sora\SoraVideoTask;
use app\common\model\storyboard\StoryboardVideoTask;
use app\common\service\FileService;
use think\facade\Db;

/**
 * VideoLogic
 * @desc 视频创作记录
 */
class VideoLogic extends ApiLogic
{
    public static function getVideoCreationRecordLists($params, $userId): array
    {
        // 分页
        $pageNo   = isset($params['page_no']) && $params['page_no'] > 0 ? (int)$params['page_no'] : 1;
        $pageSize = isset($params['page_size']) && $params['page_size'] > 0 ? (int)$params['page_size'] : 10;
        $offset   = ($pageNo - 1) * $pageSize;

        $type            = !empty($params['type']) ? (int)$params['type'] : 0;
        $success         = !empty($params['success']) ? 1 : 0;
        $shanjianWhere   = [];
        $humanWhere      = [];
        $soraWhere       = [];
        $storyboardWhere = [];
        if (in_array($type, [2, 3, 4, 5])) {
            switch ($type) {
                case 2:
                    $shanjianWhere = [
                        ['shanjian_type', '=', 1],
                    ];
                    break;
                case 3:
                    $shanjianWhere = [
                        ['shanjian_type', '=', 2],
                    ];
                    break;
                case 4:
                    $shanjianWhere = [
                        ['shanjian_type', '=', 3],
                    ];
                    break;
                case 5:
                    $shanjianWhere = [
                        ['shanjian_type', '=', 4],
                    ];
            }
        }

        if ($success) {
            $humanWhere      = [
                ['status', '=', 1],
            ];
            $shanjianWhere[] = ['status', '=', 3];
            $soraWhere       = [
                ['status', '=', 3],
            ];
            $storyboardWhere = [
                ['status', '=', 3]
            ];
        }

        // 查询条件
        $where = [['user_id', '=', $userId], ['delete_time', '=', null]];

        $query1 = Db::name('human_video_task')
                    ->field([
                                'id',
                                'name',
                                'task_id',
                                'model_version',
                                'status',
                                'pic',
                                'result_url as video_result_url',
                                'automatic_clip',
                                'clip_status',
                                'clip_result_url',
                                'create_time',
                                'update_time',
                                'remark',
                                "'1' as type",
                                'duration'
                            ])
                    ->where($where)
                    ->where($humanWhere)
                    ->buildSql();

        $query2 = Db::name('shanjian_video_task')
                    ->field([
                                'id',
                                'name',
                                'task_id',
                                "'8' as model_version",
                                'status',
                                'pic',
                                'video_result_url',
                                "'0' as automatic_clip",
                                "'1' as clip_status",
                                'video_result_url as clip_result_url',
                                'create_time',
                                'update_time',
                                'remark',
                                "shanjian_type + 1 as type",
                                'duration'
                            ])
                    ->where($where)
                    ->where($shanjianWhere)
                    ->buildSql();

        $query3 = Db::name('sora_video_task')
                    ->field([
                                'id',
                                'name',
                                'task_id',
                                "'9' as model_version",
                                'status',
                                'pic',
                                'video_result_url',
                                "'0' as automatic_clip",
                                "'1' as clip_status",
                                'video_result_url as clip_result_url',
                                'create_time',
                                'update_time',
                                'remark',
                                "'6' as type",
                                'duration'
                            ])
                    ->where($where)
                    ->where($soraWhere)
                    ->buildSql();

        $query4 = Db::name('storyboard_video_task')
                    ->field([
                                'id',
                                'name',
                                'task_id',
                                "'10' as model_version",
                                'status',
                                'pic',
                                'video_result_url',
                                "'0' as automatic_clip",
                                "'1' as clip_status",
                                'video_result_url as clip_result_url',
                                'create_time',
                                'update_time',
                                'remark',
                                "'7' as type",
                                'duration'
                            ])
                    ->where($where)
                    ->where($storyboardWhere)
                    ->buildSql();

        // 合并子查询sql
        $unionSql = "({$query1} UNION ALL {$query2} UNION ALL {$query3} UNION ALL {$query4}) AS t";
        if ($type == 1) {
            $unionSql = "({$query1}) AS t";
        } else if (in_array($type, [2, 3, 4, 5])) {
            $unionSql = "({$query2}) AS t";
        } else if ($type == 6) {
            $unionSql = "({$query3}) AS t";
        } else if ($type == 7) {
            $unionSql = "({$query4}) AS t";
        }

        $lists = Db::table($unionSql)
                   ->order('create_time', 'desc')  // 按创建时间倒序
                   ->limit($offset, $pageSize)      // 分页：偏移量, 每页条数
                   ->select()
                   ->toArray();

        $video = [];
        foreach ($lists as $key => $item) {
            $video[$key] = [
                'id'               => $item['id'],
                'name'             => $item['name'],
                'task_id'          => $item['task_id'],
                'model_version'    => (int)$item['model_version'],
                'status'           => (int)$item['status'],
                'pic'              => empty($item['pic']) ? FileService::getFileUrl('/static/images/creationRecord.jpg') : FileService::getFileUrl($item['pic']),
                'video_result_url' => empty($item['video_result_url']) ? '' : FileService::getFileUrl($item['video_result_url']),
                'automatic_clip'   => (int)$item['type'] == 1 ? $item['automatic_clip'] : '',
                'clip_status'      => (int)$item['type'] == 1 ? ((int)$item['automatic_clip'] == 0 ? '' : $item['clip_status']) : '',
                'clip_result_url'  => empty($item['clip_result_url']) ? '' : FileService::getFileUrl($item['clip_result_url']),
                'create_time'      => date('Y-m-d H:i:s', $item['create_time']),
                'update_time'      => date('Y-m-d H:i:s', $item['update_time']),
                'remark'           => $item['remark'],
                'type'             => (int)$item['type'],
                'duration'         => $item['duration'],
            ];
        }

        $total = self::getTotalCount($where, $shanjianWhere, $humanWhere, $soraWhere, $storyboardWhere, $type, $success);

        return [
            'count'      => $total,
            'lists'      => $video,
            'page_no'    => $pageNo,
            'page_size'  => $pageSize,
            'total_page' => (int)ceil($total / $pageSize)
        ];
    }

    /**
     * 计算4个表的总记录数
     */
    private static function getTotalCount(array $where, $shanjianWhere, $humanWhere, $soraWhere, $storyboardWhere, $type, $success): int
    {
        if ($type === 0) {
            if ($success) {
                $count1 = Db::name('human_video_task')->where($where)->where($humanWhere)->count();
                $count2 = Db::name('shanjian_video_task')->where($where)->where($shanjianWhere)->count();
                $count3 = Db::name('sora_video_task')->where($where)->where($soraWhere)->count();
                $count4 = Db::name('storyboard_video_task')->where($where)->where($storyboardWhere)->count();
            } else {
                $count1 = Db::name('human_video_task')->where($where)->count();
                $count2 = Db::name('shanjian_video_task')->where($where)->where($shanjianWhere)->count();
                $count3 = Db::name('sora_video_task')->where($where)->count();
                $count4 = Db::name('storyboard_video_task')->where($where)->count();
            }
            return $count1 + $count2 + $count3 + $count4;
        }
        if ($type == 1) {
            if ($success) {
                $count1 = Db::name('human_video_task')->where($where)->where($humanWhere)->count();
            } else {
                $count1 = Db::name('human_video_task')->where($where)->count();
            }
            return $count1;
        }
        if (in_array($type, [2, 3, 4, 5])) {
            $count2 = Db::name('shanjian_video_task')->where($where)->where($shanjianWhere)->count();
            return $count2;
        }
        if ($type == 6) {
            if ($success) {
                $count3 = Db::name('sora_video_task')->where($where)->where($soraWhere)->count();
            } else {
                $count3 = Db::name('sora_video_task')->where($where)->count();
            }
            return $count3;
        }
        if ($type == 7) {
            if ($success) {
                $count4 = Db::name('storyboard_video_task')->where($where)->where($storyboardWhere)->count();
            } else {
                $count4 = Db::name('storyboard_video_task')->where($where)->count();
            }
            return $count4;
        }
        return 0;
    }

    /**
     * 删除视频任务
     * @param array $data
     * @return bool
     */
    public static function creationRecordDelete($data): bool
    {
        try {
            if (!isset($data['type']) || !isset($data['id']) || !isset($data['task_id'])) {
                throw new \Exception('参数错误');
            }
            $type    = $data['type'];
            $id      = $data['id'];
            $task_id = $data['task_id'];
            if ($type == 1) {
                $task = HumanVideoTask::where('id', $id)
                                      ->where('task_id', $task_id)
                                      ->where('user_id', self::$uid)
                                      ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->delete();
            }
            if (in_array($type, [2, 3, 4, 5])) {
                $task = ShanjianVideoTask::where('id', $id)
                                         ->where('task_id', $task_id)
                                         ->where('user_id', self::$uid)
                                         ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->delete();
            }
            if ($type == 6) {
                $task = SoraVideoTask::where('id', $id)
                                     ->where('task_id', $task_id)
                                     ->where('user_id', self::$uid)
                                     ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->delete();
            }
            if ($type == 7) {
                $task = StoryboardVideoTask::where('id', $id)
                                           ->where('task_id', $task_id)
                                           ->where('user_id', self::$uid)
                                           ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function creationRecordUpdate(array $data): bool
    {
        try {
            if (!isset($data['type']) || !isset($data['id']) || !isset($data['task_id']) || !isset($data['name'])) {
                throw new \Exception('参数错误');
            }
            $type    = $data['type'];
            $id      = $data['id'];
            $task_id = $data['task_id'];
            $name    = $data['name'];
            if ($type == 1) {
                $task = HumanVideoTask::where('id', $id)
                                      ->where('task_id', $task_id)
                                      ->where('user_id', self::$uid)
                                      ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->name = $name;
                $task->save();
            }
            if (in_array($type, [2, 3, 4, 5])) {
                $task = ShanjianVideoTask::where('id', $id)
                                         ->where('task_id', $task_id)
                                         ->where('user_id', self::$uid)
                                         ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->name = $name;
                $task->save();
            }
            if ($type == 6) {
                $task = SoraVideoTask::where('id', $id)
                                     ->where('task_id', $task_id)
                                     ->where('user_id', self::$uid)
                                     ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->name = $name;
                $task->save();
            }
            if ($type == 7) {
                $task = StoryboardVideoTask::where('id', $id)
                                           ->where('task_id', $task_id)
                                           ->where('user_id', self::$uid)
                                           ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->name = $name;
                $task->save();
            }
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
}