<?php


namespace app\common\enum;


class FileEnum
{
    // 图片类型
    const IMAGE_TYPE = 10;  // 图片类型
    const VIDEO_TYPE = 20;  // 视频类型
    const AUDIO_TYPE = 40;  // 音频类型
    const FILE_TYPE = 30;  // 文件类型
    const CSV_TYPE = 50;  // 文件类型


    // 图片来源
    const SOURCE_ADMIN = 0;  // 后台
    const SOURCE_USER = 1;   // 用户
    const SOURCE_WECHAT = 3; // 微信
}
