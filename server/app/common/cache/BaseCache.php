<?php


declare(strict_types=1);

namespace app\common\cache;

use think\App;
use think\Cache;

/**
 * 缓存基础类，用于管理缓存
 * Class BaseCache
 * @package app\common\cache
 */
abstract class BaseCache extends Cache
{
    /**
     * 缓存标签
     * @var string
     */
    protected $tagName;

    public function __construct()
    {
        parent::__construct(app());
        $this->tagName = get_class($this);
    }


    /**
     * @notes 重写父类set，自动打上标签
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     * @return bool
     * @author 段誉
     * @date 2021/12/27 14:16
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->store()->tag($this->tagName)->set($key, $value, $ttl);
    }


    /**
     * @notes 清除缓存类所有缓存
     * @return bool
     * @author 段誉
     * @date 2021/12/27 14:16
     */
    public function deleteTag(): bool
    {
        return $this->tag($this->tagName)->clear();
    }
}
