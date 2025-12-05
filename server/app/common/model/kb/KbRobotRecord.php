<?php


namespace app\common\model\kb;

use app\common\enum\ChatRecordEnum;
use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 机器人对话记录模型
 */
class KbRobotRecord extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';

    /**
     * @notes 回复获取器
     * @param $value
     * @return mixed
     * @author ljj
     * @date 2023/6/19 7:43 下午
     */
    public function getAnswerAttr($value): mixed
    {
        $result = json_decode($value,true);
        if (!$result) {
            $result = $value;
        }
        if (!is_array($result)) {
            $result = [$result];
        }
        return $result;
    }

    /**
     * @notes 审核状态
     * @param $value
     * @param $data
     * @return string
     * @author ljj
     * @date 2023/6/21 10:57 上午
     */
    public function getCensorStatusDescAttr($value,$data): string
    {
        return ChatRecordEnum::getCensorStatusDesc($data['censor_status']);
    }

    /**
     * @notes 审核结果
     * @param $value
     * @param $data
     * @return array
     * @author ljj
     * @date 2023/7/5 3:34 下午
     */
    public function getCensorResultDescAttr($value, $data): array
    {
        $key = 0;
        $result = [];
        if ($data['censor_status'] > ChatRecordEnum::CENSOR_STATUS_COMPLIANCE) {
            $censor_result = json_decode($data['censor_result'],true);
            foreach ($censor_result as $censor_result_val) {
                if (isset($censor_result_val['error_msg'])) {
                    $result[] = $censor_result_val['error_msg'];
                    break;
                }
                if (isset($censor_result_val['data']) && !empty($censor_result_val['data'])) {
                    foreach ($censor_result_val['data'] as $val) {
                        if (isset($val['msg']) && !empty($val['msg'])) {
                            $result[$key] = $val['msg'];
                        }
                        if (isset($val['hits']) && !empty($val['hits'])) {
                            foreach ($val['hits'] as $hits_val) {
                                if (isset($hits_val['words'])) {
                                    $result[$key] .= '（敏感词：'.implode('、',$hits_val['words']).'）';
                                }
                            }
                        }
                        $key++;
                    }
                }
            }
        }

        return $result;
    }
}