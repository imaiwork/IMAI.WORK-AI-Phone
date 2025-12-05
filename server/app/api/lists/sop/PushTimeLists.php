<?php
declare (strict_types = 1);

namespace app\api\lists\sop;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\sop\AiWechatSopPushTime;
use think\Db;
use think\db\Raw;

/**
 * 推送时间列表
 * Class PushTimeLists
 * @package app\api\lists\sop
 */
class PushTimeLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['t.push_id','t.order_day'],         // 支持推送ID精确搜索
        ];
    }

    /**
     * @notes 获取推送时间列表
     * @return array
     */
    public function lists(): array
    {
        // 添加未删除条件
        $this->searchWhere[] = ['t.delete_time', 'null', null];
        // 构建基本查询，关联 iw_ai_wechat_sop_push_content 表
        $results = AiWechatSopPushTime::alias('t')->where($this->searchWhere)
        ->join('ai_wechat_sop_push_content c', 't.id=c.push_time_id') // 使用别名
        ->field('t.order_day,t.push_time,c.content,c.sort,c.push_time_id,c.type')
       ->order(['t.order_day'=>'asc','c.sort'=>'desc']) ->select()->toArray();
        // 执行查询并获取结果
        // 统计每个 push_time 的内容数量
        $groupedResults = [];
        foreach ($results as $item) {
            // 生成 order_day 的唯一键
            if (!isset($groupedResults[$item['order_day']])) {
                $groupedResults[$item['order_day']] = [
                    'order_day' => $item['order_day'],
                    'push_times' => [] // 初始化 push_times 数组
                ];
            }
    
            // 生成 push_time 的唯一键
            $pushTimeKey = $item['push_time'];
            if (!isset($groupedResults[$item['order_day']]['push_times'][$pushTimeKey])) {
                $groupedResults[$item['order_day']]['push_times'][$pushTimeKey] = [
                    'push_time' => $item['push_time'],
                    'content' => $item['content'],
                    'sort' => $item['sort'],
                    'type' => $item['type'],
                    'push_count' => 1 // 初始化计数
                ];
            } else {
                $groupedResults[$item['order_day']]['push_times'][$pushTimeKey]['push_count']++; // 增加计数
            }
        }
        // 将结果转换为数组
        return array_values($groupedResults); // 返回结果
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['t.delete_time', 'null', null];

        // 统计每个 order_day 和 push_time 的推送条数
        return AiWechatSopPushTime::alias('t')->where($this->searchWhere)
        ->join('ai_wechat_sop_push_content c', 't.id=c.push_time_id') // 使用别名
            ->count(); // 返回总数
    }
} 