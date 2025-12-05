<?php


namespace app\common\logic;

use app\common\enum\user\AccountLogEnum;
use app\common\model\user\UserAccountLog;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;

/**
 * 账户流水记录逻辑层
 * Class AccountLogLogic
 * @package app\common\logic
 */
class AccountLogLogic extends BaseLogic
{

    /**
     * @notes 账户流水记录
     * @param $userId
     * @param $changeType
     * @param $action
     * @param $changeAmount
     * @param string $sourceSn
     * @param string $remark
     * @param array $extra
     * @return UserAccountLog|false|\think\Model
     * @author 段誉
     * @date 2023/2/23 12:03
     */
    public static function add($userId, $changeType, $action, $changeAmount, $status, string $sourceSn = '', string $remark = '', array $extra = [])
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty()) {
            return false;
        }
        $changeObject = AccountLogEnum::getChangeObject($changeType);
        if (!$changeObject) {
            return false;
        }

        switch ($changeObject) {
                // 用户余额
            case AccountLogEnum::UM:
                $model       = new UserAccountLog();
                $left_amount = $user->user_money;
                break;
            case AccountLogEnum::TOKENS:
                $model = new UserTokensLog();
                $left_tokens = $user->tokens;
                break;
                // 其他
        }

        $data = [
            'sn'            => generate_sn(UserAccountLog::class, 'sn', 20),
            'user_id'       => $userId,
            'change_object' => $changeObject,
            'change_type'   => $changeType,
            'action'        => $action,
            'change_amount' => $changeAmount,
            'source_sn'     => $sourceSn,
            'remark'        => $remark,
            'extra'         => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : '',
        ];

        //如果属于系统功能算力扣费，需要记录task_id，且设置source_sn为空
        if (AccountLogEnum::checkCode($changeType)) {
            $data['source_sn'] = '';
            $data['task_id'] = $sourceSn;
        }

        if ($model instanceof UserTokensLog) {
            $data['left_tokens'] = $left_tokens ?? 0;
            $data['status'] = $status ?? 1;
        }
        if ($model instanceof UserAccountLog) {
            $data['left_amount'] = $left_amount ?? 0;
        }
        return $model->create($data);
    }

    /**
     * 进行用户token操作 并记录
     * @param bool $success
     * @param int $userId
     * @param int $changeType
     * @param float $tokens
     * @return void
     * @author L
     * @data 2024/8/2 9:43
     */
    public static function recordUserTokensLog(bool $success, int $userId, int $changeType, float $tokens, $source_sn = '', $extra = []): void
    {
        $remark = AccountLogEnum::getChangeTypeDesc($changeType);
        //运行失败。token恢复
        if (!$success) {
            $remark = str_replace('减少算力', '请求失败恢复算力', $remark);
            User::userTokensChange($userId, $tokens, 'inc');
        }

        AccountLogLogic::add(
            $userId,
            $changeType,
            $success ? AccountLogEnum::DEC : AccountLogEnum::INC,
            $tokens,
            $success ? 1 : 2,
            $source_sn,
            $remark,
            $extra
        );
    }
}
