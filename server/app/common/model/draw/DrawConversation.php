<?php

declare(strict_types=1);

namespace app\common\model\draw;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * draw 会话
 */
class DrawConversation extends BaseModel
{
    use SoftDelete;

    protected $name = 'draw_conversation';
    protected $deleteTime = 'delete_time';

    public function messages()
    {
        return $this->hasMany(DrawMessage::class, 'conversation_id', 'id');
    }
}
