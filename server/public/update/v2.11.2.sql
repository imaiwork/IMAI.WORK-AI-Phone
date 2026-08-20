-- 自动视频合成设备公平轮询：按每日调度次数从小到大执行
ALTER TABLE `la_sv_device`
ADD COLUMN `synthesis_m_retry_count` int unsigned NOT NULL DEFAULT '0' COMMENT '社媒视频合成每日调度次数';


-- 成片自动下载独立计数，避免占用合成 tries
ALTER TABLE `la_shanjian_video_task`
ADD COLUMN `download_tries` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '成片自动下载尝试次数' AFTER `download_status`;


UPDATE `la_dev_crontab` SET    `expression` = '*/8 * * * *'   WHERE `command` = 'auto_video_synthesis';