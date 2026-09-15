<?php
namespace app\api\validate\distributionAgent;

use app\adminapi\logic\setting\DistributionAgentConfigLogic;
use app\common\model\distribution\DistributionAgent;
use app\common\model\user\User;
use app\common\validate\BaseValidate;

class DistributionAgentValidate extends BaseValidate
{
    protected $rule = [
        'user_id' => 'require|checkDownline',
        'level' => 'require|integer|egt:0',
        'tokens' => 'require|integer|gt:0|checkTokens',
        'qr_code' => 'require|max:255',
    ];

    protected $message = [
        'user_id.require' => '请选择下级用户',
        'level.require' => '请选择代理等级',
        'level.integer' => '代理等级错误',
        'level.egt' => '代理等级错误',
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
        $value = (int)$value;
        $currentUserId = request()->userId;
        $currentUserAgent = DistributionAgent::where('user_id', $currentUserId)->findOrEmpty();
        if ($currentUserAgent->isEmpty() || $currentUserAgent->status == 0) {
            return '您当前无权限操作';
        }
        $myLevel = (int)$currentUserAgent->level;

        // 等级清单由后台「代理等级」配置，数量可增删，此处不能写死 1/2/3
        $allLevels = DistributionAgentConfigLogic::getLevelValues();
        if ($value !== 0 && !in_array($value, $allLevels, true)) {
            return '代理等级错误';
        }

        // level 数值越小等级越高，只能把下级设为比自己低的等级或取消代理
        $canSetLevels = array_values(array_filter($allLevels, static function ($level) use ($myLevel) {
            return $level > $myLevel;
        }));
        if ($myLevel === 0 || $canSetLevels === []) {
            return '您的等级无法设置下线等级';
        }
        if ($value !== 0 && !in_array($value, $canSetLevels, true)) {
            return '您只能将下级设置为比自己更低的等级或取消代理';
        }

        // 下级数量上限校验:在 admin 后台「代理等级 → 下级人数上限」配置
        // 取消代理(value=0)不计入上限
        if ($value > 0) {
            $limits = DistributionAgentConfigLogic::getSubLimits();
            $limit = (int)($limits[(string)$myLevel][(string)$value] ?? 0);
            if ($limit > 0) {
                // 已经是当前 level 的下级数(排除本次目标用户,避免他原本就是该级再"重设"被卡)
                $targetUserId = $data['user_id'] ?? 0;
                $existing = DistributionAgent::where('parent_id', $currentUserId)
                    ->where('level', $value)
                    ->where('user_id', '<>', $targetUserId)
                    ->count();
                if ($existing >= $limit) {
                    $levelName = DistributionAgentConfigLogic::getLevelName($value) ?: "{$value}级";
                    return "「{$levelName}」下级数量已达上限（{$limit}人），无法继续设置。";
                }
            }
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
