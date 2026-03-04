<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvCrawlingRecord extends BaseModel {

    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public static function getKeywordStatusStats(array $keywords = [],  $task_id = '')
    {
        $query = self::field([
            'exec_keyword',
            'status',
            'count(*) as record_count',
            // 计算该状态下所有 reg_content 的线索总数（逗号数+1）
            'sum(case 
                when reg_content is null or reg_content = "" then 0 
                else length(reg_content) - length(replace(reg_content, ",", "")) + 1 
            end) as clue_count'
        ])->group('exec_keyword, status');
        if (!empty($keywords)) {
            $query->whereIn('exec_keyword', $keywords);
        }
        if (!empty($task_id)) {
            $query->where('task_id', $task_id);
        }
        $list = $query->select();
        // 重组数据
        $result = [];


        foreach ($list as $item) {
            $keyword = $item['exec_keyword'];
            $status = (int)$item['status'];
            
            if (!isset($result[$keyword])) {
                $result[$keyword] = [
                    'exec_keyword' => $keyword,
                    'number_of_recognitions' => 0,
                    'total_clue_count' => 0,
                    'default' => 0,
                    'effective_clues' => 0,
                    'number_of_invalid_clues' => 0,
                    'number_of_valid_clues_included' => 0,
                ];
            }
            // 累加总数
            $result[$keyword]['number_of_recognitions'] += (int)$item['record_count'];
            $result[$keyword]['total_clue_count'] += (int)$item['clue_count'];

            switch ($status) {
                case 0:
                     $result[$keyword]['default'] =(int)$item['clue_count'];
                    break;
                case 1:
                    $result[$keyword]['effective_clues'] =(int)$item['clue_count'];
                    break;
                case 2:
                    $result[$keyword]['number_of_invalid_clues'] =(int)$item['clue_count'];
                    break;
                case 3: 
                    $result[$keyword]['number_of_valid_clues_included'] =(int)$item['clue_count'];
                    break;
            }
        }

        return array_values($result);
    
    }
}