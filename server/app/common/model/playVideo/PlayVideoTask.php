<?php

declare(strict_types=1);

namespace app\common\model\playVideo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 爆款玩法任务(la_play_video_task)。template_snapshot/input 为 JSON 文本,由 Logic 编解码。
 */
class PlayVideoTask extends BaseModel
{
    use SoftDelete;

    protected $name = 'play_video_task';
    protected $deleteTime = 'delete_time';
}
