<?php

namespace app\common\model\shanjian;

use app\common\model\BaseModel;

class SystemMusic extends BaseModel
{
    protected $name = 'system_music';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    /** 来源：模板中心同步（内容只读，同步维护 enabled/sort） */
    public const SOURCE_CENTER = 'center';

    /** 来源：站长后台上传（可改名、删除，同步永不触碰） */
    public const SOURCE_LOCAL = 'local';

    /**
     * 本站上传允许的音频格式。
     * 只收各端与闪剪链路都能直接解码的格式——pcm/amr/wma/speex/aiff 这类
     * 系统上传接口虽放行，但做背景音乐时常出现拉不到/解不了，故单独收窄。
     */
    public const LOCAL_ALLOWED_EXTS = ['mp3', 'wav', 'm4a', 'aac', 'flac', 'ogg'];

    /**
     * 生效排序表达式：后台设过本站排序就用它，否则跟随模板中心下发的 sort。
     * 只用于 ORDER BY，不要拿去当字段值读写。
     */
    public const SORT_EXPRESSION = 'IFNULL(sort_override, sort)';
}
