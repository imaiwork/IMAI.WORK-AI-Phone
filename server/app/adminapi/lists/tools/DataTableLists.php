<?php


namespace app\adminapi\lists\tools;

use app\adminapi\lists\BaseAdminDataLists;
use think\facade\Db;


/**
 * 数据表列表
 * Class GeneratorLists
 * @package app\adminapi\lists\tools
 */
class DataTableLists extends BaseAdminDataLists
{

    /**
     * @notes 查询结果
     * @return mixed
     * @author 段誉
     * @date 2022/6/13 18:54
     */
    public function queryResult()
    {
        // 表清单只有几百行，筛选放到 PHP 侧做：SHOW TABLE STATUS 不能稳定走预处理绑定，
        // 直接把入参拼进 LIKE 就是注入口子
        $name = trim((string)($this->params['name'] ?? ''));
        $comment = trim((string)($this->params['comment'] ?? ''));

        $rows = Db::query('SHOW TABLE STATUS');
        if ($name === '' && $comment === '') {
            return $rows;
        }
        return array_values(array_filter($rows, function ($row) use ($name, $comment) {
            $row = array_change_key_case($row);
            if ($name !== '' && stripos((string)($row['name'] ?? ''), $name) === false) {
                return false;
            }
            if ($comment !== '' && mb_stripos((string)($row['comment'] ?? ''), $comment) === false) {
                return false;
            }
            return true;
        }));
    }


    /**
     * @notes 处理列表
     * @return array
     * @author 段誉
     * @date 2022/6/13 18:54
     */
    public function lists(): array
    {
        $lists = array_map("array_change_key_case", $this->queryResult());
        $offset = max(0, ($this->pageNo - 1) * $this->pageSize);
        $lists = array_slice($lists, $offset, $this->pageSize, true);
        return array_values($lists);
    }


    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2022/6/13 18:54
     */
    public function count(): int
    {
        return count($this->queryResult());
    }
}
