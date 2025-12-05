<?php


namespace app\adminapi\validate\setting;


use app\common\validate\BaseValidate;


/**
 * 存储引擎验证
 * Class StorageValidate
 * @package app\adminapi\validate\setting
 */
class StorageValidate extends BaseValidate
{

    protected $rule = [
        'engine' => 'require',
        'status' => 'require',
        'migration' => 'require',
    ];



    /**
     * @notes 设置存储引擎参数场景
     * @return StorageValidate
     * @author 段誉
     * @date 2022/4/20 16:18
     */
    public function sceneSetup()
    {
        return $this->only(['engine', 'status']);
    }


    /**
     * @notes 获取配置参数信息场景
     * @return StorageValidate
     * @author 段誉
     * @date 2022/4/20 16:18
     */
    public function sceneDetail()
    {
        return $this->only(['engine']);
    }


    /**
     * @notes 切换存储引擎场景
     * @return StorageValidate
     * @author 段誉
     * @date 2022/4/20 16:18
     */
    public function sceneChange()
    {
        return $this->only(['engine']);
    }


    /**
     * @notes  迁移
     * @return StorageValidate
     * @author 段誉
     * @date 2022/4/20 16:18
     */
    public function sceneMigration()
    {
        return $this->only(['engine', 'status','migration']);
    }
}
