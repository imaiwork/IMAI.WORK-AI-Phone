<?php


namespace app\api\lists\kb;

use app\api\lists\BaseApiDataLists;
use app\common\model\kb\KbDigital;
use app\common\service\FileService;

class KbDigitalLists extends BaseApiDataLists
{
    /**
     * @notes 数字人列表
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function lists(): array
    {
        $model = new KbDigital();
        return $model
            ->field(['id,name,avatar,image,is_disable'])
            ->where(['user_id'=>$this->userId])
            ->order('id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    /**
     * @notes 数字人统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public function count(): int
    {
        $model = new KbDigital();
        return $model
            ->where(['user_id'=>$this->userId])
            ->count();
    }
}