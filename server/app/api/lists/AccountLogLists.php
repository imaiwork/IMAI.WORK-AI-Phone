<?php


namespace app\api\lists;

use app\common\enum\user\AccountLogEnum;
use app\common\model\user\UserAccountLog;
use app\common\model\user\UserTokensLog;


/**
 * 账户流水列表
 * Class AccountLogLists
 * @package app\shopapi\lists
 */
class AccountLogLists extends BaseApiDataLists
{

    /**
     * @notes 搜索条件
     * @return array
     * @author 段誉
     * @date 2023/2/24 14:43
     */
    public function queryWhere()
    {
        // 指定用户
        $where[] = ['user_id', '=', $this->userId];

        // 用户月明细
        if (isset($this->params['type']) && $this->params['type'] == 'um') {
            $where[] = ['change_type', 'in', AccountLogEnum::getUserMoneyChangeType()];
        }

        // 用户月算力明细
        if (isset($this->params['type']) && $this->params['type'] == 'tokens') {
            $where[] = ['change_type', 'in', AccountLogEnum::getUserTokensChangeType()];
        }

        // 变动类型 
        if (!empty($action = $this->params['action'])) {

            if ($action == 2) {
                $where[] = ['source_sn', '=', ""];
            } else {
                $where[] = ['action', '=', $this->params['action']];
                $where[] = ['source_sn', '<>', ""];
            }
        }

        // 只显示用户购买的订单
        if (!empty($this->params['is_order'])) {
            // $where[] = ['source_sn', '<>', ""];
        }

        // $model = $this->params['type'] == 'tokens' ? (new UserTokensLog()) : (new UserAccountLog());
        // if ($model instanceof UserTokensLog) {
        //     $where[] = ['status', '=', 1];
        // }
        return $where;
    }


    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/24 14:43
     */
    public function lists(): array
    {
        $model = $this->params['type'] == 'tokens' ? (new UserTokensLog()) : (new UserAccountLog());
        $lists = $model::where($this->queryWhere());
        if( $this->params['action'] == 2){
            $lists = $lists ->whereOr(function ($q) {
                $q->where([
                    ['user_id', '=',  $this->userId],
                    ['change_type', '=', AccountLogEnum::TOKENS_DEC_AI_INTERVIEW_CHAT],
                ]);
            });
        }


        $lists = $lists ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->toArray();

        foreach ($lists as &$item) {
            $item['type_desc'] = $item['remark'];
            $symbol = $item['action'] == AccountLogEnum::DEC ? '-' : '+';
            $item['change_amount_desc'] = $symbol . $item['change_amount'];

            if (isset($item['extra'])) {
                $item['extra'] = json_decode($item['extra'], true);
            }
        }

        return $lists;
    }


    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/24 14:44
     */
    public function count(): int
    {
        $model = $this->params['type'] == 'tokens' ? (new UserTokensLog()) : (new UserAccountLog());
        $model = $model::where($this->queryWhere());
        if( $this->params['action'] == 2){
            $model = $model ->whereOr(function ($q) {
                $q->where([
                    ['user_id', '=',  $this->userId],
                    ['change_type', '=', AccountLogEnum::TOKENS_DEC_AI_INTERVIEW_CHAT],
                ]);
            });
        }
        $model = $model->count();
        return $model;
    }
}
