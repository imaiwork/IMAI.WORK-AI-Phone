<?php

declare(strict_types=1);

namespace app\common\model\draw;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * draw 消息
 */
class DrawMessage extends BaseModel
{
    use SoftDelete;

    protected $name = 'draw_message';
    protected $deleteTime = 'delete_time';

    protected $json = ['attachments', 'params'];
    protected $jsonAssoc = true;

    public function conversation()
    {
        return $this->belongsTo(DrawConversation::class, 'conversation_id', 'id');
    }
}
