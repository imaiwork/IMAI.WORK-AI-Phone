<?php


namespace app\api\lists;

use app\common\lists\BaseDataLists;

abstract class BaseApiDataLists extends BaseDataLists
{
    protected array $userInfo = [];
    protected int $userId = 0;

    public string $export;

    public function __construct()
    {
        // 必须在父类构造之前获取登录信息：父类构造内的 initExport() 会调用 count() 做导出数据校验，
        // 若此时 userId 尚未注入（恒为 0），开启用户隔离的列表导出会误判“没有数据，无法导出”
        $this->request = request();
        if (isset($this->request->userInfo) && $this->request->userInfo) {
            $this->userInfo = $this->request->userInfo;
            $this->userId = $this->request->userId;
        }
        parent::__construct();
        $this->export = $this->request->get('export', '');
    }
}
