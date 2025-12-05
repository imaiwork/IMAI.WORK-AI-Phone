<?php
declare (strict_types = 1);

namespace app\api\lists\sop;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\sop\AiWechatSopPush;

/**
 * 推送列表
 * Class PushLists
 * @package app\api\lists\sop
 */
class PushLists  extends BaseApiDataLists implements ListsSearchInterface
{
    
     /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['push_name'],  // 支持推送名称模糊搜索
            '=' => ['status','push_type','type'],          // 支持状态精确搜索

        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
      
        return AiWechatSopPush::where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
   
        return AiWechatSopPush::where($this->searchWhere)->count();
    }
} 