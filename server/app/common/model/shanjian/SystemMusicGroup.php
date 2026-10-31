<?php

namespace app\common\model\shanjian;

use app\common\model\BaseModel;

/**
 * 公共音乐分组。
 *
 * 只做「把曲目分成几档」这一件事：用户端选曲时按分组出 tab，
 * 「全部」不筛选。曲目用 group_id 关联，0 表示无分组（后台显示「无」）。
 */
class SystemMusicGroup extends BaseModel
{
    protected $name = 'system_music_group';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    /** 分组名上限：与后台表单 maxlength 一致 */
    public const NAME_MAX_LENGTH = 32;

    /** 无分组曲目用的 group_id */
    public const NO_GROUP = 0;
}
