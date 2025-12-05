<?php


namespace app\adminapi\lists\setting;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\SensitiveWord;

/**
 * 敏感词列表
 */
class SensitiveWordLists extends BaseAdminDataLists
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function lists(): array
    {
        return (new SensitiveWord())
            ->where($this->setSearch())
            ->withoutField('update_time,delete_time')
            ->append(['word_arr'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id'=>'desc'])
            ->select()->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public function count(): int
    {
        return (new SensitiveWord())->where($this->setSearch())->count();
    }

    /**
     * @notes 条件
     * @return array
     * @author kb
     */
    public function setSearch(): array
    {
        $where = [];
        if (isset($this->params['keyword']) && $this->params['keyword'] != '') {
            $where[] = ['word','like','%'.$this->params['keyword'].'%'];
        }
        if (isset($this->params['status']) && $this->params['status'] != '') {
            $where[] = ['status','=',$this->params['status']];
        }

        return $where;
    }
}