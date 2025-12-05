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
        parent::__construct();
        if (isset($this->request->userInfo) && $this->request->userInfo) {
            $this->userInfo = $this->request->userInfo;
            $this->userId = $this->request->userId;
        }
        $this->export = $this->request->get('export', '');
    }
}
