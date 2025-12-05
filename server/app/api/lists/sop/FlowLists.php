<?php
declare (strict_types = 1);

namespace app\api\lists\sop;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\sop\AiWechatSopFlow;
use app\common\model\wechat\sop\AiWechatSopSubStage;

/**
 * 流程列表
 * Class FlowLists
 * @package app\api\lists\sop
 */
class FlowLists extends BaseApiDataLists implements ListsSearchInterface
{
    
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['flow_name'],  // 支持流程名称模糊搜索
            '=' => ['status'],          // 支持状态精确搜索
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
       $type = $this->request->get('type');
        // 添加用户ID条件
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        // 添加未删除条件
        $this->searchWhere[] = ['delete_time', 'null', null];
         // 添加用户ID条件
         $this->searchWhere[] = ['status', '=', 1];
       
        $baseQuery = AiWechatSopFlow::field('id,flow_name,status,create_time,update_time')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength);

        if ($type == 1) {
            $result = $baseQuery->select()->toArray();
        } else {
            $result = $baseQuery->select()
                ->each(function ($item) {
                    // 获取该流程下的子阶段数量
                    $subStageCount = AiWechatSopSubStage::where([
                        ['flow_id', '=', $item['id']],
                        ['delete_time', 'null', null]
                    ])->count();
                    
                    // 获取关键阶段和警示阶段
                    $keyStages = AiWechatSopSubStage::where([
                        ['flow_id', '=', $item['id']],
                        ['delete_time', 'null', null],
                    ])
                    ->field('id,sub_stage_name,status,sort')
                    ->order('sort', 'desc')
                    ->select()
                    ->toArray();

                    // 添加额外信息
                    $item['sub_stage_count'] = $subStageCount;
                    $item['key_stages'] = $keyStages;
                    $item['status_text'] = $item['status'] == 1 ? '开启' : '关闭';
                })
                ->toArray();
        }

        return $result;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['delete_time', 'null', null];
        return AiWechatSopFlow::where($this->searchWhere)->count();
    }
} 