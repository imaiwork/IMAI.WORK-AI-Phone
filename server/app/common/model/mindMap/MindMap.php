<?php


namespace app\common\model\mindMap;

use app\common\enum\YesNoEnum;
use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 资讯收藏
 * Class ArticleCollect
 * @package app\common\model\article
 */
class MindMap extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}
