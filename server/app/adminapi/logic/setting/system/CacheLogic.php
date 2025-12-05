<?php


namespace app\adminapi\logic\setting\system;

use app\common\logic\BaseLogic;
use think\facade\Cache;

/**
 * 系统缓存逻辑
 * Class CacheLogic
 * @package app\adminapi\logic\setting\system
 */
class CacheLogic extends BaseLogic
{
    /**
     * @notes 清楚系统缓存
     * @author 段誉
     * @date 2022/4/8 16:29
     */
    public static function clear()
    {
        Cache::clear();
        del_target_dir(app()->getRootPath() . 'runtime/file', true);
    }
}
