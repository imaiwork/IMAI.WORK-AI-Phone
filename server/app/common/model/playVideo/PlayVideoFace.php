<?php

declare(strict_types=1);

namespace app\common\model\playVideo;

use app\common\model\BaseModel;

/**
 * 爆款玩法-人脸素材登记(la_play_video_face)。
 * 只记对象归属,签名 URL 只签发给素材归属用户,防止跨用户签名越权。
 */
class PlayVideoFace extends BaseModel
{
    protected $name = 'play_video_face';
}
