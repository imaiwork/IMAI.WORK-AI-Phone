<?php


namespace app\adminapi\validate\oem;

use app\common\validate\BaseValidate;
use app\common\model\oem\Oem;

/**
 * oem管理验证
 * Class OemValidate
 * @package app\adminapi\validate\oem
 */
class OemValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'domain' => 'require|length:1,255',
        'name' => 'require|length:1,255',
        'logo_url' => 'require',
        'user_id' => 'require',
        'status' => 'require|in:0,1',
        'auth_time' => 'require',
    ];

    protected $message = [
        'id.require' => '参数id不能为空',
        'domain.require' => '站点域名不能为空',
        'domain.length' => '站点域名长度须在1-255位字符',
        'name.require' => '站点名称不能为空',
        'name.length' => '站点名称长度须在1-255位字符',
        'logo_url.require' => 'logo图不能为空',
        'user_id.require' => '用户id必须存在',
        'status.require' => '状态不能为空',
        'auth_time.require' => '授权时间不能为空',

    ];

    /**
     * @notes  添加场景
     * @return OemValidate
     */
    public function sceneAdd()
    {
        return $this->only(['domain', 'logo_url', 'user_id', 'name']);
    }

    /**
     * @notes  详情场景
     * @return OemValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes  更改状态场景
     * @return OemValidate
     */
    public function sceneStatus()
    {
        return $this->only(['id', 'is_show']);
    }

    public function sceneEdit()
    {
        return $this->only(['id', 'domain', 'name', 'logo_url', 'user_id']);
    }

    /**
     * @notes  删除场景
     * @return OemValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes  检查指定oem是否存在
     * @param $value
     * @return bool|string
     */
    public function checkOem($value)
    {
        $oem = Oem::findOrEmpty($value);
        if ($oem->isEmpty()) {
            return 'oem不存在';
        }
        return true;
    }
}
