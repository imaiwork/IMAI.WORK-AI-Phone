<?php


namespace app\common\cache;



class ExportCache extends BaseCache
{

    protected $uniqid = '';

    public function __construct()
    {
        parent::__construct();
        //以微秒计的当前时间，生成一个唯一的 ID,以tagname为前缀
        $this->uniqid = md5(uniqid($this->tagName, true) . mt_rand());
    }

    /**
     * @notes 获取excel缓存目录
     * @return string
     * @author 令狐冲
     * @date 2021/7/28 17:36
     */
    public function getSrc()
    {
        return app()->getRootPath() . 'runtime/file/export/' . date('Y-m') . '/' . $this->uniqid . '/';
    }


    /**
     * @notes 设置文件路径缓存地址
     * @param $fileName
     * @return string
     * @author 令狐冲
     * @date 2021/7/28 17:36
     */
    public function setFile($fileName)
    {
        $src = $this->getSrc();
        $key = md5($src . $fileName) . time();
        $this->store('redis')->set($key, ['src' => $src, 'name' => $fileName], 300);
        return $key;
    }

    /**
     * @notes 获取文件缓存的路径
     * @param $key
     * @return mixed
     * @author 令狐冲
     * @date 2021/7/26 18:36
     */
    public function getFile($key)
    {
        return $this->store('redis')->get($key);
    }
}
