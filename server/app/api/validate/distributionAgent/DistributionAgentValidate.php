<?php
namespace app\api\validate\distributionAgent;

use app\common\model\distribution\DistributionAgent;
use app\common\model\user\User;
use app\common\validate\BaseValidate;

class DistributionAgentValidate extends BaseValidate
{
    protected $rule = [
        'user_id' => 'require|checkDownline',
        'level' => 'require|in:0,1,2,3',
        'tokens' => 'require|integer|gt:0|checkTokens',
        'qr_code' => 'require|max:255',
    ];

    protected $message = [
        'user_id.require' => '请选择下级用户',
        'level.require' => '请选择代理等级',
        'level.in' => '代理等级错误',
        'tokens.require' => '请输入赠送额度',
        'tokens.integer' => '赠送额度必须为整数',
        'tokens.gt' => '赠送额度必须大于0',
        'qr_code.require' => '请上传二维码',
        'qr_code.max' => '二维码链接过长',
    ];

    public function sceneSetLevel()
    {
        return $this->only(['user_id', 'level'])->append('level', 'checkLevelRule');
    }

    public function sceneRemoveSub()
    {
        return $this->only(['user_id']);
    }

    public function sceneTransfer()
    {
        return $this->only(['user_id', 'tokens']);
    }

    public function sceneSetQrCode()
    {
        return $this->only(['qr_code']);
    }

    protected function checkDownline($value, $rule, $data)
    {
        $currentUserId = request()->userId;
        $agent = DistributionAgent::where('user_id', $value)->where('parent_id', $currentUserId)->findOrEmpty();
        if ($agent->isEmpty()) {
            return '不是您的直属下线，无法操作';
        }
        return true;
    }

    protected function checkLevelRule($value, $rule, $data)
    {
        $currentUserId = request()->userId;
        $currentUserAgent = DistributionAgent::where('user_id', $currentUserId)->findOrEmpty();
        if ($currentUserAgent->isEmpty() || $currentUserAgent->status == 0) {
            return '您当前无权限操作';
        }
        $myLevel = $currentUserAgent->level;

        if ($myLevel == 0 || $myLevel == 3) {
            return '您的等级无法设置下线等级';
        }
        if ($myLevel == 1 && !in_array($value, [0, 2, 3])) {
            return '一级代理只能设置二级、三级代理或取消代理';
        }
        if ($myLevel == 2 && !in_array($value, [0, 3])) {
            return '二级代理只能设置三级代理或取消代理';
        }
        return true;
    }

    protected function checkTokens($value, $rule, $data)
    {
        $currentUserId = request()->userId;
        $user = User::findOrEmpty($currentUserId);
        if ($user->tokens < $value) {
            return '算力余额不足';
        }
        return true;
    }
}
