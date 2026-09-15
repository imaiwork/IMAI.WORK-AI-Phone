<?php

namespace app\api\validate\workData;

use app\common\validate\BaseValidate;

class WorkDataValidate extends BaseValidate
{
    protected $rule = [
        'persona_id' => 'require|integer|gt:0',
        'platform' => 'require|in:douyin,xhs',
        'refresh' => 'in:0,1,true,false,True,False',
        'account' => 'max:64|checkWorkDataAccount',
        'auto_sync_enabled' => 'require|in:0,1',
    ];

    protected $message = [
        'persona_id.require' => '人设不能为空',
        'persona_id.integer' => '人设不能为空',
        'persona_id.gt' => '人设不能为空',
        'platform.require' => '平台不能为空',
        'platform.in' => '不支持的平台',
        'account.max' => '账号格式不正确',
        'account.checkWorkDataAccount' => '账号格式不正确',
    ];

    public function scenePersonas()
    {
        return $this->only([]);
    }

    public function sceneAnalytics()
    {
        return $this->only(['persona_id', 'platform', 'refresh', 'account']);
    }

    public function sceneSyncSettings()
    {
        return $this->only(['persona_id', 'auto_sync_enabled']);
    }

    /**
     * 可选账号选择器：空值跳过；只允许平台号常见字符，禁止 URL / 空白。
     */
    public function checkWorkDataAccount($value, $rule = '', array $data = []): bool
    {
        if ($value === '' || $value === null) {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }
        return (bool)preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9_.\-]+$/u', $value);
    }
}
