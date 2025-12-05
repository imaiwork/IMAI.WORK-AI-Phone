<?php



namespace app\common\lists;


interface ListsSortInterface
{

    /**
     * @notes 设置支持排序字段
     * @return array
     * @author 令狐冲
     * @date 2021/7/7 19:44
     */
    public function setSortFields(): array;

    /**
     * @notes 设置默认排序条件
     * @return array
     * @author 令狐冲
     * @date 2021/7/16 00:01
     */
    public function setDefaultOrder(): array;
}
