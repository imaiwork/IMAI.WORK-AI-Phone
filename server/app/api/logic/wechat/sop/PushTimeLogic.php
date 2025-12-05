<?php
declare (strict_types = 1);

namespace app\api\logic\wechat\sop;

use app\api\logic\ApiLogic;
use app\common\model\wechat\sop\AiWechatSopPush;
use app\common\model\wechat\sop\AiWechatSopPushTime;

class PushTimeLogic extends ApiLogic
{
    /**
     * 创建推送时间
     */
    public static function createPushTime(array $params,string $push_id): bool
    {
        try {
            $push = AiWechatSopPush::where(['id' => $push_id, 'user_id' => self::$uid])->find();
            if (!$push) {
                throw new \Exception('推送任务不存在');
            }

            // 检查同一 push_id, order_day, push_time 是否已存在
            $existingPushTime = AiWechatSopPushTime::where('push_id', $push_id)
                ->where('order_day', $params['order_day'])
                ->where('push_time', $params['push_time'])
                ->where('user_id', self::$uid)
                ->find();

            if ($existingPushTime) {
                throw new \Exception('排序天数和推送时间不能重复');
            }
         
            // 创建推送时间记录
            $params['user_id'] = self::$uid;
            $params['push_day'] = $push['push_day'];
            $params['push_real_day'] = date('Y-m-d',strtotime($push['push_day']) + $params['order_day'] * 86400);
            $params['push_id'] = $push_id;
            $pushTime = AiWechatSopPushTime::create($params);
            if (!$pushTime) {
                throw new \Exception('创建推送时间失败');
            }
            $all = AiWechatSopPushTime::where([
                'push_id' => $push_id,
                'user_id' => self::$uid
              ])
              ->count('DISTINCT order_day'); // 统计数量
              
            $push->all_day = $all;
            $push->update_time = time();
            $push->save();
            self::$returnData = $pushTime->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新推送时间
     */
    public static function updatePushTime(array $params): bool
    {
        try {
            // 检查推送时间是否存在
            $pushTime = AiWechatSopPushTime::where(['id' => $params['id'], 'user_id' =>self::$uid])->find();
            if (!$pushTime) {
                throw new \Exception('记录不存在');
            }

            if ($pushTime->order_day == $params['order_day'] && $pushTime->push_time == $params['push_time']) {
                throw new \Exception('排序天数和推送时间的组合不能重复');
            }
           
            $pushTime->order_day = $params['order_day'];
            $pushTime->push_time = $params['push_time'];
            // 更新推送时间记录
            $pushTime->save();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除推送时间
     */
    public static function deletePushTime(array $params): bool
    {
        try {
            $pushTime = AiWechatSopPushTime::where(['id' => $params['id'], 'user_id' => self::$uid])->find();
            if (!$pushTime) {
                throw new \Exception('记录不存在');
            }
            $pushTime->delete_time = time();
            $pushTime->save();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
 /**
     * @notes 获取推送时间列表
     * @param int $push_id
     * @return array
     */
    public function getPushTimeList(string $push_id): array
    {
     // 检查 push_id 是否有效
        if ($push_id <= 0) {
            throw new \Exception('Invalid push_id');
        }

        // 添加未删除条件
        $searchWhere = [['push_id', '=', $push_id]];

        // 构建基本查询
        $query = AiWechatSopPushTime::where($searchWhere)
            ->order('order_day', 'asc') // 按 order_day 排序
            ->order('id', 'desc');

        // 执行查询并获取结果
        $results = $query->select()->toArray();

        if (empty($results)){
            return [];
        }
        // 组织成多维数组
        $groupedResults = [];
        $pushDay = '';
        foreach ($results as $item) {
            $orderDay = $item['order_day'];
            if (!isset($groupedResults[$orderDay])) {
                $pushDay = $item['push_day'];
                $groupedResults[$orderDay] =
                 [
                  
                    'id' => $item['id'], 
                    'push_id' => $item['push_id'], 
                    'push_day' => $item['push_day'], 
                    'order_day' => $item['order_day'], 
                    'push_time' => $item['push_time'], 
                    'timeList' => []
                ]; // 初始化数组
            }
            $groupedResults[$orderDay]['timeList'][] = $item; // 将时间数据放入 timeList
        }

        // 获取最大 orderDay
        $maxOrderDay = max(array_keys($groupedResults));

       // 构建最终结果，确保从 0 到 maxOrderDay 都有项
       $finalResults = [];
       for ($i = 0; $i <= $maxOrderDay; $i++) {
        // 计算实际日期
            $calculatedDate = date('Y-m-d', strtotime("{$pushDay} +{$i} days"));

            if (isset($groupedResults[$i])) {
                $groupedResults[$i]['push_day'] = $calculatedDate; // 直接赋值
                $finalResults[] = $groupedResults[$i];
            } else {
                $finalResults[] = [
                    'order_day' => $i,
                    'timeList' => [], 
                    'push_day' => $calculatedDate // 使用计算出的日期
                ]; // 不存在的 timeList 显示为空数组
            }
       }

        return $finalResults; // 返回最终结果
    }
} 